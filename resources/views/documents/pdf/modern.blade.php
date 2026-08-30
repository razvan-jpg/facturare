@php($color = $document->company->invoiceColor())
@php($logo = $document->company->logoDataUri())
<!DOCTYPE html>
<html lang="{{ $locale ?? 'ro' }}">
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11.5px; color: #102a43; margin: 0; }
.topbar { background: {{ $color }}; color: #fff; padding: 16px 18px; }
.topbar h1 { margin: 0; font-size: 20px; color: #fff; }
.topbar .meta { opacity: .9; margin-top: 4px; font-size: 11px; }
.wrap { padding: 18px; }
.grid { width: 100%; }
.grid td { border: none; vertical-align: top; width: 50%; padding: 0; }
.box { background: #f7fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 4px; }
.label { font-size: 10px; text-transform: uppercase; color: #627d98; margin-bottom: 6px; }
table.items { width: 100%; border-collapse: collapse; margin-top: 18px; }
table.items th { background: {{ $color }}; color: #fff; text-align: left; padding: 8px; font-size: 10px; text-transform: uppercase; }
table.items td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
.totals .grand { font-size: 18px; color: {{ $color }}; }
@include('documents.pdf.partials.page-bottom-styles')
.pdf-bottom { padding: 0 18px 8px; }
</style>
</head>
<body>
@include('documents.pdf.partials.pdf-sheet-open')
<div class="topbar">
    <h1>{{ ($labels['type_'.$document->type] ?? null) ?: $document->typeLabel($locale ?? 'ro') }} {{ $document->number_full ?: ($labels['draft'] ?? 'Draft') }}</h1>
    <div class="meta">{{ $labels['date'] ?? 'Data' }} {{ dc_date($document->issue_date) }}@if($document->due_date) · {{ $labels['due_date'] ?? 'Scadență' }} {{ dc_date($document->due_date) }}@endif</div>
</div>
<div class="wrap">
<table class="grid">
<tr>
<td>
    @include('documents.pdf.partials.logo-img')
    <div class="label">{{ $labels['supplier'] ?? 'Furnizor' }}</div>
    @include('documents.pdf.partials.company-block')
</td>
<td>
    <div class="box">
        <div class="label">{{ $labels['client'] ?? 'Client' }}</div>
        @include('documents.pdf.partials.client-block')
    </div>
</td>
</tr>
</table>
@include('documents.pdf.partials.items-table')
</div>
@include('documents.pdf.partials.page-bottom')
</body>
</html>
