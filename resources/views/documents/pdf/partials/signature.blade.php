@php
    $t = $labels ?? [];
    $company = $document->company;
    $sig = $company->signatureDataUri();
    $stamp = $company->stampDataUri();
    $sigSize = $company->brandingDisplaySize('signature');
    $stampSize = $company->brandingDisplaySize('stamp');
    $sigLines = $company->signatureTextLines();
    $hasSigText = $sigLines !== [];
    $sigLabel = $t['signature'] ?? 'Semnătură';
    $stampLabel = $t['stamp'] ?? 'Ștampilă';

    // First Consulting: imaginea (ștampilă/semnătură) peste eticheta „ȘTAMPILĂ”.
    $isFirstConsulting = $company->numericCui() === '40094365';
    $stampOverLabel = false;
    $stampImg = $stamp;
    $sigImg = $sig;
    if ($isFirstConsulting) {
        $stampImg = $stamp ?: $sig;
        // Evită dublarea aceleiași imagini sub SEMNĂTURĂ când e mutată pe ȘTAMPILĂ.
        if (! $stamp && $sig) {
            $sigImg = null;
        }
        $stampOverLabel = (bool) $stampImg;
        if ($stampOverLabel && ! $stamp && $sig) {
            // Folosește cutia de ștampilă (mai potrivită pentru cerc), păstrând scala imaginii încărcate.
            $stampSize = $company->brandingDisplaySize('stamp');
        }
    }
@endphp
@if($sigImg || $stampImg || $hasSigText || $stamp)
<table class="sign-table">
<tr>
    <td class="sign-cell" style="text-align:left; vertical-align:bottom;">
        <div class="sign-stack">
            <div class="sign-block">
                <div class="sign-label">{{ $sigLabel }}</div>
                @if($sigImg)
                    <div class="sign-media" style="width:{{ $sigSize['w'] }}px;">
                        <img src="{{ $sigImg }}"
                             alt="{{ $sigLabel }}"
                             width="{{ $sigSize['w'] }}"
                             height="{{ $sigSize['h'] }}"
                             style="width:{{ $sigSize['w'] }}px;height:{{ $sigSize['h'] }}px;max-width:{{ $sigSize['w'] }}px;max-height:{{ $sigSize['h'] }}px;border:0;display:block;margin:0 auto;">
                        <div class="sign-line"></div>
                    </div>
                @elseif($hasSigText)
                    <div class="sign-text-lines">
                        @foreach($sigLines as $line)
                            <div class="sign-line-row">{{ $line }}</div>
                        @endforeach
                    </div>
                @else
                    <div class="sign-media" style="width:150px;">
                        <div class="sign-line"></div>
                    </div>
                @endif
            </div>

            <div class="sign-block">
                @if($stampOverLabel && $stampImg)
                    {{-- Imagine peste textul „ȘTAMPILĂ” (First Consulting) --}}
                    <div class="sign-stamp-over"
                         style="position:relative;width:{{ max($stampSize['w'], 90) }}px;height:{{ max($stampSize['h'], 48) }}px;margin:0;">
                        <div class="sign-label sign-label-under-stamp"
                             style="position:absolute;left:0;right:0;top:50%;margin-top:-7px;text-align:center;z-index:0;">
                            {{ $stampLabel }}
                        </div>
                        <img src="{{ $stampImg }}"
                             alt="{{ $stampLabel }}"
                             width="{{ $stampSize['w'] }}"
                             height="{{ $stampSize['h'] }}"
                             style="position:relative;z-index:1;width:{{ $stampSize['w'] }}px;height:{{ $stampSize['h'] }}px;max-width:{{ $stampSize['w'] }}px;max-height:{{ $stampSize['h'] }}px;border:0;display:block;margin:0 auto;">
                    </div>
                    <div class="sign-media" style="width:{{ max($stampSize['w'], 90) }}px;">
                        <div class="sign-line"></div>
                    </div>
                @else
                    <div class="sign-label">{{ $stampLabel }}</div>
                    @if($stampImg)
                        <div class="sign-media" style="width:{{ $stampSize['w'] }}px;">
                            <img src="{{ $stampImg }}"
                                 alt="{{ $stampLabel }}"
                                 width="{{ $stampSize['w'] }}"
                                 height="{{ $stampSize['h'] }}"
                                 style="width:{{ $stampSize['w'] }}px;height:{{ $stampSize['h'] }}px;max-width:{{ $stampSize['w'] }}px;max-height:{{ $stampSize['h'] }}px;border:0;display:block;margin:0 auto;">
                            <div class="sign-line"></div>
                        </div>
                    @else
                        <div class="sign-media" style="width:150px;">
                            <div class="sign-line"></div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </td>
</tr>
</table>
@endif
