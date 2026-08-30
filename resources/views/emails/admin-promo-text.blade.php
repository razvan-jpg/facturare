{{ __('Îți recomand călduros DateConta Facturare') }}

{{ __('Salut,') }}

{{ __('Sunt :sender de la :company. Îți recomand călduros DateConta Facturare — facturi, proforme, avize, chitanțe, e-Factura ANAF.', [
    'sender' => $senderName,
    'company' => $companyName,
]) }}

{{ __('Platforma e gratuită până la :date. După aceea: planuri de la 1,99 EUR/lună + TVA.', [
    'date' => \Illuminate\Support\Carbon::parse($promoFreeUntil)->format('d.m.Y'),
]) }}

{{ __('Creează cont:') }} {{ $registerUrl }}

{{ __('Cu drag,') }}
{{ $senderName }}
{{ $companyName }}
