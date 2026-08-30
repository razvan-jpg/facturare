@php($color = $document->company->invoiceColor())
@php($logo = $document->company->logoDataUri())
@php($typeLabel = ($labels['type_'.$document->type] ?? null) ?: $document->typeLabel($locale ?? 'ro'))
<!DOCTYPE html>
<html lang="{{ $locale ?? 'ro' }}">
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11.5px; color: #102a43; margin: 0; }
.letterhead { background: #f7fafc; border-bottom: 3px solid {{ $color }}; padding: 14px 18px; }
.letterhead table { width: 100%; }
.letterhead td { border: none; vertical-align: middle; padding: 0; }
.company-mini { text-align: right; font-size: 10px; color: #486581; }
.wrap { padding: 16px 18px; }
.doc-title { margin: 0 0 4px; font-size: 20px; color: {{ $color }}; }
.muted { color: #627d98; font-size: 11px; margin-bottom: 14px; }
.parties { width: 100%; margin-bottom: 8px; }
.parties td { border: none; vertical-align: top; width: 50%; padding: 0 12px 0 0; }
.label { font-size: 9px; font-weight: bold; text-transform: uppercase; color: {{ $color }}; margin-bottom: 6px; }
table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
table.items th { background: #f0f4f8; text-align: left; padding: 8px; font-size: 10px; text-transform: uppercase; border-bottom: 2px solid {{ $color }}; }
table.items td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
.totals .grand { font-size: 16px; color: {{ $color }}; }
@include('documents.pdf.partials.page-bottom-styles')
.pdf-bottom { padding: 0 18px 8px; }
</style>
</head>
<body>
@include('documents.pdf.partials.pdf-sheet-open')
<div class="letterhead">
<table>
<tr>
<td>@include('documents.pdf.partials.logo-img')</td>
<td class="company-mini">@include('documents.pdf.partials.company-block')</td>
</tr>
</table>
</div>
<div class="wrap">
    <h1 class="doc-title">{{ $typeLabel }}</h1>
    <div class="muted">
        <strong>{{ $document->number_full ?: ($labels['draft'] ?? 'Draft') }}</strong>
        · {{ $labels['date'] ?? 'Data' }} {{ dc_date($document->issue_date) }}
        @if($document->due_date) · {{ $labels['due_date'] ?? 'Scadență' }} {{ dc_date($document->due_date) }}@endif
    </div>
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
</div>
@include('documents.pdf.partials.page-bottom')
</body>
</html>
