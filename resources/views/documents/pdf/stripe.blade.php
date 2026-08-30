@php($color = $document->company->invoiceColor())
<!DOCTYPE html>
<html lang="{{ $locale ?? 'ro' }}">
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11.5px; color: #102a43; margin: 0; }
.layout { width: 100%; }
.layout td { border: none; vertical-align: top; padding: 0; }
.stripe { width: 14px; background: {{ $color }}; }
.content { padding: 18px 18px 18px 16px; }
.header { width: 100%; margin-bottom: 12px; }
.header td { border: none; vertical-align: top; padding: 0; }
h1 { color: {{ $color }}; font-size: 22px; margin: 0 0 4px; }
.muted { color: #627d98; }
.client-box { border-left: 3px solid {{ $color }}; padding: 8px 0 8px 10px; margin: 14px 0; background: #f8fafc; }
table.items { width: 100%; border-collapse: collapse; margin-top: 10px; }
table.items th { background: #f0f4f8; text-align: left; padding: 8px; font-size: 10px; text-transform: uppercase; border-bottom: 2px solid {{ $color }}; }
table.items td { padding: 8px; border-bottom: 1px solid #e2e8f0; }
.totals .grand { font-size: 16px; color: {{ $color }}; }
@include('documents.pdf.partials.page-bottom-styles')
</style>
</head>
<body>
<table class="layout">
<tr>
<td class="stripe"></td>
<td class="content">
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

    <div class="client-box">@include('documents.pdf.partials.client-block')</div>
    @include('documents.pdf.partials.items-table')
@include('documents.pdf.partials.page-bottom')
</td>
</tr>
</table>
</body>
</html>
