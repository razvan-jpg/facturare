@php
    $fmt = fn ($n) => number_format((float) $n, 2, ',', '.');
    $t = $totals;
@endphp

<div class="company">
    <strong>Societate: {{ $company->name }}</strong><br>
    CIF: {{ $company->cui }}<br>
    Adresa: {{ $company->fullAddress() }}<br>
    @if($company->reg_com)Nr. reg. com. {{ $company->reg_com }}@endif
</div>

<div class="title">FISA DE PARTENER</div>
<div class="period">Perioada de la {{ dc_date($from) }} la {{ dc_date($to) }}</div>
<div class="currency">- {{ $currency }} -</div>

<table class="meta">
    <tr>
        <td class="lbl">Partener:</td>
        <td><strong>{{ $client->name }}</strong>
            @if($client->cui || $client->cnp)
                · {{ $client->cui ? 'CUI '.$client->cui : 'CNP '.$client->cnp }}
            @endif
        </td>
    </tr>
    <tr>
        <td class="lbl">Cont:</td>
        <td>{{ $account }}</td>
    </tr>
</table>

<table class="ledger">
    <thead>
    <tr>
        <th style="width:14%">Punct de lucru</th>
        <th style="width:7%" class="center">Tip doc</th>
        <th style="width:10%" class="center">Data</th>
        <th style="width:23%">Nr document</th>
        <th style="width:15%" class="num">Valoare debit</th>
        <th style="width:15%" class="num">Valoare credit</th>
        <th style="width:16%" class="num">Sold</th>
    </tr>
    </thead>
    <tbody>
    @foreach($lines as $line)
        <tr class="{{ ! empty($line['is_opening']) ? 'opening' : (! empty($line['is_total']) ? 'total' : '') }}">
            <td>{{ $line['branch'] }}</td>
            <td class="center">{{ $line['tip'] }}</td>
            <td class="center">{{ $line['date'] ? dc_date($line['date']) : '' }}</td>
            <td>{{ $line['number'] }}</td>
            <td class="num">{{ $fmt($line['debit']) }}</td>
            <td class="num">{{ $fmt($line['credit']) }}</td>
            <td class="num">{{ $fmt($line['sold']) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="totals-wrap">
    <table>
        <thead>
        <tr>
            <th>Totaluri generale</th>
            <th class="num">Suma debit</th>
            <th class="num">Suma credit</th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>Totaluri solduri initiale</td>
            <td class="num">{{ $fmt($t['opening_debit']) }}</td>
            <td class="num">{{ $fmt($t['opening_credit']) }}</td>
        </tr>
        <tr>
            <td>Total rulaje precedente</td>
            <td class="num">{{ $fmt($t['prior_debit']) }}</td>
            <td class="num">{{ $fmt($t['prior_credit']) }}</td>
        </tr>
        <tr>
            <td>Total rulaje perioada</td>
            <td class="num">{{ $fmt($t['period_debit']) }}</td>
            <td class="num">{{ $fmt($t['period_credit']) }}</td>
        </tr>
        <tr>
            <td><strong>Total sume</strong></td>
            <td class="num"><strong>{{ $fmt($t['total_debit']) }}</strong></td>
            <td class="num"><strong>{{ $fmt($t['total_credit']) }}</strong></td>
        </tr>
        <tr>
            <td>Totaluri solduri finale</td>
            <td class="num">{{ $fmt($t['final_debit']) }}</td>
            <td class="num">{{ $fmt($t['final_credit']) }}</td>
        </tr>
        </tbody>
    </table>
</div>

<div class="footer">
    <a href="{{ rtrim((string) config('app.url'), '/') }}/" class="footer-brand-link" style="color:inherit;text-decoration:underline">Document generat cu DateConta Facturare</a> · {{ dc_datetime(now()) }}
</div>
