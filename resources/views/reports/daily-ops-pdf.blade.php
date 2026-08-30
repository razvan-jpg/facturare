<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <title>Raport DateConta Facturare {{ $date->format('d.m.Y') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #102a43; }
        h1 { font-size: 14px; margin: 0 0 4px 0; }
        h2 { font-size: 11px; margin: 14px 0 6px 0; color: #0F4C5C; page-break-after: avoid; }
        .meta { color: #627d98; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { border: 1px solid #d9e2ec; padding: 4px 5px; text-align: left; vertical-align: top; }
        th { background: #0F4C5C; color: #fff; font-size: 8.5px; text-transform: uppercase; letter-spacing: .03em; }
        tr:nth-child(even) td { background: #f8fafc; }
        .num { text-align: right; white-space: nowrap; }
        .kpi { width: 100%; margin-bottom: 10px; border-collapse: separate; border-spacing: 4px; }
        .kpi td { border: 1px solid #d9e2ec; background: #f8fafc; padding: 6px 8px; width: 25%; }
        .kpi .label { font-size: 8px; text-transform: uppercase; color: #627d98; letter-spacing: .04em; }
        .kpi .value { font-size: 13px; font-weight: bold; margin-top: 2px; color: #0F4C5C; }
        .kpi .sub { font-size: 8px; color: #486581; margin-top: 2px; }
        .ok { color: #0b6e4f; }
        .fail { color: #9b2226; }
        .muted { color: #627d98; }
        .small { font-size: 8px; color: #627d98; }
        .reason { font-size: 8px; color: #9b2226; }
    </style>
</head>
<body>
@php
    $dateLabel = $date->format('d.m.Y');
    $t = $totals;
    $v = $visitors;
    $ef = $efactura;
    $fmt = fn ($n) => number_format((float) $n, 2, ',', '.');
    $typeLabels = [
        'invoice' => 'Factură',
        'proforma' => 'Proformă',
        'credit_note' => 'Storno',
        'receipt' => 'Chitanță',
    ];
@endphp

    <h1>Raport DateConta Facturare pentru ziua {{ $dateLabel }}</h1>
    <div class="meta">
        Toată platforma · generat {{ now('Europe/Bucharest')->format('d.m.Y H:i') }} (Europe/Bucharest)
    </div>

    <table class="kpi">
        <tr>
            <td>
                <div class="label">Documente emise</div>
                <div class="value">{{ $t['documents'] }}</div>
                <div class="sub">Manuale {{ $t['manual'] }} · Recurente {{ $t['recurring'] }}</div>
                <div class="sub">Facturi {{ $t['invoices'] }} · Proforme {{ $t['proformas'] }}</div>
            </td>
            <td>
                <div class="label">Încasări</div>
                <div class="value">{{ $fmt($payments_total) }}</div>
                <div class="sub">{{ $payments_count }} înregistrări · RON</div>
            </td>
            <td>
                <div class="label">Vizitatori site</div>
                <div class="value">{{ $v['total'] }}</div>
                <div class="sub">{{ $v['new'] }} noi · {{ $v['returning'] }} reveniți</div>
                <div class="sub">≈ {{ number_format($v['page_views'], 0, ',', '.') }} pageviews</div>
            </td>
            <td>
                <div class="label">e-Factura (facturi)</div>
                <div class="value">{{ $ef['sent'] }}</div>
                <div class="sub">
                    OK <span class="ok">{{ $ef['ok'] }}</span>
                    · Err <span class="fail">{{ $ef['errors'] }}</span>
                    @if($ef['pending'] > 0) · În curs {{ $ef['pending'] }}@endif
                </div>
                <div class="sub">Eligible {{ $ef['eligible'] }} · Netremise {{ $ef['none'] }}</div>
            </td>
        </tr>
    </table>

    @if($ef['errors'] > 0)
        <h2>Erori / respingeri e-Factura</h2>
        <table>
            <thead>
                <tr>
                    <th>Societate</th>
                    <th>Factură</th>
                    <th>Client</th>
                    <th>Status</th>
                    <th>Motiv</th>
                </tr>
            </thead>
            <tbody>
                @foreach($ef['error_rows'] as $err)
                    <tr>
                        <td>
                            {{ $err->company_name }}
                            @if($err->company_cui)<div class="small">CUI {{ $err->company_cui }}</div>@endif
                        </td>
                        <td>{{ $err->number_full }}</td>
                        <td>{{ $err->client_name }}</td>
                        <td class="fail">{{ strtoupper($err->status) }}</td>
                        <td class="reason">{{ $err->reason }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Documente manuale (utilizator → societate)</h2>
    <table>
        <thead>
            <tr>
                <th>Utilizator</th>
                <th>Societate</th>
                <th class="num">Doc.</th>
                <th class="num">F / P</th>
                <th class="num">Total RON</th>
            </tr>
        </thead>
        <tbody>
            @forelse($documents_by_user as $row)
                <tr>
                    <td>
                        {{ $row->user_name }}
                        <div class="small">{{ $row->user_email }}</div>
                    </td>
                    <td>
                        {{ $row->company_name }}
                        @if($row->company_cui)<div class="small">CUI {{ $row->company_cui }}</div>@endif
                    </td>
                    <td class="num">{{ $row->count }}</td>
                    <td class="num">{{ $row->invoices }} / {{ $row->proformas }}</td>
                    <td class="num">{{ $fmt($row->total_ron) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Nicio emitere manuală.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Recurente emise (pe societate)</h2>
    <table>
        <thead>
            <tr>
                <th>Societate</th>
                <th class="num">Doc.</th>
                <th class="num">F / P</th>
                <th class="num">Total RON</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recurring_by_company as $row)
                <tr>
                    <td>
                        {{ $row->company_name }}
                        @if($row->company_cui)<div class="small">CUI {{ $row->company_cui }}</div>@endif
                    </td>
                    <td class="num">{{ $row->count }}</td>
                    <td class="num">{{ $row->invoices }} / {{ $row->proformas }}</td>
                    <td class="num">{{ $fmt($row->total_ron) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">Nicio emitere recurentă.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Lista documentelor emise</h2>
    <table>
        <thead>
            <tr>
                <th>Societate</th>
                <th>Document</th>
                <th>Tip</th>
                <th>Client</th>
                <th>Sursă</th>
                <th>e-Factura</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($documents as $doc)
                @php
                    $efStatus = strtolower(trim((string) ($doc->efactura_status ?: 'none')));
                    if ($efStatus === '' || $efStatus === 'not_sent') {
                        $efStatus = 'none';
                    }
                    $efLabel = $doc->type === 'proforma'
                        ? 'N/A'
                        : (\App\Models\Document::EFACTURA_LABELS[$efStatus] ?? $efStatus);
                @endphp
                <tr>
                    <td>
                        {{ $doc->company?->name ?: '—' }}
                        @if($doc->company?->cui)<div class="small">CUI {{ $doc->company->cui }}</div>@endif
                    </td>
                    <td>{{ $doc->number_full ?: ('#'.$doc->id) }}</td>
                    <td>{{ $typeLabels[$doc->type] ?? $doc->type }}</td>
                    <td>{{ $doc->client?->name ?: ($doc->client_name ?: '—') }}</td>
                    <td>{{ $doc->recurring_invoice_id ? 'Recurentă' : 'Manuală' }}</td>
                    <td>{{ $efLabel }}</td>
                    <td class="num">{{ $fmt($doc->total) }} {{ $doc->currency ?: 'RON' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted">Niciun document emis.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Încasări (pe societate)</h2>
    <table>
        <thead>
            <tr>
                <th>Societate</th>
                <th class="num">Nr.</th>
                <th class="num">Sumă RON</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments_by_company as $row)
                <tr>
                    <td>
                        {{ $row->company_name }}
                        @if($row->company_cui)<div class="small">CUI {{ $row->company_cui }}</div>@endif
                    </td>
                    <td class="num">{{ $row->count }}</td>
                    <td class="num">{{ $fmt($row->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="muted">Nicio încasare.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Detaliu încasări</h2>
    <table>
        <thead>
            <tr>
                <th>Societate</th>
                <th>Document</th>
                <th>Client</th>
                <th>Metodă</th>
                <th class="num">Sumă</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $p)
                <tr>
                    <td>{{ $p->company?->name ?: '—' }}</td>
                    <td>{{ $p->document?->number_full ?: '—' }}</td>
                    <td>{{ $p->client?->name ?: '—' }}</td>
                    <td>{{ $p->methodLabel() }}</td>
                    <td class="num">{{ $fmt($p->amount) }} {{ $p->currency ?: 'RON' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Nicio încasare.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Conturi &amp; societăți noi</h2>
    <p style="margin:0 0 6px 0;">
        Utilizatori noi: <strong>{{ $new_users->count() }}</strong>
        · Societăți noi: <strong>{{ $new_companies->count() }}</strong>
    </p>
    @if($new_users->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Nume</th>
                    <th>Email</th>
                    <th>Creat</th>
                </tr>
            </thead>
            <tbody>
                @foreach($new_users as $u)
                    <tr>
                        <td>{{ $u->name }}</td>
                        <td>{{ $u->email }}</td>
                        <td>{{ optional($u->created_at)->timezone('Europe/Bucharest')->format('H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
    @if($new_companies->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Societate</th>
                    <th>CUI</th>
                    <th>Creat</th>
                </tr>
            </thead>
            <tbody>
                @foreach($new_companies as $c)
                    <tr>
                        <td>{{ $c->name }}</td>
                        <td>{{ $c->cui ?: '—' }}</td>
                        <td>{{ optional($c->created_at)->timezone('Europe/Bucharest')->format('H:i') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
