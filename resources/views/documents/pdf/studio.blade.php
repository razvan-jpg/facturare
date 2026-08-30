@php($color = $document->company->invoiceColor())
@php($logo = $document->company->logoDataUri())
@php($typeLabel = ($labels['type_'.$document->type] ?? null) ?: $document->typeLabel($locale ?? 'ro'))
<!DOCTYPE html>
<html lang="{{ $locale ?? 'ro' }}">
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #102a43; margin: 0; }
.pad { padding: 18px 20px; }
.hero { font-size: 42px; font-weight: bold; color: {{ $color }}; letter-spacing: -1.5px; line-height: 1; margin: 0 0 6px; text-transform: uppercase; }
.meta { color: #627d98; font-size: 11px; margin-bottom: 18px; }
.parties { width: 100%; margin-bottom: 6px; }
.parties td { border: none; vertical-align: top; width: 50%; padding: 0 12px 0 0; }
.label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: {{ $color }}; margin-bottom: 6px; }
table.items { width: 100%; border-collapse: collapse; margin-top: 8px; }
table.items th { text-align: left; padding: 7px 6px; font-size: 9px; text-transform: uppercase; background: #102a43; color: #fff; }
table.items td { padding: 7px 6px; border-bottom: 1px solid #e2e8f0; }
.totals .grand { font-size: 18px; color: {{ $color }}; }
@include('documents.pdf.partials.page-bottom-styles')
.pdf-bottom { padding: 0 20px 10px; }
</style>
</head>
<body>
@include('documents.pdf.partials.pdf-sheet-open')
<div class="pad">
    @include('documents.pdf.partials.logo-img')
    <div class="hero">{{ $typeLabel }}</div>
    <div class="meta">
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
