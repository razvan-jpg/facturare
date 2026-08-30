@php $fmt = fn ($n) => number_format((float) $n, 2, ',', '.'); @endphp

<div class="company">
    <strong>Societate: {{ $company->name }}</strong><br>
    CIF: {{ $company->cui }}<br>
    Adresa: {{ $company->fullAddress() }}<br>
    @if($company->reg_com)Nr. reg. com. {{ $company->reg_com }}@endif
</div>

<div class="title">BALANTA TERTI</div>
<div class="period">Perioada de la {{ dc_date($from) }} la {{ dc_date($to) }}</div>
<div class="currency">- {{ $currency }} -</div>

<table class="bal">
    <thead>
    <tr>
        <th rowspan="2" style="width:6%">Simbol cont</th>
        <th rowspan="2" style="width:22%">Denumire partener</th>
        <th rowspan="2" style="width:7%">TVA la<br>incasare</th>
        <th colspan="2">Rulaje precedente</th>
        <th colspan="2">Rulaje curente</th>
        <th colspan="2">Total sume</th>
        <th colspan="2">Solduri finale</th>
    </tr>
    <tr>
        <th>Debitoare</th>
        <th>{{ __('Creditoare') }}</th>
        <th>Debitoare</th>
        <th>{{ __('Creditoare') }}</th>
        <th>Debitoare</th>
        <th>{{ __('Creditoare') }}</th>
        <th>Debitoare</th>
        <th>{{ __('Creditoare') }}</th>
    </tr>
    </thead>
    <tbody>
    @foreach($rows as $row)
        <tr class="{{ ! empty($row['is_account_header']) ? 'header' : (! empty($row['is_total']) ? 'total' : '') }}">
            <td>{{ $row['account'] }}</td>
            <td class="name">{{ $row['name'] }}</td>
            <td style="text-align:center">{{ $row['vat_collection'] }}</td>
            <td class="num">{{ $fmt($row['prior_debit']) }}</td>
            <td class="num">{{ $fmt($row['prior_credit']) }}</td>
            <td class="num">{{ $fmt($row['period_debit']) }}</td>
            <td class="num">{{ $fmt($row['period_credit']) }}</td>
            <td class="num">{{ $fmt($row['total_debit']) }}</td>
            <td class="num">{{ $fmt($row['total_credit']) }}</td>
            <td class="num">{{ $fmt($row['final_debit']) }}</td>
            <td class="num">{{ $fmt($row['final_credit']) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table class="sign">
    <tr>
        <td>Intocmit,</td>
        <td>Conducatorul compartimentului financiar-contabil,</td>
        <td>Director,</td>
    </tr>
</table>

<div class="footer">
    <a href="{{ rtrim((string) config('app.url'), '/') }}/" class="footer-brand-link" style="color:inherit;text-decoration:underline">Document generat cu DateConta Facturare</a> · {{ dc_datetime(now()) }} · Cont {{ $account }}-Clienți
</div>
