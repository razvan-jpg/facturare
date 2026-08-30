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
.rail { width: 28%; background: #f0f4f8; border-right: 3px solid {{ $color }}; padding: 18px 12px; }
.main { width: 72%; padding: 18px 16px; }
.rail-label { font-size: 8px; text-transform: uppercase; letter-spacing: 1px; color: #627d98; margin: 12px 0 3px; }
.rail-val { font-size: 11px; font-weight: bold; color: #102a43; }
h1 { margin: 0 0 4px; font-size: 22px; color: {{ $color }}; }
.muted { color: #627d98; font-size: 10px; }
.label { font-size: 9px; text-transform: uppercase; letter-spacing: .8px; color: {{ $color }}; margin-bottom: 6px; }
table.items { width: 100%; border-collapse: collapse; margin-top: 12px; }
table.items th { text-align: left; padding: 7px 5px; font-size: 9px; text-transform: uppercase; border-bottom: 2px solid {{ $color }}; background: transparent; color: #486581; }
table.items td { padding: 7px 5px; border-bottom: 1px solid #e2e8f0; }
.totals .grand { font-size: 16px; color: {{ $color }}; }
.rail-total { margin-top: 24px; padding-top: 12px; border-top: 2px solid {{ $color }}; }
.rail-total .amt { font-size: 16px; font-weight: bold; color: {{ $color }}; }
@include('documents.pdf.partials.page-bottom-styles')
</style>
</head>
<body>
<table class="layout">
<tr>
<td class="rail">
    @include('documents.pdf.partials.logo-img')
    <div style="height:8px"></div>
    <div class="rail-label">{{ $labels['document'] ?? 'Document' }}</div>
    <div class="rail-val">{{ $typeLabel }}</div>
    <div class="rail-label">{{ $labels['number'] ?? 'Număr' }}</div>
    <div class="rail-val">{{ $document->number_full ?: ($labels['draft'] ?? 'Draft') }}</div>
    <div class="rail-label">{{ $labels['date'] ?? 'Data' }}</div>
    <div class="rail-val">{{ dc_date($document->issue_date) }}</div>
    @if($document->due_date)
        <div class="rail-label">{{ $labels['due_date'] ?? 'Scadență' }}</div>
        <div class="rail-val">{{ dc_date($document->due_date) }}</div>
    @endif
    <div class="rail-label" style="margin-top:18px">{{ $labels['supplier'] ?? 'Furnizor' }}</div>
    <div style="font-size:10px">@include('documents.pdf.partials.company-block')</div>
    <div class="rail-total">
        <div class="rail-label">{{ $labels['total'] ?? 'Total' }}</div>
        <div class="amt">{{ number_format((float) $document->total, 2, ',', '.') }} {{ $document->currency }}</div>
        @include('documents.pdf.partials.ron-equivalent', ['style' => 'margin-top:6px;font-size:9px;color:#64748b;line-height:1.35;'])
    </div>
</td>
<td class="main">
@include('documents.pdf.partials.pdf-sheet-open')
    <h1>{{ $typeLabel }}</h1>
    <div class="muted" style="margin-bottom:14px">{{ $document->number_full ?: ($labels['draft'] ?? 'Draft') }}</div>
    <div class="label">{{ $labels['client'] ?? 'Client' }}</div>
    @include('documents.pdf.partials.client-block')
    @include('documents.pdf.partials.items-table')
@include('documents.pdf.partials.page-bottom')
</td>
</tr>
</table>
</body>
</html>
