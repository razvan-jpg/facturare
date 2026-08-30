<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\URL;

class DocumentEmailTemplate
{
    /** @return array<string, string> label => token */
    public static function variablePalette(): array
    {
        return [
            'Document' => [
                'Tip document' => '#tip document#',
                'Link document' => '#link document#',
                'Număr document' => '#numar document#',
                'Serie număr document' => '#serie numar document#',
                'Total' => '#total document#',
                'Data emiterii' => '#data emiterii#',
                'Data scadenței' => '#data scadentei#',
                'Mențiune' => '#mentiune#',
            ],
            'Client' => [
                'Nume client' => '#nume client#',
                'Contact client' => '#contact client#',
            ],
            'Societate' => [
                'Nume societate' => '#societate#',
            ],
        ];
    }

    public function defaultSubject(): string
    {
        return __('Documentul #serie numar document#');
    }

    public function defaultBody(): string
    {
        return __("Bună ziua,\n\n"
            ."Vă anunțăm că am emis #tip document# #serie numar document# în valoare de #total document#.\n\n"
            ."Puteți vizualiza documentul dând click pe butonul de mai jos sau deschizând PDF-ul atașat.\n\n"
            ."Mulțumim pentru colaborare!\n"
            ."#societate#");
    }

    public function subjectFor(Document $document): string
    {
        $company = $document->company;
        $template = filled($company?->email_invoice_subject)
            ? $company->email_invoice_subject
            : $this->defaultSubject();

        return $this->expand($template, $document);
    }

    public function bodyFor(Document $document): string
    {
        $company = $document->company;
        $template = filled($company?->email_invoice_body)
            ? $company->email_invoice_body
            : $this->defaultBody();

        return $this->expand($template, $document);
    }

    public function expand(string $template, Document $document): string
    {
        $document->loadMissing(['company', 'client']);
        $map = $this->replacements($document);

        // Case-insensitive replace for #tokens#
        $text = preg_replace_callback(
            '/#[^#]+#/',
            function (array $m) use ($map) {
                $key = mb_strtolower(trim($m[0]));

                return $map[$key] ?? $m[0];
            },
            $template
        ) ?? $template;

        // Legacy {placeholders}
        return strtr($text, [
            '{company}' => $map['#societate#'] ?? '',
            '{number}' => $map['#serie numar document#'] ?? '',
            '{client}' => $map['#nume client#'] ?? '',
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function replacements(Document $document): array
    {
        $company = $document->company;
        $total = number_format((float) $document->total, 2, ',', '.').' '.($document->currency ?: 'RON');
        $number = trim((string) ($document->number_full ?: '#'.$document->id));
        $link = URL::temporarySignedRoute(
            'documents.pdf.signed',
            now()->addDays(30),
            ['document' => $document->id]
        );

        $contact = trim((string) (
            $document->client_email
            ?: $document->client?->email
            ?: $document->client?->phone
            ?: ''
        ));

        $pairs = [
            '#tip document#' => $document->typeLabel(),
            '#link document#' => $link,
            '#numar document#' => $number,
            '#serie numar document#' => $number,
            '#total document#' => $total,
            '#total#' => $total,
            '#data emiterii#' => dc_date($document->issue_date, ''),
            '#data scadentei#' => dc_date($document->due_date, ''),
            '#mentiune#' => trim((string) ($document->notes ?: '')),
            '#nume client#' => trim((string) ($document->client_name ?: $document->client?->name ?: '')),
            '#contact client#' => $contact,
            '#societate#' => trim((string) ($company?->name ?: '')),
        ];

        $map = [];
        foreach ($pairs as $token => $value) {
            $map[mb_strtolower($token)] = $value;
        }

        return $map;
    }
}
