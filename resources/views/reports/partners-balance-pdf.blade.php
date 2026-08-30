<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="utf-8">
<style>
@page { margin: 12mm 10mm 12mm 10mm; }
body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #111; }
.company { font-size: 9px; line-height: 1.35; margin-bottom: 8px; }
.company strong { font-size: 10px; }
.title { text-align: center; font-size: 14px; font-weight: bold; margin: 8px 0 3px; }
.period { text-align: center; font-size: 10px; }
.currency { text-align: center; font-size: 10px; margin-bottom: 8px; }
table.bal { width: 100%; border-collapse: collapse; }
table.bal th, table.bal td { border: 1px solid #555; padding: 2px 3px; vertical-align: top; }
table.bal th { background: #f0f0f0; font-size: 7px; text-align: center; font-weight: bold; }
table.bal .num { text-align: right; white-space: nowrap; }
table.bal .name { text-align: left; }
table.bal tr.header td { font-weight: bold; background: #f7f7f7; }
table.bal tr.total td { font-weight: bold; background: #ececec; }
.sign { margin-top: 22px; width: 100%; }
.sign td { width: 33%; text-align: center; font-size: 9px; padding-top: 28px; border-top: 1px solid #333; }
.footer { margin-top: 12px; font-size: 7px; color: #555; }
</style>
</head>
<body>
@include('reports.partials.partners-balance-content')
</body>
</html>
