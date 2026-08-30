@php($color = $document->company->invoiceColor())
@php($logo = $document->company->logoDataUri())
<!DOCTYPE html>
<html lang="{{ $locale ?? 'ro' }}">
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #102a43; margin: 0; }
h1 { color: {{ $color }}; font-size: 22px; margin: 0 0 8px; }
.muted { color: #627d98; }
table.items { width: 100%; border-collapse: collapse; margin-top: 18px; }
table.items th { background: #f0f4f8; text-align: left; padding: 8px; font-size: 11px; text-transform: uppercase; color: #334e68; }
table.items td { padding: 8px; border-top: 1px solid #d9e2ec; }
.totals .grand { font-size: 16px; color: {{ $color }}; }
.header { width: 100%; }
.header td { border: none; vertical-align: top; padding: 0; }
@include('documents.pdf.partials.page-bottom-styles')
</style>
</head>
<body>
@include('documents.pdf.partials.pdf-sheet-open')
<table class="header">
<tr>
<td>
    @include('documents.pdf.partials.logo-img')
    <h1>{{ ($labels['type_'.$document->type] ?? null) ?: $document->typeLabel($locale ?? 'ro') }}</h1>
    <div><strong>{{ $document->number_full ?: ($labels['draft'] ?? 'Draft') }}</strong></div>
    <div class="muted">{{ $labels['date'] ?? 'Data' }}: {{ dc_date($document->issue_date) }}</div>
    @if($document->due_date)<div class="muted">{{ $labels['due_date'] ?? 'Scadență' }}: {{ dc_date($document->due_date) }}</div>@endif
</td>
<td style="text-align:right">
    @include('documents.pdf.partials.company-block')
</td>
</tr>
</table>

<p style="margin-top:20px">@include('documents.pdf.partials.client-block')</p>
@include('documents.pdf.partials.items-table')
@include('documents.pdf.partials.page-bottom')
</body>
</html>
