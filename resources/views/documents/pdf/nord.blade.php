@php($color = $document->company->invoiceColor())
@php($logo = $document->company->logoDataUri())
@php($typeLabel = ($labels['type_'.$document->type] ?? null) ?: $document->typeLabel($locale ?? 'ro'))
<!DOCTYPE html>
<html lang="{{ $locale ?? 'ro' }}">
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #243b53; margin: 0; }
.pad { padding: 22px 24px; }
.header { width: 100%; margin-bottom: 28px; }
.header td { border: none; vertical-align: top; padding: 0; }
h1 { margin: 0; font-size: 26px; font-weight: normal; color: {{ $color }}; letter-spacing: -0.5px; text-align: right; }
.num { text-align: right; color: #829ab1; font-size: 11px; margin-top: 4px; }
.muted { color: #829ab1; }
.parties { width: 100%; margin-bottom: 8px; }
.parties td { border: none; vertical-align: top; width: 50%; padding: 0 16px 0 0; }
.label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #829ab1; margin-bottom: 8px; }
.rule { border: none; border-top: 1px solid #d9e2ec; margin: 16px 0; }
table.items { width: 100%; border-collapse: collapse; margin-top: 4px; }
table.items th { text-align: left; padding: 8px 4px; font-size: 9px; text-transform: uppercase; letter-spacing: .6px; font-weight: normal; color: #829ab1; border-bottom: 1px solid #d9e2ec; background: transparent; }
table.items td { padding: 10px 4px; border-bottom: 1px solid #f0f4f8; }
.totals .grand { font-size: 15px; color: {{ $color }}; font-weight: bold; }
@include('documents.pdf.partials.page-bottom-styles')
.pdf-bottom { padding: 0 24px 12px; }
</style>
</head>
<body>
@include('documents.pdf.partials.pdf-sheet-open')
<div class="pad">
<table class="header">
<tr>
<td style="width:40%">
    @include('documents.pdf.partials.logo-img')
    <div class="muted" style="margin-top:10px;font-size:10px">@include('documents.pdf.partials.company-block')</div>
</td>
<td>
    <h1>{{ $typeLabel }}</h1>
    <div class="num"><strong>{{ $document->number_full ?: ($labels['draft'] ?? 'Draft') }}</strong></div>
    <div class="num">{{ $labels['date'] ?? 'Data' }} {{ dc_date($document->issue_date) }}@if($document->due_date) · {{ $labels['due_date'] ?? 'Scadență' }} {{ dc_date($document->due_date) }}@endif</div>
</td>
</tr>
</table>
<hr class="rule">
<table class="parties">
<tr>
<td>
    <div class="label">{{ $labels['client'] ?? 'Client' }}</div>
    @include('documents.pdf.partials.client-block')
</td>
<td></td>
</tr>
</table>
@include('documents.pdf.partials.items-table')
</div>
@include('documents.pdf.partials.page-bottom')
</body>
</html>
