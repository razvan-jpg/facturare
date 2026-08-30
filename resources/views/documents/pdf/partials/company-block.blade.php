@php
    $t = $labels ?? [];
    $company = $document->company;
    $showCui = $company->preference('show_cui_on_docs', true) !== false;
    $showRegCom = $company->preference('show_reg_com_on_docs', true) !== false;
    $showBank = $company->preference('show_bank_on_docs', true) !== false;
    $invoiceAccounts = $showBank ? $company->invoiceBankAccounts() : collect();
@endphp
<strong>{{ $company->name }}</strong><br>
@if($showCui)
    {{ $t['cui'] ?? 'CUI' }} {{ $company->cui }}{{ ($showRegCom && $company->reg_com) ? ' · '.$company->reg_com : '' }}<br>
@endif
{{ $company->fullAddress() }}<br>
@if($showBank)
    @forelse($invoiceAccounts as $account)
        @php
            $bankLabel = optional($account->bank)->name;
            $currencySuffix = ($account->currency && $account->currency !== 'RON') ? ' ('.$account->currency.')' : '';
        @endphp
        {{ $t['iban'] ?? 'IBAN' }} {{ $account->iban }}{{ $bankLabel ? ' · '.$bankLabel : '' }}{{ $currencySuffix }}<br>
    @empty
        @if($company->iban)
            {{ $t['iban'] ?? 'IBAN' }} {{ $company->iban }}{{ $company->bank_name ? ' · '.$company->bank_name : '' }}
        @endif
    @endforelse
@endif
