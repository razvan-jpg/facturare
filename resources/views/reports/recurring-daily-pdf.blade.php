<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <title>Raport emitere recurente {{ $date->format('d.m.Y') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #102a43; }
        h1 { font-size: 15px; margin: 0 0 4px 0; }
        h2 { font-size: 12px; margin: 16px 0 6px 0; color: #0F4C5C; }
        .meta { color: #627d98; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #d9e2ec; padding: 5px 6px; text-align: left; vertical-align: top; }
        th { background: #0F4C5C; color: #fff; font-size: 9px; text-transform: uppercase; letter-spacing: .03em; }
        tr:nth-child(even) td { background: #f8fafc; }
        .num { text-align: right; white-space: nowrap; }
        .totals { margin-top: 8px; }
        .totals td { border: none; padding: 2px 0; }
        .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; background: #e0f2f1; font-size: 9px; }
        .ok { color: #0b6e4f; }
        .fail { color: #9b2226; }
        .muted { color: #627d98; }
        .err { font-size: 8px; color: #9b2226; }
    </style>
</head>
<body>
    <h1>Raport emitere documente recurente</h1>
    <div class="meta">
        Data emiterii: <strong>{{ $date->format('d.m.Y') }}</strong>
        · generat {{ now('Europe/Bucharest')->format('d.m.Y H:i') }} (Europe/Bucharest)
        · toate societățile
        · CC documente: <strong>{{ $platform_cc ?? 'facturare@fly-david.ro' }}</strong>
    </div>

    <h2>1. Agregare e-Factura</h2>
    <table>
        <thead>
            <tr>
                <th>Societate</th>
                <th>CUI</th>
                <th>Tip document</th>
                <th>Stadiu e-Factura</th>
                <th class="num">Cantitate</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                @php
                    $typeLabels = ['invoice' => 'Factură', 'proforma' => 'Proformă'];
                    $typeLabel = $typeLabels[$row->document_type] ?? $row->document_type;
                    $efLabel = \App\Models\Document::EFACTURA_LABELS[$row->efactura_status] ?? $row->efactura_status;
                    if ($row->document_type === 'proforma') {
                        $efLabel = 'N/A (proformă)';
                    }
                @endphp
                <tr>
                    <td>{{ $row->company_name }}</td>
                    <td>{{ $row->company_cui }}</td>
                    <td><span class="badge">{{ $typeLabel }}</span></td>
                    <td>{{ $efLabel }}</td>
                    <td class="num">{{ (int) $row->documents_count }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Nu s-au emis documente din recurente în această zi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td><strong>Total facturi:</strong> {{ (int) ($totals['invoice'] ?? 0) }}</td>
            <td><strong>Total proforme:</strong> {{ (int) ($totals['proforma'] ?? 0) }}</td>
            <td><strong>Total general:</strong> {{ (int) $grand_total }}</td>
        </tr>
        <tr>
            <td><strong>Email trimise:</strong> {{ (int) ($email_totals['sent'] ?? 0) }}</td>
            <td><strong>Email eșuate:</strong> {{ (int) ($email_totals['failed'] ?? 0) }}</td>
            <td><strong>Email neconfigurate:</strong> {{ (int) (($email_totals['skipped'] ?? 0) + ($email_totals['none'] ?? 0)) }}</td>
        </tr>
    </table>

    <h2>2. Detaliu documente — email beneficiar</h2>
    <table>
        <thead>
            <tr>
                <th>Societate</th>
                <th>Document</th>
                <th>Client</th>
                <th>To</th>
                <th>Cc</th>
                <th>Email</th>
                <th class="num">Încerc.</th>
                <th>e-Factura</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($documents ?? collect()) as $doc)
                @php
                    $typeLabels = ['invoice' => 'Factură', 'proforma' => 'Proformă'];
                    $typeLabel = $typeLabels[$doc->type] ?? $doc->type;
                    $emailStatus = (string) ($doc->client_email_status ?: 'none');
                    $emailLabel = \App\Models\Document::CLIENT_EMAIL_LABELS[$emailStatus] ?? $emailStatus;
                    $efStatus = (string) ($doc->efactura_status ?: 'none');
                    $efLabel = $doc->type === 'proforma'
                        ? 'N/A'
                        : (\App\Models\Document::EFACTURA_LABELS[$efStatus] ?? $efStatus);
                    $to = [];
                    if ($doc->auto_email_client) {
                        $to = array_merge($to, dc_parse_emails($doc->client_email ?: $doc->client?->email));
                    }
                    if ($doc->auto_email_cc && filled($doc->auto_email_cc_address)) {
                        $to = array_merge($to, dc_parse_emails($doc->auto_email_cc_address));
                    }
                    $to = array_values(array_unique($to));
                    $cc = $doc->recurring_invoice_id ? ($platform_cc ?? 'facturare@fly-david.ro') : '—';
                    $cls = $emailStatus === 'sent' ? 'ok' : ($emailStatus === 'failed' ? 'fail' : 'muted');
                @endphp
                <tr>
                    <td>{{ $doc->company?->name }}</td>
                    <td>
                        <span class="badge">{{ $typeLabel }}</span><br>
                        {{ $doc->number_full ?: ($doc->series.'-'.$doc->number) }}
                    </td>
                    <td>{{ $doc->client_name ?: ($doc->client?->name ?: '—') }}</td>
                    <td>{{ $to !== [] ? implode(', ', $to) : '—' }}</td>
                    <td>{{ $doc->wantsClientEmail() ? $cc : '—' }}</td>
                    <td class="{{ $cls }}">
                        {{ $emailLabel }}
                        @if(filled($doc->client_email_error))
                            <div class="err">{{ \Illuminate\Support\Str::limit($doc->client_email_error, 120) }}</div>
                        @endif
                    </td>
                    <td class="num">{{ (int) $doc->client_email_attempts }}</td>
                    <td>{{ $efLabel }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">Nu s-au emis facturi/proforme din recurente în această zi.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
