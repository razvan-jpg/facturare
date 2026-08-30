@php($color = $document->company->invoiceColor())
@php($logo = $document->company->logoDataUri())
<!DOCTYPE html>
<html lang="{{ $locale ?? 'ro' }}">
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #243b53; margin: 0; }
h1 { color: {{ $color }}; font-size: 16px; margin: 0 0 4px; }
.muted { color: #627d98; font-size: 9px; }
.header { width: 100%; margin-bottom: 8px; }
.header td { border: none; vertical-align: top; padding: 0; }
table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
table.items th { background: #e2e8f0; text-align: left; padding: 4px 6px; font-size: 9px; text-transform: uppercase; border-bottom: 2px solid {{ $color }}; }
table.items td { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; }
.totals { font-size: 10px; }
.totals .grand { font-size: 13px; color: {{ $color }}; }
.notes { font-size: 9px; }
.sign-label { font-size: 8px; }
.sign-text-lines { font-size: 9px; }
.sign-line { height: 6px; }
.footer { font-size: 8px; }
@include('documents.pdf.partials.page-bottom-styles')
</style>
</head>
<body>
@include('documents.pdf.partials.pdf-sheet-open')
<table class="header">
<tr>
<td>
    @include('documents.pdf.partials.logo-img')
    <h1>{{ ($labels['type_'.$document->type] ?? null) ?: $document->typeLabel($locale ?? 'ro') }} {{ $document->number_full ?: ($labels['draft'] ?? 'Draft') }}</h1>
    <div class="muted">{{ $labels['date'] ?? 'Data' }}: {{ dc_date($document->issue_date) }}@if($document->due_date) · {{ $labels['due_date'] ?? 'Scadență' }}: {{ dc_date($document->due_date) }}@endif</div>
</td>
<td style="text-align:right;width:48%">
    @include('documents.pdf.partials.company-block')
</td>
</tr>
</table>
<div style="margin:6px 0">@include('documents.pdf.partials.client-block')</div>
@include('documents.pdf.partials.items-table')
@include('documents.pdf.partials.page-bottom')
</body>
</html>
