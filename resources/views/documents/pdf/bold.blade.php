@php($color = $document->company->invoiceColor())
@php($logo = $document->company->logoDataUri())
<!DOCTYPE html>
<html lang="{{ $locale ?? 'ro' }}">
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #102a43; margin: 0; }
.banner { background: {{ $color }}; color: #fff; padding: 22px 20px 18px; }
.banner table { width: 100%; }
.banner td { border: none; vertical-align: middle; color: #fff; padding: 0; }
.banner h1 { margin: 0; font-size: 26px; color: #fff; letter-spacing: .5px; }
.banner .num { font-size: 14px; margin-top: 6px; opacity: .95; }
.wrap { padding: 18px 20px; }
.parties { width: 100%; margin-bottom: 8px; }
.parties td { border: none; vertical-align: top; width: 50%; padding: 0 10px 0 0; }
.label { font-size: 10px; font-weight: bold; text-transform: uppercase; color: {{ $color }}; margin-bottom: 6px; }
table.items { width: 100%; border-collapse: collapse; margin-top: 12px; }
table.items th { background: #102a43; color: #fff; text-align: left; padding: 9px; font-size: 10px; text-transform: uppercase; }
table.items td { padding: 9px; border-bottom: 1px solid #d9e2ec; }
.totals .grand { display: inline-block; background: {{ $color }}; color: #fff; padding: 8px 14px; font-size: 16px; }
.muted { opacity: .9; font-size: 11px; }
@include('documents.pdf.partials.page-bottom-styles')
.pdf-bottom { padding: 0 20px 8px; }
</style>
</head>
<body>
@include('documents.pdf.partials.pdf-sheet-open')
<div class="banner">
<table>
<tr>
<td>
    <h1>{{ ($labels['type_'.$document->type] ?? null) ?: $document->typeLabel($locale ?? 'ro') }}</h1>
    <div class="num">{{ $document->number_full ?: ($labels['draft'] ?? 'Draft') }}</div>
    <div class="muted" style="margin-top:6px">{{ $labels['date'] ?? 'Data' }} {{ dc_date($document->issue_date) }}@if($document->due_date) · {{ $labels['due_date'] ?? 'Scadență' }} {{ dc_date($document->due_date) }}@endif</div>
</td>
<td style="text-align:right">
    @include('documents.pdf.partials.logo-img')
</td>
</tr>
</table>
</div>
<div class="wrap">
<table class="parties">
<tr>
<td><div class="label">{{ $labels['supplier'] ?? 'Furnizor' }}</div>@include('documents.pdf.partials.company-block')</td>
<td><div class="label">{{ $labels['client'] ?? 'Client' }}</div>@include('documents.pdf.partials.client-block')</td>
</tr>
</table>
@include('documents.pdf.partials.items-table')
</div>
@include('documents.pdf.partials.page-bottom')
</body>
</html>
