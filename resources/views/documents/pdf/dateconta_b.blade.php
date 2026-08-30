@php
    $teal = '#0f4c5c';
    $amber = '#e08a1e';
    $ink = '#102a43';
    $muted = '#627d98';
    $typeLabel = ($labels['type_'.$document->type] ?? null) ?: $document->typeLabel($locale ?? 'ro');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale ?? 'ro' }}">
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: {{ $ink }}; margin: 0; }
.layout { width: 100%; border-collapse: collapse; }
.layout > tbody > tr > td { border: none; vertical-align: top; padding: 0; }
.rail {
    width: 28%;
    background: {{ $teal }};
    color: #fff;
    padding: 20px 14px;
}
.rail * { color: #fff; }
.rail .pill {
    display: inline-block;
    background: {{ $amber }};
    color: #fff;
    font-size: 9px;
    font-weight: bold;
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 3px 7px;
    margin-bottom: 14px;
}
.rail h1 { margin: 0 0 6px; font-size: 18px; color: #fff; line-height: 1.2; }
.rail .num { font-size: 12px; opacity: .95; margin-bottom: 16px; }
.rail .label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: .75;
    margin: 12px 0 3px;
    color: #fff;
}
.rail .total-box {
    margin-top: 22px;
    background: rgba(255,255,255,.1);
    border-left: 3px solid {{ $amber }};
    padding: 10px 10px;
}
.rail .total-box .t-label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; opacity: .85; color: #fff; }
.rail .total-box .t-amt { font-size: 18px; font-weight: bold; color: #fff; margin-top: 4px; }
.rail .total-box .t-amt span { color: {{ $amber }}; }
.content { width: 72%; padding: 18px 16px 10px; }
.top-meta { width: 100%; margin-bottom: 12px; }
.top-meta td { border: none; vertical-align: top; padding: 0; }
.logo-cell { text-align: right; }
.client-card {
    border: 1px solid #d9e2ec;
    border-left: 4px solid {{ $amber }};
    background: #fffaf3;
    padding: 10px 12px;
    margin-bottom: 12px;
}
.client-card .label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: {{ $muted }};
    font-weight: bold;
    margin-bottom: 5px;
}
.supplier-inline {
    font-size: 10px;
    color: {{ $muted }};
    line-height: 1.35;
    margin-bottom: 10px;
}
.supplier-inline strong { color: {{ $teal }}; font-size: 11px; }
table.items { width: 100%; border-collapse: collapse; margin-top: 4px; }
table.items th {
    text-align: left;
    padding: 7px 5px;
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: {{ $teal }};
    background: #f0f4f8;
    border-bottom: 2px solid {{ $teal }};
}
table.items td { padding: 7px 5px; border-bottom: 1px solid #e2e8f0; }
.totals .grand { font-size: 15px; color: {{ $teal }}; }
.totals .grand strong { color: {{ $amber }}; }
@include('documents.pdf.partials.page-bottom-styles')
.pdf-bottom { padding: 0; }
.footer { color: {{ $muted }}; }
</style>
</head>
<body>
<table class="layout">
<tr>
<td class="rail">
    <div class="pill">DateConta</div>
    <h1>{{ $typeLabel }}</h1>
    <div class="num">{{ $document->number_full ?: ($labels['draft'] ?? 'Draft') }}</div>
    <div class="label">{{ $labels['date'] ?? 'Data' }}</div>
    <div>{{ dc_date($document->issue_date) }}</div>
    @if($document->due_date)
        <div class="label">{{ $labels['due_date'] ?? 'Scadență' }}</div>
        <div>{{ dc_date($document->due_date) }}</div>
    @endif
    <div class="total-box">
        <div class="t-label">{{ $labels['total'] ?? 'Total de plată' }}</div>
        <div class="t-amt"><span>{{ number_format((float) $document->total, 2, ',', '.') }}</span> {{ $document->currency }}</div>
        @include('documents.pdf.partials.ron-equivalent', ['style' => 'margin-top:6px;font-size:9px;color:#cbd5e1;line-height:1.35;'])
    </div>
</td>
<td class="content">
@include('documents.pdf.partials.pdf-sheet-open')
    <table class="top-meta">
        <tr>
            <td>
                <div class="supplier-inline">
                    <strong>{{ $labels['supplier'] ?? 'Furnizor' }}</strong><br>
                    @include('documents.pdf.partials.company-block')
                </div>
            </td>
            <td class="logo-cell">@include('documents.pdf.partials.logo-img')</td>
        </tr>
    </table>
    <div class="client-card">
        <div class="label">{{ $labels['client'] ?? 'Client' }}</div>
        @include('documents.pdf.partials.client-block')
    </div>
    @include('documents.pdf.partials.items-table')
@include('documents.pdf.partials.page-bottom')
</td>
</tr>
</table>
</body>
</html>
