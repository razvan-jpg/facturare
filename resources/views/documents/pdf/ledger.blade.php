@php($color = $document->company->invoiceColor())
@php($logo = $document->company->logoDataUri())
@php($typeLabel = ($labels['type_'.$document->type] ?? null) ?: $document->typeLabel($locale ?? 'ro'))
<!DOCTYPE html>
<html lang="{{ $locale ?? 'ro' }}">
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #102a43; margin: 0; }
.outer { border: 2px solid {{ $color }}; padding: 8px; }
.inner { border: 1px solid #bcccdc; padding: 10px; }
.head { width: 100%; margin-bottom: 10px; border: 1px solid #bcccdc; }
.head td { border: none; vertical-align: middle; padding: 10px 12px; }
h1 { margin: 0; font-size: 18px; color: {{ $color }}; text-transform: uppercase; }
.stamp { border: 1px dashed {{ $color }}; padding: 8px 10px; text-align: center; font-size: 10px; color: {{ $color }}; min-width: 90px; }
.parties { width: 100%; margin: 10px 0; }
.parties td { border: 1px solid #bcccdc; vertical-align: top; width: 50%; padding: 10px; }
.label { font-size: 9px; font-weight: bold; text-transform: uppercase; color: {{ $color }}; margin-bottom: 6px; border-bottom: 1px solid #d9e2ec; padding-bottom: 4px; }
table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
table.items th { background: #f0f4f8; border: 1px solid #bcccdc; text-align: left; padding: 6px; font-size: 9px; text-transform: uppercase; }
table.items td { border: 1px solid #d9e2ec; padding: 6px; }
.totals { border: 2px solid {{ $color }}; padding: 8px; }
.totals .grand { font-size: 15px; color: {{ $color }}; }
.muted { color: #486581; font-size: 10px; }
@include('documents.pdf.partials.page-bottom-styles')
</style>
</head>
<body>
<div class="outer"><div class="inner">
@include('documents.pdf.partials.pdf-sheet-open')
<table class="head">
<tr>
<td>
    @include('documents.pdf.partials.logo-img')
    <h1>{{ $typeLabel }}</h1>
    <div class="muted">{{ $labels['date'] ?? 'Data' }}: {{ dc_date($document->issue_date) }}@if($document->due_date) · {{ $labels['due_date'] ?? 'Scadență' }}: {{ dc_date($document->due_date) }}@endif</div>
</td>
<td style="width:130px;text-align:right">
    <div class="stamp">
        <div style="font-size:8px;text-transform:uppercase">Serie / Nr.</div>
        <strong>{{ $document->number_full ?: ($labels['draft'] ?? 'Draft') }}</strong>
    </div>
</td>
</tr>
</table>
<table class="parties">
<tr>
<td>
    <div class="label">{{ $labels['supplier'] ?? 'Furnizor' }}</div>
    @include('documents.pdf.partials.company-block')
</td>
<td>
    <div class="label">{{ $labels['client'] ?? 'Client' }}</div>
    @include('documents.pdf.partials.client-block')
</td>
</tr>
</table>
@include('documents.pdf.partials.items-table')
@include('documents.pdf.partials.page-bottom')
</div></div>
</body>
</html>
