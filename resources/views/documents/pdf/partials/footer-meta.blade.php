@php
    $preparedBy = trim((string) ($document->prepared_by ?: $document->company?->seriesResponsibleName() ?: ''));
    $rows = array_filter([
        'Întocmit de' => trim(implode(' · ', array_filter([
            $preparedBy !== '' ? $preparedBy : null,
            $document->prepared_by_cnp ? 'CNP '.$document->prepared_by_cnp : null,
        ]))),
        'Delegat' => trim(implode(' · ', array_filter([
            $document->delegate_name,
            $document->delegate_id_card ? 'CI '.$document->delegate_id_card : null,
        ]))),
        'Auto' => $document->vehicle_reg,
        'Contract (BT-12)' => $document->contract_number,
        'Aviz însoțire (BT-16)' => $document->despatch_advice,
    ]);
    $cardPaymentLinks = $cardPaymentLinks ?? [];
    $cardPaymentHubUrl = $cardPaymentHubUrl ?? null;
    $showCard = $document->allow_card_payment && ($cardPaymentLinks !== [] || filled($cardPaymentHubUrl));
@endphp
@if($rows !== [] || $showCard)
<div class="footer-meta">
    @foreach($rows as $label => $value)
        <div><strong>{{ $label }}:</strong> {{ $value }}</div>
    @endforeach
    @if($showCard)
        <div style="margin-top:4px;">
            <strong>Plată cu cardul online:</strong>
            @if($cardPaymentLinks !== [])
                @foreach($cardPaymentLinks as $i => $link)
                    @if($i > 0) · @endif
                    <a href="{{ $link['url'] }}" style="color:#0f766e; text-decoration:underline;">{{ $link['short'] }}</a>
                @endforeach
            @elseif(filled($cardPaymentHubUrl))
                <a href="{{ $cardPaymentHubUrl }}" style="color:#0f766e; text-decoration:underline;">{{ $cardPaymentHubUrl }}</a>
            @endif
        </div>
        @if(filled($cardPaymentHubUrl) && count($cardPaymentLinks) > 1)
            <div style="font-size:9pt;color:#64748b;">
                Sau alege procesatorul: <a href="{{ $cardPaymentHubUrl }}" style="color:#0f766e;">pagina de plată</a>
            </div>
        @endif
    @endif
</div>
@endif
