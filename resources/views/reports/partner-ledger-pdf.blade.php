<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="utf-8">
<style>
@page { margin: 18mm 14mm 16mm 14mm; }
body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
.company { font-size: 11px; line-height: 1.35; margin-bottom: 10px; }
.company strong { font-size: 12px; }
.title { text-align: center; font-size: 16px; font-weight: bold; margin: 12px 0 4px; letter-spacing: .02em; }
.period { text-align: center; font-size: 11px; margin-bottom: 2px; }
.currency { text-align: center; font-size: 11px; margin-bottom: 10px; }
.meta { margin: 8px 0 10px; font-size: 11px; }
.meta td { padding: 1px 0; vertical-align: top; }
.meta .lbl { width: 70px; color: #333; }
table.ledger { width: 100%; border-collapse: collapse; margin-top: 6px; }
table.ledger th {
    border: 1px solid #444; background: #f3f3f3; padding: 4px 5px; font-size: 9px;
    text-align: left; font-weight: bold;
}
table.ledger td { border: 1px solid #666; padding: 3px 5px; font-size: 9px; }
table.ledger .num { text-align: right; white-space: nowrap; }
table.ledger .center { text-align: center; }
table.ledger tr.opening td { font-weight: bold; background: #fafafa; }
table.ledger tr.total td { font-weight: bold; background: #f0f0f0; }
.totals-wrap { margin-top: 16px; width: 72%; margin-left: auto; }
.totals-wrap table { width: 100%; border-collapse: collapse; }
.totals-wrap th, .totals-wrap td { border: 1px solid #666; padding: 4px 6px; font-size: 9px; }
.totals-wrap th { background: #f3f3f3; text-align: left; }
.totals-wrap .num { text-align: right; }
.footer { margin-top: 18px; font-size: 8px; color: #555; }
</style>
</head>
<body>
@include('reports.partials.partner-ledger-content')
</body>
</html>
