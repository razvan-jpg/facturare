@php($color = $document->company->invoiceColor())
@php($logo = $document->company->logoDataUri())
@php($typeLabel = ($labels['type_'.$document->type] ?? null) ?: $document->typeLabel($locale ?? 'ro'))
<!DOCTYPE html>
<html lang="{{ $locale ?? 'ro' }}">
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #102a43; margin: 0; }
.wrap { max-width: 420px; margin: 0 auto; padding: 24px 28px; }
.center { text-align: center; }
h1 { margin: 0; font-size: 18px; color: {{ $color }}; text-transform: uppercase; letter-spacing: 1px; }
.num { color: #627d98; margin: 6px 0 14px; font-size: 11px; }
.dash { border: none; border-top: 1px dashed #bcccdc; margin: 12px 0; }
.party { font-size: 10px; color: #486581; margin-bottom: 4px; }
.label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: {{ $color }}; margin: 10px 0 4px; }
table.items { width: 100%; border-collapse: collapse; margin-top: 6px; }
table.items th { text-align: left; padding: 5px 3px; font-size: 9px; text-transform: uppercase; border-bottom: 1px solid {{ $color }}; background: transparent; color: #627d98; font-weight: normal; }
table.items td { padding: 6px 3px; border-bottom: 1px dashed #e2e8f0; }
.big-total { text-align: center; margin: 18px 0 8px; }
.big-total .lbl { font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; color: #627d98; }
.big-total .amt { font-size: 28px; font-weight: bold; color: {{ $color }}; margin-top: 4px; }
.totals .grand { font-size: 14px; color: {{ $color }}; }
@include('documents.pdf.partials.page-bottom-styles')
.pdf-bottom-table { width: 100%; }
.pdf-bottom { max-width: 420px; margin: 0 auto !important; padding: 0 28px 16px; }
</style>
</head>
<body>
@include('documents.pdf.partials.pdf-sheet-open')
<div class="wrap">
<div class="center">
    @include('documents.pdf.partials.logo-img')
    <h1>{{ $typeLabel }}</h1>
    <div class="num"><strong>{{ $document->number_full ?: ($labels['draft'] ?? 'Draft') }}</strong><br>
        {{ dc_date($document->issue_date) }}@if($document->due_date) · {{ $labels['due_date'] ?? 'Scadență' }} {{ dc_date($document->due_date) }}@endif
    </div>
</div>
<hr class="dash">
<div class="label">{{ $labels['supplier'] ?? 'Furnizor' }}</div>
<div class="party">@include('documents.pdf.partials.company-block')</div>
<div class="label">{{ $labels['client'] ?? 'Client' }}</div>
<div class="party">@include('documents.pdf.partials.client-block')</div>
<hr class="dash">
@include('documents.pdf.partials.items-table')
<div class="big-total">
    <div class="lbl">{{ $labels['total'] ?? 'Total' }}</div>
    <div class="amt">{{ number_format((float) $document->total, 2, ',', '.') }} <span style="font-size:16px">{{ $document->currency }}</span></div>
    @include('documents.pdf.partials.ron-equivalent', ['style' => 'margin-top:6px;font-size:10px;color:#486581;line-height:1.35;text-align:left;'])
</div>
<hr class="dash">
</div>
@include('documents.pdf.partials.page-bottom')
</body>
</html>
