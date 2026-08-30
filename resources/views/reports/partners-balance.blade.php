<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Balanță parteneri — {{ $company->name }}</title>
<style>
* { box-sizing: border-box; }
body { margin: 0; font-family: Georgia, "Times New Roman", Times, serif; font-size: 11px; color: #111; background: {{ !empty($embed) ? '#fff' : '#e8eef2' }}; }
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
.dc-btn-primary { background: #fff; color: #115e59; }
.dc-btn-primary:hover { background: #f0fdfa; }
.dc-btn-secondary { background: transparent; color: #fff; border-color: rgba(255,255,255,.45); }
.dc-btn-secondary:hover { background: rgba(255,255,255,.12); }
.sheet {
    max-width: {{ !empty($embed) ? '100%' : '1200px' }};
    margin: {{ !empty($embed) ? '0' : '16px auto' }};
    padding: {{ !empty($embed) ? '16px 18px' : '20px 22px' }};
    background: #fff;
    box-shadow: {{ !empty($embed) ? 'none' : '0 8px 28px rgba(15, 23, 42, .12)' }};
    overflow-x: auto;
}
.company { font-size: 11px; line-height: 1.4; margin-bottom: 10px; }
.company strong { font-size: 12px; }
.title { text-align: center; font-size: 16px; font-weight: bold; margin: 10px 0 4px; }
.period, .currency { text-align: center; font-size: 12px; }
.currency { margin-bottom: 10px; }
table.bal { width: 100%; border-collapse: collapse; min-width: 900px; }
table.bal th, table.bal td { border: 1px solid #555; padding: 3px 4px; vertical-align: top; }
table.bal th { background: #f0f0f0; font-size: 10px; text-align: center; font-weight: bold; }
table.bal .num { text-align: right; white-space: nowrap; }
table.bal .name { text-align: left; }
table.bal tr.header td { font-weight: bold; background: #f7f7f7; }
table.bal tr.total td { font-weight: bold; background: #ececec; }
.sign { margin-top: 28px; width: 100%; }
.sign td { width: 33%; text-align: center; font-size: 11px; padding-top: 32px; border-top: 1px solid #333; }
.footer { margin-top: 14px; font-size: 10px; color: #555; font-family: system-ui, sans-serif; }
@media print {
    body { background: #fff; }
    .no-print { display: none !important; }
    .sheet { max-width: none; margin: 0; padding: 0; box-shadow: none; overflow: visible; }
    @page { size: landscape; margin: 10mm; }
}
</style>
</head>
<body>
@if(empty($embed))
@include('reports.partials.report-preview-toolbar', [
    'title' => 'Balanță parteneri / BALANTA TERTI',
    'pdfUrl' => $pdfUrl,
])
@endif
<div class="sheet">
    @include('reports.partials.partners-balance-content')
</div>
</body>
</html>
