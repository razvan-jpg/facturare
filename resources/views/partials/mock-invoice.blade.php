<div class="dc-mock dc-mock-invoice" aria-hidden="true">
    <div class="dc-mock-bar">
        <span></span><span></span><span></span>
        <em>{{ __('Factură · FCT-2026-0142') }}</em>
    </div>
    <div class="dc-mock-body">
        <div class="dc-mock-row between">
            <div style="display:flex;align-items:center;gap:8px;">
                <img src="{{ asset('images/brand/dateconta-icon.png') }}" alt="" width="28" height="28" style="border-radius:8px;object-fit:cover;">
                <div>
                    <strong class="dc-mock-brand">DateConta</strong>
                    <small>{{ __('Facturare') }}</small>
                </div>
            </div>
            <div class="right">
                <b>Client Demo SRL</b>
                <small>CUI 12345678</small>
            </div>
        </div>
        <div class="dc-mock-lines">
            <div><span>{{ __('Consultanță fiscală') }}</span><span>1.800,00</span></div>
            <div><span>{{ __('Implementare proceduri') }}</span><span>950,00</span></div>
            <div><span>{{ __('TVA 21%') }}</span><span>577,50</span></div>
        </div>
        <div class="dc-mock-total">
            <span>{{ __('Total de plată') }}</span>
            <strong>3.327,50 RON</strong>
        </div>
    </div>
</div>
