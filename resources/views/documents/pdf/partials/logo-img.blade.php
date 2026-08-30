@php
    /** Doar logo-ul firmei — dimensiunea e controlată din Personalizare (+/−), nu afectează restul facturii. */
    $logoSrc = $logo ?? $document->company->logoDataUri();
    $logoSize = $document->company->brandingDisplaySize('logo');
@endphp
@if($logoSrc)
    <img src="{{ $logoSrc }}"
         alt="Logo"
         width="{{ $logoSize['w'] }}"
         height="{{ $logoSize['h'] }}"
         style="width:{{ $logoSize['w'] }}px;height:{{ $logoSize['h'] }}px;max-width:{{ $logoSize['w'] }}px;max-height:{{ $logoSize['h'] }}px;border:0;display:inline-block;vertical-align:top;">
@endif
