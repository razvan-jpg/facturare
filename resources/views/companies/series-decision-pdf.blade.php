<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #102a43; line-height: 1.45; }
h1 { font-size: 16px; text-align: center; margin: 0 0 6px; text-transform: uppercase; }
h2 { font-size: 13px; text-align: center; margin: 0 0 18px; font-weight: normal; }
.meta { margin-bottom: 16px; }
.meta strong { display: inline-block; min-width: 90px; }
p { margin: 0 0 10px; text-align: justify; }
table { width: 100%; border-collapse: collapse; margin: 16px 0; }
th, td { border: 1px solid #334e68; padding: 7px 8px; vertical-align: top; }
th { background: #f0f4f8; font-size: 10px; text-transform: uppercase; text-align: left; }
.center { text-align: center; }
.sign { margin-top: 40px; width: 100%; }
.sign td { border: none; width: 50%; vertical-align: top; }
.muted { color: #627d98; font-size: 10px; }
.footer { margin-top: 28px; font-size: 9px; color: #829ab1; }
</style>
</head>
<body>
<h1>Decizie de inseriere</h1>
<h2>privind seriile și numerotarea documentelor financiar-contabile<br>pentru exercițiul financiar {{ $year }}</h2>

<div class="meta">
    <div><strong>Societatea:</strong> {{ $company->name }}</div>
    <div><strong>CUI:</strong> {{ $company->cui }}@if($company->reg_com) · Reg. Com. {{ $company->reg_com }}@endif</div>
    <div><strong>Sediu:</strong> {{ $company->fullAddress() }}</div>
    <div><strong>Data:</strong> {{ dc_date($decisionDate) }}</div>
</div>

<p>
În conformitate cu Legea contabilității nr. 82/1991, republicată, și cu Ordinul MFP nr. 2634/2015
privind documentele financiar-contabile, societatea stabilește prin prezenta decizie regimul intern
de numerotare a documentelor, pe serii și numere de ordine secvențiale, pentru anul {{ $year }}.
</p>

<p>
Se desemnează ca persoană cu atribuții privind alocarea și gestionarea numerelor aferente documentelor
financiar-contabile: <strong>{{ $responsibleName }}</strong>@if(!empty($responsibleRole)), în calitate de {{ $responsibleRole }}@endif.
</p>

<p>
Pentru exercițiul financiar <strong>{{ $year }}</strong>, se alocă următoarele serii și plaje de numere:
</p>

<table>
    <thead>
    <tr>
        <th class="center" style="width:40px">Nr.</th>
        <th>{{ __('Tip document') }}</th>
        <th>{{ __('Serie') }}</th>
        <th>An</th>
        <th>De la nr.</th>
        <th>Până la nr.</th>
    </tr>
    </thead>
    <tbody>
    @foreach($series as $i => $row)
        @php
            $from = max(1, (int) $row->next_number);
            $to = max($from, $from + 9999);
        @endphp
        <tr>
            <td class="center">{{ $i + 1 }}</td>
            <td>{{ $row->typeLabel() }}</td>
            <td><strong>{{ $row->prefix }}</strong></td>
            <td class="center">{{ $row->year }}</td>
            <td class="center">{{ str_pad((string) $from, 4, '0', STR_PAD_LEFT) }}</td>
            <td class="center">{{ str_pad((string) $to, 4, '0', STR_PAD_LEFT) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<p>
Numerotarea este cronologică și secvențială în cadrul fiecărei serii. În cazul epuizării plajei alocate,
se va emite o nouă decizie de alocare a unei plaje suplimentare, cu aprobarea administratorului.
</p>

<p class="muted">
Formatul numărului complet pe documentele emise în aplicație: SERIE-AN-NUMĂR (ex. FCT-{{ $year }}-0001).
</p>

<table class="sign">
    <tr>
        <td>
            <div><strong>Întocmit / Responsabil numerotare</strong></div>
            <div style="margin-top:36px">.................................</div>
            <div>{{ $responsibleName }}@if(!empty($responsibleRole))<br><span class="muted">{{ $responsibleRole }}</span>@endif</div>
        </td>
        <td style="text-align:right">
            <div><strong>Administrator / Conducere</strong></div>
            <div style="margin-top:36px">.................................</div>
            <div>{{ $company->name }}</div>
        </td>
    </tr>
</table>

<p class="footer"><a href="{{ rtrim((string) config('app.url'), '/') }}/" class="footer-brand-link" style="color:inherit;text-decoration:underline">Document generat cu DateConta Facturare</a> · {{ dc_datetime($decisionDate) }}</p>
</body>
</html>
