<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Document;
use App\Models\DocumentCardPayment;
use App\Models\SubscriptionOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Netopia: abonament platformă (.env / PlatformSettings) + plăți document (credite firmă).
 */
class NetopiaPaymentService
{
    public function __construct(private CompanyIntegrations $integrations) {}

    /** Configurat pentru abonamentul DateConta (platformă sau firma operator FLY DAVID). */
    public function isConfigured(): bool
    {
        return $this->subscriptionCredentials() !== null;
    }

    /**
     * Detalii readiness (Admin / debug) — fără secrete.
     *
     * @return array{
     *     ready: bool,
     *     enabled: bool,
     *     signature: bool,
     *     public_cer: bool,
     *     private_key: bool,
     *     sandbox: bool|null,
     *     payment_url: string|null,
     *     source: string|null,
     *     missing: list<string>
     * }
     */
    public function configurationStatus(): array
    {
        $creds = $this->subscriptionCredentials();
        if ($creds !== null) {
            return [
                'ready' => true,
                'enabled' => true,
                'signature' => true,
                'public_cer' => true,
                'private_key' => true,
                'sandbox' => (bool) ($creds['sandbox'] ?? false),
                'payment_url' => $creds['payment_url'],
                'source' => $creds['source'],
                'missing' => [],
            ];
        }

        $enabled = (bool) config('netopia.enabled');
        $sig = trim((string) config('netopia.signature')) !== '';
        $pubPath = (string) config('netopia.public_key_path');
        $privPath = (string) config('netopia.private_key_path');
        $pub = $pubPath !== '' && is_readable($pubPath);
        $priv = $privPath !== '' && is_readable($privPath);

        $missing = [];
        if (! $enabled) {
            $missing[] = 'Activează NETOPIA în Admin → Integrări, sau pe firma FLY DAVID (Setări → Integrări).';
        }
        if (! $sig) {
            $missing[] = 'Lipsește semnătura merchant.';
        }
        if (! $pub) {
            $missing[] = 'Lipsește sau nu e citibil public.cer.';
        }
        if (! $priv) {
            $missing[] = 'Lipsește sau nu e citibil private.key.';
        }

        return [
            'ready' => false,
            'enabled' => $enabled,
            'signature' => $sig,
            'public_cer' => $pub,
            'private_key' => $priv,
            'sandbox' => (bool) config('netopia.sandbox'),
            'payment_url' => (string) config('netopia.payment_url'),
            'source' => null,
            'missing' => $missing,
        ];
    }

    /**
     * Credențiale pentru plata abonamentului: mai întâi firma operator (FLY DAVID),
     * ca setările din aplicație (inclusiv sandbox) să aibă prioritate; altfel Admin/.env.
     *
     * @return array{signature:string,public_key_path:string,payment_url:string,source:string,sandbox:bool}|null
     */
    public function subscriptionCredentials(): ?array
    {
        $operator = $this->platformOperatorCompany();
        if ($operator && $this->integrations->isNetopiaReady($operator)) {
            $sandbox = $this->integrations->getBool($operator, 'netopia', 'sandbox', false);

            return [
                'signature' => trim((string) $this->integrations->get($operator, 'netopia', 'signature', '')),
                'public_key_path' => $this->integrations->netopiaPublicPath($operator),
                'payment_url' => $this->integrations->netopiaPaymentUrl($operator),
                'source' => 'operator_company',
                'sandbox' => $sandbox,
            ];
        }

        $enabled = (bool) config('netopia.enabled');
        $sig = trim((string) config('netopia.signature'));
        $pubPath = (string) config('netopia.public_key_path');
        $privPath = (string) config('netopia.private_key_path');

        if ($enabled && $sig !== '' && is_readable($pubPath) && is_readable($privPath)) {
            return [
                'signature' => $sig,
                'public_key_path' => $pubPath,
                'payment_url' => (string) config('netopia.payment_url'),
                'source' => 'platform',
                'sandbox' => (bool) config('netopia.sandbox'),
            ];
        }

        return null;
    }

    public function platformOperatorCompany(): ?Company
    {
        $cui = preg_replace('/\D+/', '', (string) config('dateconta.platform_operator.cui', '')) ?: '';
        if ($cui === '') {
            return null;
        }

        return Company::query()
            ->where(function ($q) use ($cui) {
                $q->where('cui', $cui)
                    ->orWhere('cui', 'RO'.$cui)
                    ->orWhere('cui', 'ro'.$cui);
            })
            ->orderBy('id')
            ->first();
    }

    /**
     * @return array{url:string,env_key:string,data:string,cipher:string,iv:string}
     */
    public function buildCardPaymentForm(SubscriptionOrder $order): array
    {
        $creds = $this->subscriptionCredentials();
        if ($creds === null) {
            throw new RuntimeException('Netopia nu este configurat (chei / semnătură lipsă).');
        }

        $xml = $this->buildOrderXml(
            signature: $creds['signature'],
            orderId: $order->number,
            amount: (float) $order->amount_total,
            currency: (string) $order->currency,
            description: config('dateconta.subscription.product_name').' — '.$order->periodLabel().' ('.$order->billing_name.')',
            returnUrl: route('billing.netopia.return', ['order' => $order->number]),
            billingName: (string) $order->billing_name,
            billingEmail: (string) $order->billing_email,
            billingPhone: (string) ($order->billing_phone ?: '0700000000'),
            billingAddress: (string) ($order->billing_address ?: '-'),
            billingCui: (string) ($order->billing_cui ?: ''),
            confirmUrl: route('billing.netopia.confirm'),
        );
        $encrypted = $this->encrypt($xml, $creds['public_key_path']);

        return [
            'url' => $creds['payment_url'],
            'env_key' => $encrypted['env_key'],
            'data' => $encrypted['data'],
            'cipher' => $encrypted['cipher'],
            'iv' => $encrypted['iv'],
        ];
    }

    /**
     * @return array{url:string,env_key:string,data:string,cipher:string,iv:string}
     */
    public function buildDocumentPaymentForm(DocumentCardPayment $checkout, Document $document): array
    {
        $document->loadMissing('company');
        $company = $document->company;
        if (! $company || ! $this->integrations->isNetopiaReady($company)) {
            throw new RuntimeException('NETOPIA nu este configurat pentru această firmă.');
        }

        $xml = $this->buildOrderXml(
            signature: (string) $this->integrations->get($company, 'netopia', 'signature', ''),
            orderId: $checkout->checkout_number,
            amount: (float) $checkout->amount,
            currency: (string) $checkout->currency,
            description: 'Plată '.$document->number_full.' — '.$document->client_name,
            returnUrl: route('documents.pay.return', ['checkout' => $checkout->checkout_number]),
            billingName: (string) $document->client_name,
            billingEmail: (string) ($document->client_email ?: 'client@example.com'),
            billingPhone: '0700000000',
            billingAddress: (string) ($document->client_address ?: '-'),
            billingCui: (string) ($document->client_cui ?: ''),
            confirmUrl: route('plata.netopia.confirm'),
        );
        $encrypted = $this->encrypt($xml, $this->integrations->netopiaPublicPath($company));

        return [
            'url' => $this->integrations->netopiaPaymentUrl($company),
            'env_key' => $encrypted['env_key'],
            'data' => $encrypted['data'],
            'cipher' => $encrypted['cipher'],
            'iv' => $encrypted['iv'],
        ];
    }

    /**
     * Sincronizare la return (browser) — ca Mollie/Eu Plătesc.
     * Dacă Netopia trimite env_key/data pe return (GET/POST), procesăm ca IPN;
     * altfel așteptăm scurt IPN-ul sincron care ar trebui să fi ajuns deja.
     */
    public function syncSubscriptionOrder(SubscriptionOrder $order, Request $request): SubscriptionOrder
    {
        if ($order->isPaid() || $order->status === 'failed') {
            return $order;
        }

        $this->applyReturnPayload($request);

        $order->refresh();
        if ($order->isPaid() || $order->status === 'failed') {
            return $order;
        }

        // Cursa: confirm (IPN) sincron e înainte de redirect, dar uneori finalizează după return.
        for ($i = 0; $i < 6; $i++) {
            usleep(350000);
            $order->refresh();
            if ($order->isPaid() || $order->status === 'failed') {
                break;
            }
        }

        return $order->fresh();
    }

    /**
     * Sincronizare return pentru plata document (DF-…).
     */
    public function syncDocumentCheckout(DocumentCardPayment $checkout, Request $request): DocumentCardPayment
    {
        if ($checkout->isPaid() || $checkout->status === 'failed') {
            return $checkout;
        }

        $this->applyReturnPayload($request);

        $checkout->refresh();
        if ($checkout->isPaid() || $checkout->status === 'failed') {
            return $checkout;
        }

        for ($i = 0; $i < 6; $i++) {
            usleep(350000);
            $checkout->refresh();
            if ($checkout->isPaid() || $checkout->status === 'failed') {
                break;
            }
        }

        return $checkout->fresh();
    }

    /**
     * Procesează payload IPN dacă e prezent pe request-ul de return.
     */
    public function applyReturnPayload(Request $request): void
    {
        $envKey = (string) $request->input('env_key', $request->query('env_key', ''));
        $data = (string) $request->input('data', $request->query('data', ''));
        if ($envKey === '' || $data === '') {
            return;
        }

        $iv = $request->input('iv', $request->query('iv'));
        $cipher = $request->input('cipher', $request->query('cipher'));

        Log::info('netopia.return_payload', [
            'has_iv' => filled($iv),
            'cipher' => $cipher,
        ]);

        $result = $this->handleConfirm($envKey, $data, $iv !== null ? (string) $iv : null, $cipher !== null ? (string) $cipher : null);
        if ($result['error']) {
            Log::warning('netopia.return_payload_failed', ['error' => $result['error']]);
        }
    }

    /**
     * @return array{xml:string,order:?SubscriptionOrder,error:?string}
     */
    public function handleConfirm(string $envKey, string $data, ?string $iv = null, ?string $cipher = null): array
    {
        try {
            $xmlString = $this->decryptIpn($envKey, $data, $iv, $cipher);
            $xml = simplexml_load_string($xmlString);
            if ($xml === false) {
                throw new RuntimeException('XML IPN invalid.');
            }

            $orderNumber = (string) ($xml->attributes()->id ?? '');
            $errorCode = isset($xml->mobilpay->error)
                ? (string) $xml->mobilpay->error->attributes()->code
                : '0';
            $action = isset($xml->mobilpay) ? (string) ($xml->mobilpay->attributes()->action ?? '') : '';
            $ref = (string) ($xml->mobilpay->purchase->attributes()->crc ?? '');

            Log::info('netopia.ipn', [
                'order' => $orderNumber,
                'action' => $action,
                'error_code' => $errorCode,
                'ref' => $ref,
            ]);

            if (str_starts_with($orderNumber, 'DF-')) {
                $checkout = app(DocumentCardPaymentService::class)->findByCheckoutNumber($orderNumber);
                if (! $checkout) {
                    throw new RuntimeException('Checkout document '.$orderNumber.' nu a fost găsit.');
                }
                if ($errorCode === '0' && in_array($action, ['confirmed', 'paid', 'confirmed_pending'], true)) {
                    app(DocumentCardPaymentService::class)->markPaid($checkout, $ref ?: $orderNumber);
                } elseif ($errorCode !== '0') {
                    app(DocumentCardPaymentService::class)->markFailed(
                        $checkout,
                        'Netopia '.$errorCode.': '.(string) ($xml->mobilpay->error ?? '')
                    );
                }

                return [
                    'xml' => '<?xml version="1.0" encoding="utf-8"?><crc error_type="0" error_code="0">OK</crc>',
                    'order' => null,
                    'error' => null,
                ];
            }

            $order = SubscriptionOrder::query()->where('number', $orderNumber)->first();
            if (! $order) {
                throw new RuntimeException('Comanda '.$orderNumber.' nu a fost găsită.');
            }

            if ($errorCode === '0' && in_array($action, ['confirmed', 'paid', 'confirmed_pending'], true)) {
                app(SubscriptionOrderService::class)->markPaid($order, $ref);
            } elseif ($errorCode !== '0') {
                $order->forceFill([
                    'status' => 'failed',
                    'netopia_error' => 'Netopia '.$errorCode.': '.(string) ($xml->mobilpay->error ?? ''),
                ])->save();
            }

            return [
                'xml' => '<?xml version="1.0" encoding="utf-8"?><crc error_type="0" error_code="0">OK</crc>',
                'order' => $order->fresh(),
                'error' => null,
            ];
        } catch (\Throwable $e) {
            $msg = htmlspecialchars($e->getMessage(), ENT_XML1);

            return [
                'xml' => '<?xml version="1.0" encoding="utf-8"?><crc error_type="1" error_code="1">'.$msg.'</crc>',
                'order' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    private function buildOrderXml(
        string $signature,
        string $orderId,
        float $amount,
        string $currency,
        string $description,
        string $returnUrl,
        string $billingName,
        string $billingEmail,
        string $billingPhone,
        string $billingAddress,
        string $billingCui,
        ?string $confirmUrl = null,
    ): string {
        $confirmUrl = $confirmUrl ?: route('billing.netopia.confirm');
        $amountFmt = number_format($amount, 2, '.', '');
        $currency = strtoupper($currency);
        $desc = htmlspecialchars($description, ENT_XML1);

        $type = filled($billingCui) ? 'company' : 'person';
        $name = htmlspecialchars($billingName, ENT_XML1);
        $email = htmlspecialchars($billingEmail, ENT_XML1);
        $phone = htmlspecialchars($billingPhone, ENT_XML1);
        $address = htmlspecialchars($billingAddress, ENT_XML1);
        $cui = htmlspecialchars($billingCui, ENT_XML1);

        $billing = $type === 'company'
            ? "<billing type=\"company\"><identity>{$cui}</identity><company>{$name}</company><email>{$email}</email><mobilePhone>{$phone}</mobilePhone><address>{$address}</address></billing>"
            : "<billing type=\"person\"><first_name>{$name}</first_name><last_name>-</last_name><email>{$email}</email><mobilePhone>{$phone}</mobilePhone><address>{$address}</address></billing>";

        $timestamp = date('YmdHis');

        return '<?xml version="1.0" encoding="utf-8"?>'
            .'<order type="card" id="'.htmlspecialchars($orderId, ENT_XML1).'" timestamp="'.$timestamp.'">'
            .'<signature>'.htmlspecialchars($signature, ENT_XML1).'</signature>'
            .'<url><return>'.htmlspecialchars($returnUrl, ENT_XML1).'</return><confirm>'.htmlspecialchars($confirmUrl, ENT_XML1).'</confirm></url>'
            .'<invoice currency="'.$currency.'" amount="'.$amountFmt.'"><details>'.$desc.'</details>'
            .'<contact_info>'.$billing.'</contact_info></invoice>'
            .'</order>';
    }

    /** @return array{env_key:string,data:string,cipher:string,iv:string} */
    private function encrypt(string $xml, string $publicKeyPath): array
    {
        $publicKey = openssl_pkey_get_public(file_get_contents($publicKeyPath));
        if ($publicKey === false) {
            throw new RuntimeException('Cheia publică Netopia este invalidă.');
        }

        // Aliniat cu SDK Netopia / Mobilpay: AES-256-CBC + IV generat de openssl_seal.
        $cipher = 'aes-256-cbc';
        $methods = openssl_get_cipher_methods();
        if (! in_array($cipher, $methods, true) && in_array(strtoupper($cipher), $methods, true)) {
            $cipher = strtoupper($cipher);
        }

        $iv = '';
        $envKeys = [];
        $sealed = '';
        $ok = openssl_seal($xml, $sealed, $envKeys, [$publicKey], $cipher, $iv);
        if (! $ok || empty($envKeys[0])) {
            throw new RuntimeException('Criptarea Netopia a eșuat.');
        }

        return [
            'env_key' => base64_encode($envKeys[0]),
            'data' => base64_encode($sealed),
            'cipher' => $cipher,
            'iv' => $iv !== '' ? base64_encode($iv) : '',
        ];
    }

    private function decryptIpn(string $envKey, string $data, ?string $iv = null, ?string $cipher = null): string
    {
        $paths = [];
        if (is_readable((string) config('netopia.private_key_path'))) {
            $paths[] = (string) config('netopia.private_key_path');
        }

        // Cheile firmelor de pe disk (plăți facturi clienți) — nu doar checkout-uri pending.
        foreach ($this->integrations->allNetopiaPrivateKeyPaths() as $path) {
            $paths[] = $path;
        }

        // Fallback: pending recente (în caz că fișierele au fost mutate).
        $companyIds = DocumentCardPayment::query()
            ->where('processor', 'netopia')
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subDays(14))
            ->distinct()
            ->pluck('company_id');

        foreach ($companyIds as $companyId) {
            $company = Company::query()->find($companyId);
            if (! $company) {
                continue;
            }
            $path = $this->integrations->netopiaPrivatePath($company);
            if (is_readable($path)) {
                $paths[] = $path;
            }
        }

        $paths = array_values(array_unique($paths));
        $last = null;
        foreach ($paths as $path) {
            try {
                return $this->decryptWithKey($envKey, $data, $iv, $path, $cipher);
            } catch (\Throwable $e) {
                $last = $e;
            }
        }

        throw $last ?: new RuntimeException('Decriptarea IPN Netopia a eșuat.');
    }

    private function decryptWithKey(
        string $envKey,
        string $data,
        ?string $iv,
        string $privateKeyPath,
        ?string $cipher = null,
    ): string {
        $privateKey = openssl_pkey_get_private(file_get_contents($privateKeyPath));
        if ($privateKey === false) {
            throw new RuntimeException('Cheia privată Netopia este invalidă.');
        }

        $cipherAlgo = trim((string) ($cipher ?: 'aes-256-cbc'));
        if ($cipherAlgo === '') {
            $cipherAlgo = 'aes-256-cbc';
        }
        $methods = openssl_get_cipher_methods();
        if (! in_array($cipherAlgo, $methods, true) && in_array(strtoupper($cipherAlgo), $methods, true)) {
            $cipherAlgo = strtoupper($cipherAlgo);
        }

        $src = base64_decode($data, true);
        $key = base64_decode($envKey, true);
        if ($src === false || $key === false) {
            throw new RuntimeException('Payload Netopia invalid.');
        }

        // IV: niciodată null către openssl_open (PHP 8 + AES cere string).
        $ivBin = '';
        if ($iv !== null && $iv !== '') {
            $decoded = base64_decode($iv, true);
            if ($decoded === false) {
                throw new RuntimeException('IV Netopia invalid.');
            }
            $ivBin = $decoded;
        }

        $open = '';
        $ok = @openssl_open($src, $open, $key, $privateKey, $cipherAlgo, $ivBin);

        // IPN vechi fără IV / RC4 (OpenSSL < 3).
        if (! $ok && $ivBin === '' && strcasecmp($cipherAlgo, 'rc4') !== 0) {
            foreach (['rc4', 'RC4'] as $legacy) {
                if (! in_array($legacy, $methods, true)) {
                    continue;
                }
                $ok = @openssl_open($src, $open, $key, $privateKey, $legacy, '');
                if ($ok) {
                    break;
                }
            }
        }

        if (! $ok) {
            throw new RuntimeException('Decriptarea IPN Netopia a eșuat.');
        }

        return $open;
    }
}
