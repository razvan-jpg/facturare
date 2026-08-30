<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Fișă de partener — {{ $client->name }}</title>
<style>
:root { --teal-800: #115e59; --slate-100: #f1f5f9; --slate-700: #334155; }
* { box-sizing: border-box; }
body { margin: 0; font-family: Georgia, "Times New Roman", Times, serif; font-size: 12px; color: #111; background: {{ !empty($embed) ? '#fff' : '#e8eef2' }}; }
.dc-report-toolbar {
    position: sticky; top: 0; z-index: 20;
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px;
    padding: 12px 16px; background: #0f766e; color: #fff;
    font-family: system-ui, -apple-system, Segoe UI, sans-serif;
}
.dc-report-toolbar-title { font-weight: 600; font-size: 14px; }
.dc-report-toolbar-actions { display: flex; flex-wrap: wrap; gap: 8px; }
.dc-btn-primary, .dc-btn-secondary {
    display: inline-flex; align-items: center; justify-content: center;
    padding: 8px 14px; border-radius: 8px; font-size: 13px; font-weight: 600;
    text-decoration: none; border: 1px solid transparent; cursor: pointer;
}
.dc-btn-primary { background: #fff; color: var(--teal-800); }
.dc-btn-primary:hover { background: #f0fdfa; }
.dc-btn-secondary { background: transparent; color: #fff; border-color: rgba(255,255,255,.45); }
.dc-btn-secondary:hover { background: rgba(255,255,255,.12); }
.sheet {
    max-width: {{ !empty($embed) ? '100%' : '920px' }};
    margin: {{ !empty($embed) ? '0' : '20px auto' }};
    padding: {{ !empty($embed) ? '20px 24px' : '28px 32px' }};
    background: #fff;
    box-shadow: {{ !empty($embed) ? 'none' : '0 8px 28px rgba(15, 23, 42, .12)' }};
}
.company { font-size: 12px; line-height: 1.4; margin-bottom: 12px; }
.company strong { font-size: 13px; }
.title { text-align: center; font-size: 18px; font-weight: bold; margin: 14px 0 6px; letter-spacing: .02em; }
.period, .currency { text-align: center; font-size: 12px; }
.currency { margin-bottom: 12px; }
.meta { margin: 10px 0 12px; font-size: 12px; }
.meta td { padding: 2px 0; vertical-align: top; }
.meta .lbl { width: 80px; color: #333; }
table.ledger { width: 100%; border-collapse: collapse; margin-top: 8px; }
table.ledger th {
    border: 1px solid #444; background: #f3f3f3; padding: 5px 6px; font-size: 11px;
    text-align: left; font-weight: bold;
}
table.ledger td { border: 1px solid #666; padding: 4px 6px; font-size: 11px; }
table.ledger .num { text-align: right; white-space: nowrap; }
table.ledger .center { text-align: center; }
table.ledger tr.opening td { font-weight: bold; background: #fafafa; }
table.ledger tr.total td { font-weight: bold; background: #f0f0f0; }
.totals-wrap { margin-top: 18px; width: 72%; margin-left: auto; }
.totals-wrap table { width: 100%; border-collapse: collapse; }
.totals-wrap th, .totals-wrap td { border: 1px solid #666; padding: 5px 7px; font-size: 11px; }
.totals-wrap th { background: #f3f3f3; text-align: left; }
.totals-wrap .num { text-align: right; }
.footer { margin-top: 20px; font-size: 10px; color: #555; font-family: system-ui, sans-serif; }
@media print {
    body { background: #fff; }
    .no-print { display: none !important; }
    .sheet { max-width: none; margin: 0; padding: 0; box-shadow: none; }
    @page { margin: 14mm 12mm; }
}
</style>
</head>
<body>
@if(empty($embed))
@include('reports.partials.report-preview-toolbar', [
    'title' => 'Fișă de partener — '.$client->name,
    'pdfUrl' => $pdfUrl,
])
@endif
<div class="sheet">
    @include('reports.partials.partner-ledger-content')
</div>
</body>
</html>
