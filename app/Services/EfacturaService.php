<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Document;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use SimpleXMLElement;

class EfacturaService
{
    public function __construct(
        private AnafOAuthService $oauth,
        private EfacturaUblBuilder $ubl,
    ) {}

    public function send(Document $document): Document
    {
        $document->loadMissing(['items', 'company', 'client']);
        $company = $document->company;

        if (! in_array($document->type, ['invoice', 'credit_note'], true)
            || ! in_array($document->status, ['issued', 'storno'], true)) {
            throw new RuntimeException('Doar facturile emise / storno / notele de creditare pot fi trimise în e-Factura.');
        }

        if (! $this->oauth->isConfigured()) {
            throw new RuntimeException('ANAF OAuth nu este configurat pe server (Client ID / Secret).');
        }

        if (! $company->isAnafAuthorized()) {
            throw new RuntimeException('Autorizează mai întâi SPV ANAF din setările societății.');
        }

        if (($document->efactura_status ?: 'none') === 'ok') {
            throw new RuntimeException('Factura este deja acceptată în e-Factura.');
        }

        // Retrimitere (nok / error / uploaded / processing / queued): curățăm ID-urile vechi.
        if (filled($document->efactura_upload_id) || in_array($document->efactura_status, ['nok', 'error', 'uploaded', 'processing', 'queued'], true)) {
            $document->forceFill([
                'efactura_upload_id' => null,
                'efactura_download_id' => null,
                'efactura_error' => null,
                'efactura_sent_at' => null,
                'efactura_checked_at' => null,
                'efactura_scheduled_at' => null,
            ])->save();
        }

        $xml = $this->ubl->build($document);
        $cif = $company->numericCui();
        if ($cif === '') {
            throw new RuntimeException('CUI-ul firmei lipsește.');
        }

        $token = $this->oauth->accessToken($company->fresh());
        $endpoint = $this->ubl->isB2c($document) ? 'uploadb2c' : 'upload';
        $url = rtrim((string) config('dateconta.anaf.api_base'), '/').'/'.$endpoint
            .'?'.http_build_query(['standard' => 'UBL', 'cif' => $cif]);

        $response = Http::withToken($token)
            ->withHeaders(['Content-Type' => 'application/xml'])
            ->withBody($xml, 'application/xml')
            ->post($url);

        $body = $response->body();
        Log::info('e-Factura upload', [
            'document_id' => $document->id,
            'status' => $response->status(),
            'body' => mb_substr($body, 0, 2000),
        ]);

        if (! $response->successful()) {
            $message = $this->extractError($body) ?: ('Upload e-Factura eșuat (HTTP '.$response->status().').');
            $document->update([
                'efactura_status' => 'error',
                'efactura_error' => $message,
                'efactura_checked_at' => now(),
                'efactura_auto_next_at' => now()->addMinutes(2),
            ]);

            throw new RuntimeException($message);
        }

        $uploadId = $this->extractUploadId($body);
        if (! $uploadId) {
            $message = $this->extractError($body) ?: 'ANAF nu a returnat index de încărcare.';
            $document->update([
                'efactura_status' => 'error',
                'efactura_error' => $message,
                'efactura_checked_at' => now(),
                'efactura_auto_next_at' => now()->addMinutes(2),
            ]);

            throw new RuntimeException($message);
        }

        $document->update([
            'efactura_status' => 'uploaded',
            'efactura_upload_id' => $uploadId,
            'efactura_error' => null,
            'efactura_sent_at' => now(),
            'efactura_checked_at' => now(),
            'efactura_scheduled_at' => null,
            'efactura_auto_next_at' => now(),
        ]);

        $fresh = $this->refreshStatus($document->fresh());
        if ($fresh->efactura_status === 'ok') {
            app(EfacturaReconcileService::class)->clearAutoState($fresh);
        }

        return $fresh;
    }

    public function refreshStatus(Document $document): Document
    {
        $document->loadMissing('company');

        if (! $document->efactura_upload_id) {
            return $document;
        }

        if (in_array($document->efactura_status, ['ok', 'nok'], true)) {
            return $document;
        }

        $company = $document->company;
        $token = $this->oauth->accessToken($company);
        $url = rtrim((string) config('dateconta.anaf.api_base'), '/')
            .'/stareMesaj?'.http_build_query(['id_incarcare' => $document->efactura_upload_id]);

        $response = Http::withToken($token)->get($url);
        $body = $response->body();
        $document->efactura_checked_at = now();

        if (! $response->successful()) {
            $document->efactura_error = $this->extractError($body) ?: 'Nu am putut interoga starea mesajului.';
            $document->save();

            return $document;
        }

        // ANAF răspunde tipic cu atribute pe <header stare="nok" id_descarcare="…"/>, nu tag-uri.
        $stare = mb_strtolower(
            $this->extractTag($body, 'stare')
                ?: $this->extractAttr($body, 'stare')
                ?: ''
        );
        $downloadId = $this->extractTag($body, 'id_descarcare')
            ?: $this->extractTag($body, 'id')
            ?: $this->extractAttr($body, 'id_descarcare')
            ?: $this->extractAttr($body, 'id');

        if ($stare === 'ok' || (str_contains($stare, 'ok') && ! str_contains($stare, 'nok'))) {
            $document->efactura_status = 'ok';
            $document->efactura_error = null;
            $document->efactura_download_id = $downloadId ?: $document->efactura_download_id;
            $document->efactura_auto_attempts = 0;
            $document->efactura_auto_last_error = null;
            $document->efactura_auto_next_at = null;
            $document->efactura_auto_alerted_at = null;
        } elseif ($stare === 'nok' || str_contains($stare, 'nok') || str_contains($stare, 'erori')) {
            $document->efactura_status = 'nok';
            $document->efactura_download_id = $downloadId ?: $document->efactura_download_id;
            $document->efactura_error = $this->fetchRejectionMessage($company, (string) $document->efactura_download_id)
                ?: $this->extractError($body)
                ?: 'Factura a fost respinsă de ANAF.';
            // nok = netrimisă pentru automatizare — programăm reîncercarea.
            if (! $document->efactura_auto_next_at || $document->efactura_auto_next_at->isPast()) {
                $document->efactura_auto_next_at = now();
            }
        } elseif (str_contains($stare, 'prelucrare')) {
            $document->efactura_status = 'processing';
        } elseif ($stare !== '') {
            $document->efactura_status = 'uploaded';
            $document->efactura_error = 'Stare ANAF necunoscută: '.$stare;
        } else {
            // Răspuns fără stare — lăsăm uploaded, dar păstrăm corpul scurt pentru depanare.
            $document->efactura_status = 'uploaded';
            if (! filled($document->efactura_error)) {
                $document->efactura_error = 'ANAF nu a returnat starea mesajului. Reîncearcă actualizarea.';
            }
        }

        $document->save();

        return $document;
    }

    /**
     * Descarcă ZIP-ul de răspuns ANAF (la nok) și extrage mesajele de eroare din XML.
     */
    private function fetchRejectionMessage(Company $company, string $downloadId): ?string
    {
        $downloadId = trim($downloadId);
        if ($downloadId === '') {
            return null;
        }

        try {
            $token = $this->oauth->accessToken($company);
            $url = rtrim((string) config('dateconta.anaf.api_base'), '/')
                .'/descarcare?'.http_build_query(['id' => $downloadId]);
            $response = Http::withToken($token)->get($url);
            if (! $response->successful()) {
                return null;
            }

            $bytes = $response->body();
            if ($bytes === '' || $bytes === false) {
                return null;
            }

            $tmp = tempnam(sys_get_temp_dir(), 'efactura_err_');
            if ($tmp === false) {
                return null;
            }
            file_put_contents($tmp, $bytes);

            $errors = [];
            $zip = new \ZipArchive;
            if ($zip->open($tmp) === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = (string) $zip->getNameIndex($i);
                    if (! str_ends_with(mb_strtolower($name), '.xml')) {
                        continue;
                    }
                    $xml = (string) $zip->getFromIndex($i);
                    if ($xml === '') {
                        continue;
                    }
                    if (preg_match_all('/(?:errorMessage|message|Error|erare|descriere)\s*=\s*"([^"]+)"/iu', $xml, $m)) {
                        foreach ($m[1] as $msg) {
                            $errors[] = trim($msg);
                        }
                    }
                    foreach (['errorMessage', 'ErrorMessage', 'message', 'mesaj', 'Error', 'descriere', 'Details'] as $tag) {
                        $value = $this->extractTag($xml, $tag);
                        if ($value) {
                            $errors[] = $value;
                        }
                    }
                    // Unele răspunsuri pun textul erorii direct în noduri <Error>…</Error>
                    if (preg_match_all('/<Error[^>]*>([^<]+)</iu', $xml, $m2)) {
                        foreach ($m2[1] as $msg) {
                            $errors[] = trim($msg);
                        }
                    }
                }
                $zip->close();
            } else {
                // Uneori ANAF răspunde XML fără ZIP.
                $plain = $this->extractError($bytes);
                if ($plain) {
                    $errors[] = $plain;
                }
            }
            @unlink($tmp);

            $errors = array_values(array_unique(array_filter(array_map(
                static fn ($e) => trim((string) $e),
                $errors
            ), static fn ($e) => $e !== '')));

            if ($errors === []) {
                return null;
            }

            return mb_substr(implode(' | ', array_slice($errors, 0, 5)), 0, 1000);
        } catch (\Throwable $e) {
            Log::warning('e-Factura rejection download failed', [
                'company_id' => $company->id,
                'download_id' => $downloadId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function scheduleAfterIssue(Document $document): void
    {
        try {
            $document->loadMissing('company');
            $company = $document->company;

            if (! in_array($document->type, ['invoice', 'credit_note'], true)
                || ! in_array($document->status, ['issued', 'storno'], true)) {
                return;
            }

            if (! $company?->shouldQueueEfacturaOnIssue() || ! $company->isAnafAuthorized()) {
                return;
            }

            $mode = $company->efacturaSendMode();
            $delayDays = $company->efacturaDelayDays();

            if ($mode === 'on_save') {
                $document->update([
                    'efactura_status' => 'queued',
                    'efactura_error' => null,
                    'efactura_scheduled_at' => now(),
                    'efactura_auto_next_at' => now(),
                ]);
                $this->send($document->fresh());

                return;
            }

            if ($delayDays) {
                $document->update([
                    'efactura_status' => 'queued',
                    'efactura_error' => null,
                    'efactura_scheduled_at' => now()->addDays($delayDays)->startOfMinute(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('e-Factura schedule failed', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);

            $document->refresh();
            if (! in_array($document->efactura_status, ['error', 'nok', 'ok', 'uploaded', 'processing'], true)) {
                $document->update([
                    'efactura_status' => 'error',
                    'efactura_error' => $e->getMessage(),
                    'efactura_checked_at' => now(),
                    'efactura_auto_next_at' => now()->addMinutes(2),
                ]);
            }
        }
    }

    public function processDueScheduled(int $limit = 30): int
    {
        $documents = Document::query()
            ->whereIn('type', ['invoice', 'credit_note'])
            ->whereIn('status', ['issued', 'storno'])
            ->where('efactura_status', 'queued')
            ->whereNotNull('efactura_scheduled_at')
            ->where('efactura_scheduled_at', '<=', now())
            ->with('company')
            ->orderBy('efactura_scheduled_at')
            ->limit($limit)
            ->get();

        $sent = 0;
        foreach ($documents as $document) {
            if (! $document->company?->isAnafAuthorized()) {
                continue;
            }

            try {
                $this->send($document);
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('e-Factura scheduled send failed', [
                    'document_id' => $document->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    /** @deprecated use scheduleAfterIssue */
    public function tryAutoSend(Document $document): void
    {
        $this->scheduleAfterIssue($document);
    }

    private function extractUploadId(string $body): ?string
    {
        return $this->extractTag($body, 'index_incarcare')
            ?: $this->extractTag($body, 'id_incarcare')
            ?: $this->extractAttr($body, 'index_incarcare')
            ?: $this->extractAttr($body, 'id_incarcare');
    }

    private function extractTag(string $body, string $tag): ?string
    {
        if (preg_match('/<'.$tag.'[^>]*>([^<]+)</i', $body, $m)) {
            return trim($m[1]);
        }

        try {
            $xml = new SimpleXMLElement($body);
            $nodes = $xml->xpath('//*[local-name()="'.$tag.'"]');
            if (! empty($nodes[0])) {
                return trim((string) $nodes[0]);
            }
        } catch (\Throwable) {
            // ignore parse errors
        }

        return null;
    }

    private function extractAttr(string $body, string $attr): ?string
    {
        if (preg_match('/'.$attr.'\s*=\s*"([^"]+)"/i', $body, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    private function extractError(string $body): ?string
    {
        foreach (['Errors', 'error', 'ErrorMessage', 'mesaj', 'message', 'Fault', 'detalii'] as $tag) {
            $value = $this->extractTag($body, $tag);
            if ($value) {
                return mb_substr($value, 0, 1000);
            }
        }

        if (preg_match('/errorMessage\s*=\s*"([^"]+)"/i', $body, $m)) {
            return mb_substr($m[1], 0, 1000);
        }

        $plain = trim(strip_tags($body));

        return $plain !== '' ? mb_substr($plain, 0, 1000) : null;
    }
}
