{{ $bodyText }}

@if(! empty($documentLink))
{{ __('Vizualizează documentul') }}: {{ $documentLink }}
@endif

@php
    $paymentLinks = $paymentLinks ?? [];
    $paymentHubUrl = $paymentHubUrl ?? null;
@endphp
@if($paymentLinks !== [] || filled($paymentHubUrl))

{{ __('Plată cu cardul online') }}:
@foreach($paymentLinks as $link)
- {{ $link['short'] }}: {{ $link['url'] }}
@endforeach
@if(filled($paymentHubUrl))
{{ __('Pagina de plată') }}: {{ $paymentHubUrl }}
@endif
@endif

---
{{ __('Fișierul PDF este atașat.') }}
{{ config('dateconta.brand_name', 'DateConta Facturare') }}
{{ config('app.url') }}
