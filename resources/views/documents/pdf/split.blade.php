@php($color = $document->company->invoiceColor())
@php($typeLabel = ($labels['type_'.$document->type] ?? null) ?: $document->typeLabel($locale ?? 'ro'))
<!DOCTYPE html>
<html lang="{{ $locale ?? 'ro' }}">
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #102a43; margin: 0; }
.layout { width: 100%; }
.layout > tbody > tr > td { border: none; vertical-align: top; padding: 0; }
.brand { width: 36%; background: {{ $color }}; color: #fff; padding: 20px 14px; }
.brand * { color: #fff; }
.brand .logo-wrap { background: #fff; display: inline-block; padding: 6px; margin-bottom: 14px; }
.brand h1 { margin: 0 0 8px; font-size: 20px; color: #fff; }
.brand .num { font-size: 12px; opacity: .95; margin-bottom: 14px; }
.brand .label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; opacity: .8; margin: 12px 0 4px; color: #fff; }
.brand .total-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1px; opacity: .85; margin-top: 28px; color: #fff; }
.brand .total-amt { font-size: 22px; font-weight: bold; color: #fff; margin-top: 4px; }
.content { width: 64%; padding: 18px 16px; }
.label { font-size: 9px; text-transform: uppercase; color: {{ $color }}; margin-bottom: 6px; font-weight: bold; }
table.items { width: 100%; border-collapse: collapse; margin-top: 12px; }
table.items th { text-align: left; padding: 7px 5px; font-size: 9px; text-transform: uppercase; background: #f0f4f8; border-bottom: 2px solid {{ $color }}; }
table.items td { padding: 7px 5px; border-bottom: 1px solid #e2e8f0; }
.totals .grand { font-size: 15px; color: {{ $color }}; }
@include('documents.pdf.partials.page-bottom-styles')
</style>
</head>
<body>
<table class="layout">
<tr>
<td class="brand">
    <div class="logo-wrap">@include('documents.pdf.partials.logo-img')</div>
    <h1>{{ $typeLabel }}</h1>
    <div class="num">{{ $document->number_full ?: ($labels['draft'] ?? 'Draft') }}</div>
    <div class="label">{{ $labels['date'] ?? 'Data' }}</div>
    <div>{{ dc_date($document->issue_date) }}</div>
    @if($document->due_date)
        <div class="label">{{ $labels['due_date'] ?? 'Scadență' }}</div>
        <div>{{ dc_date($document->due_date) }}</div>
    @endif
    <div class="label">{{ $labels['supplier'] ?? 'Furnizor' }}</div>
    <div style="font-size:10px;line-height:1.35">@include('documents.pdf.partials.company-block')</div>
    <div class="total-label">{{ $labels['total'] ?? 'Total de plată' }}</div>
    <div class="total-amt">{{ number_format((float) $document->total, 2, ',', '.') }} {{ $document->currency }}</div>
    @include('documents.pdf.partials.ron-equivalent', ['style' => 'margin-top:6px;font-size:9px;color:#94a3b8;line-height:1.35;'])
</td>
<td class="content">
@include('documents.pdf.partials.pdf-sheet-open')
    <div class="label">{{ $labels['client'] ?? 'Client' }}</div>
    @include('documents.pdf.partials.client-block')
    @include('documents.pdf.partials.items-table')
@include('documents.pdf.partials.page-bottom')
</td>
</tr>
</table>
</body>
</html>
