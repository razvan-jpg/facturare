@php($color = $document->company->invoiceColor())
@php($logo = $document->company->logoDataUri())
<!DOCTYPE html>
<html lang="{{ $locale ?? 'ro' }}">
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #243b53; margin: 0; }
.center { text-align: center; margin-bottom: 14px; }
.rule { border: none; border-top: 1px solid {{ $color }}; margin: 10px 0 14px; }
.rule-thin { border: none; border-top: 1px solid #d9e2ec; margin: 8px 0; }
h1 { color: {{ $color }}; font-size: 20px; margin: 6px 0 2px; font-weight: normal; letter-spacing: 2px; text-transform: uppercase; }
.sub { color: #627d98; font-size: 11px; }
.parties { width: 100%; }
.parties td { border: none; vertical-align: top; width: 50%; padding: 0 12px 0 0; }
.label { font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; color: {{ $color }}; margin-bottom: 6px; }
table.items { width: 100%; border-collapse: collapse; margin-top: 10px; }
table.items th { border-bottom: 1px solid {{ $color }}; text-align: left; padding: 7px 6px; font-size: 9px; text-transform: uppercase; letter-spacing: .8px; font-weight: normal; color: {{ $color }}; background: transparent; }
table.items td { padding: 7px 6px; border-bottom: 1px solid #eef2f6; }
.totals .grand { font-size: 15px; color: {{ $color }}; }
.notes { font-style: italic; }
.footer { text-align: center; }
@include('documents.pdf.partials.page-bottom-styles')
</style>
</head>
<body>
@include('documents.pdf.partials.pdf-sheet-open')
<div class="center">
    @include('documents.pdf.partials.logo-img')
    <h1>{{ ($labels['type_'.$document->type] ?? null) ?: $document->typeLabel($locale ?? 'ro') }}</h1>
    <div class="sub"><strong>{{ $document->number_full ?: ($labels['draft'] ?? 'Draft') }}</strong> · {{ dc_date($document->issue_date) }}@if($document->due_date) · {{ $labels['due_date'] ?? 'Scadență' }} {{ dc_date($document->due_date) }}@endif</div>
</div>
<hr class="rule">
<table class="parties">
<tr>
<td><div class="label">{{ $labels['supplier'] ?? 'Furnizor' }}</div>@include('documents.pdf.partials.company-block')</td>
<td><div class="label">{{ $labels['client'] ?? 'Client' }}</div>@include('documents.pdf.partials.client-block')</td>
</tr>
</table>
<hr class="rule-thin">
@include('documents.pdf.partials.items-table')
@include('documents.pdf.partials.page-bottom')
</body>
</html>
