@php
    $teal = '#0f4c5c';
    $amber = '#e08a1e';
    $ink = '#102a43';
    $muted = '#627d98';
    $typeLabel = ($labels['type_'.$document->type] ?? null) ?: $document->typeLabel($locale ?? 'ro');
    $logo = $document->company->logoDataUri();
@endphp
<!DOCTYPE html>
<html lang="{{ $locale ?? 'ro' }}">
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: {{ $ink }}; margin: 0; }
.header {
    background: {{ $teal }};
    color: #fff;
    padding: 18px 20px 14px;
}
.header-table { width: 100%; border-collapse: collapse; }
.header-table td { border: none; vertical-align: middle; padding: 0; color: #fff; }
.brand-mark {
    display: inline-block;
    background: {{ $amber }};
    color: #fff;
    font-size: 9px;
    font-weight: bold;
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 3px 8px;
    margin-bottom: 8px;
}
.header h1 { margin: 0; font-size: 22px; color: #fff; letter-spacing: 0.5px; }
.header .doc-num { margin-top: 4px; font-size: 12px; color: #fff; opacity: 0.95; }
.header .meta { text-align: right; font-size: 11px; line-height: 1.45; color: #fff; }
.header .meta strong { color: #fff; }
.amber-bar { height: 5px; background: {{ $amber }}; }
.wrap { padding: 16px 20px 8px; }
.parties { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
.parties td { width: 50%; border: none; vertical-align: top; padding: 0; }
.parties td + td { padding-left: 12px; }
.party {
    border: 1px solid #d9e2ec;
    border-top: 3px solid {{ $teal }};
    background: #f7fafc;
    padding: 10px 12px;
}
.party.client { border-top-color: {{ $amber }}; }
.party .label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: {{ $muted }};
    font-weight: bold;
    margin-bottom: 6px;
}
table.items { width: 100%; border-collapse: collapse; margin-top: 4px; }
table.items th {
    background: {{ $teal }};
    color: #fff;
    text-align: left;
    padding: 8px 6px;
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
table.items td { padding: 8px 6px; border-bottom: 1px solid #e2e8f0; }
table.items tr:nth-child(even) td { background: #f8fafc; }
.totals {
    border: 1px solid #d9e2ec;
    border-left: 4px solid {{ $amber }};
    background: #fffaf3;
    padding: 10px 12px;
    display: inline-block;
    min-width: 210px;
}
.totals .grand { font-size: 16px; color: {{ $teal }}; margin-top: 4px; }
.totals .grand strong { color: {{ $amber }}; }
@include('documents.pdf.partials.page-bottom-styles')
.pdf-bottom { padding: 0 20px 8px; }
.footer { color: {{ $muted }}; }
</style>
</head>
<body>
@include('documents.pdf.partials.pdf-sheet-open')
<div class="header">
    <table class="header-table">
        <tr>
            <td style="width:62%">
                <div class="brand-mark">DateConta</div>
                <h1>{{ $typeLabel }}</h1>
                <div class="doc-num">{{ $document->number_full ?: ($labels['draft'] ?? 'Draft') }}</div>
            </td>
            <td class="meta" style="width:38%">
                @if($logo)
                    <div style="margin-bottom:8px">@include('documents.pdf.partials.logo-img')</div>
                @endif
                <div><strong>{{ $labels['date'] ?? 'Data' }}:</strong> {{ dc_date($document->issue_date) }}</div>
                @if($document->due_date)
                    <div><strong>{{ $labels['due_date'] ?? 'Scadență' }}:</strong> {{ dc_date($document->due_date) }}</div>
                @endif
            </td>
        </tr>
    </table>
</div>
<div class="amber-bar"></div>
<div class="wrap">
    <table class="parties">
        <tr>
            <td>
                <div class="party">
                    <div class="label">{{ $labels['supplier'] ?? 'Furnizor' }}</div>
                    @include('documents.pdf.partials.company-block')
                </div>
            </td>
            <td>
                <div class="party client">
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
