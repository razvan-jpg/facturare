@php($color = $document->company->invoiceColor())
@php($logo = $document->company->logoDataUri())
@php($typeLabel = ($labels['type_'.$document->type] ?? null) ?: $document->typeLabel($locale ?? 'ro'))
<!DOCTYPE html>
<html lang="{{ $locale ?? 'ro' }}">
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #243b53; margin: 0; }
.frame { border: 1.5px solid {{ $color }}; padding: 6px; }
.inner { border: 1px solid #d9e2ec; padding: 16px 18px; }
.center { text-align: center; margin-bottom: 16px; }
h1 { margin: 0; font-size: 18px; font-weight: normal; letter-spacing: 3px; text-transform: uppercase; color: {{ $color }}; }
.sub { color: #627d98; margin-top: 6px; font-size: 11px; }
.parties { width: 100%; margin: 12px 0; }
.parties td { border: none; vertical-align: top; width: 50%; padding: 0 14px 0 0; }
.label { font-size: 9px; letter-spacing: 1.2px; text-transform: uppercase; color: {{ $color }}; margin-bottom: 8px; }
table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
table.items th { text-align: left; padding: 8px 6px; font-size: 9px; text-transform: uppercase; letter-spacing: .5px; color: {{ $color }}; border-top: 1px solid {{ $color }}; border-bottom: 1px solid {{ $color }}; background: transparent; font-weight: normal; }
table.items td { padding: 8px 6px; border-bottom: 1px solid #eef2f6; }
.totals .grand { font-size: 15px; color: {{ $color }}; }
@include('documents.pdf.partials.page-bottom-styles')
</style>
</head>
<body>
<div class="frame"><div class="inner">
@include('documents.pdf.partials.pdf-sheet-open')
<div class="center">
    @include('documents.pdf.partials.logo-img')
    <h1>{{ $typeLabel }}</h1>
    <div class="sub"><strong>{{ $document->number_full ?: ($labels['draft'] ?? 'Draft') }}</strong> · {{ dc_date($document->issue_date) }}@if($document->due_date) · {{ $labels['due_date'] ?? 'Scadență' }} {{ dc_date($document->due_date) }}@endif</div>
</div>
<table class="parties">
<tr>
<td><div class="label">{{ $labels['supplier'] ?? 'Furnizor' }}</div>@include('documents.pdf.partials.company-block')</td>
<td><div class="label">{{ $labels['client'] ?? 'Client' }}</div>@include('documents.pdf.partials.client-block')</td>
</tr>
</table>
@include('documents.pdf.partials.items-table')
@include('documents.pdf.partials.page-bottom')
</div></div>
</body>
</html>
