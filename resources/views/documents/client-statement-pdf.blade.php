<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="utf-8">
<style>
body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #102a43; }
h1 { color: {{ $company->invoice_color ?: '#0F4C5C' }}; font-size: 20px; margin: 0 0 6px; }
.muted { color: #627d98; }
table { width: 100%; border-collapse: collapse; margin-top: 16px; }
th { background: #f0f4f8; text-align: left; padding: 8px; font-size: 11px; text-transform: uppercase; }
td { padding: 8px; border-top: 1px solid #d9e2ec; }
.overdue { color: #9a3412; font-weight: bold; }
.unbilled { color: #b91c1c; font-weight: bold; }
.totals { margin-top: 16px; text-align: right; font-size: 13px; line-height: 1.7; }
.totals .grand { font-size: 15px; margin-top: 4px; }
.header td { border: none; vertical-align: top; }
h2 { color: {{ $company->invoice_color ?: '#0F4C5C' }}; font-size: 14px; margin: 22px 0 0; }
</style>
</head>
<body>
<table class="header" width="100%">
<tr>
<td>
    <h1>Fișă client</h1>
    <div class="muted">Generată: {{ dc_datetime(now()) }}</div>
</td>
<td style="text-align:right">
    <strong>{{ $company->name }}</strong><br>
    CUI {{ $company->cui }} · {{ $company->reg_com }}<br>
    {{ $company->fullAddress() }}
</td>
</tr>
</table>

<p style="margin-top:18px">
    <strong>Client:</strong> {{ $client->name }}<br>
    @if($client->cui)
        CUI {{ $client->cui }} ·
    @endif
    {{ $client->reg_com }}
    @if($client->cnp)
        <br>CNP {{ $client->cnp }}
    @endif
    <br>
    {{ $client->fullAddress() }}<br>
    @if($client->email)
        Email: {{ $client->email }}
    @endif
</p>

<table>
<thead>
<tr>
    <th>{{ __('Factură') }}</th>
    <th>Emitere</th>
    <th>{{ __('Scadență') }}</th>
    <th>{{ __('Total') }}</th>
    <th>Achitat</th>
    <th>{{ __('Rest') }}</th>
    <th>{{ __('Status') }}</th>
</tr>
</thead>
<tbody>
@forelse($invoices as $invoice)
@php
    $isOverdue = in_array($invoice->id, $overdueIds ?? [], true);
@endphp
<tr>
    <td>{{ $invoice->number_full }}</td>
    <td>{{ dc_date($invoice->issue_date) }}</td>
    <td class="{{ $isOverdue ? 'overdue' : '' }}">{{ dc_date($invoice->due_date) }}</td>
    <td>{{ number_format((float) $invoice->total, 2, ',', '.') }}</td>
    <td>{{ number_format((float) $invoice->paid_amount, 2, ',', '.') }}</td>
    <td><strong>{{ number_format($invoice->remainingAmount(), 2, ',', '.') }}</strong></td>
    <td class="{{ $isOverdue ? 'overdue' : '' }}">{{ $isOverdue ? 'RESTANTĂ' : $invoice->paymentStatusLabel() }}</td>
</tr>
@empty
<tr><td colspan="7" class="muted">Nicio factură deschisă.</td></tr>
@endforelse
</tbody>
</table>

@php
    $opening = (float) ($openingBalance ?? $client->opening_balance ?? 0);
    $openRem = (float) ($openRemaining ?? 0);
    if (! isset($openRemaining)) {
        $openRem = 0.0;
        foreach ($invoices as $doc) {
            $openRem += $doc->remainingAmount();
        }
        $openRem = round($openRem, 2);
    }
    $totalBal = (float) ($balance ?? ($opening + $openRem));
    $openingDate = $openingBalanceDate ?? $client->opening_balance_date ?? null;
    $openingLabel = $openingDate ? ('Sold inițial ('.dc_date($openingDate).'):') : 'Sold inițial:';
@endphp
<div class="totals">
    {{ $openingLabel }}
    <strong>{{ number_format($opening, 2, ',', '.') }} RON</strong><br>
    Sold facturi deschise: <strong>{{ number_format($openRem, 2, ',', '.') }} RON</strong><br>
    <div class="grand">Sold total: <strong>{{ number_format($totalBal, 2, ',', '.') }} RON</strong></div>
</div>

@php
    $penaltyRows = $penaltyRows ?? [];
    $ps = $penaltySummary ?? ['accrued' => 0, 'billed' => 0, 'paid' => 0];
@endphp
@if(count($penaltyRows) > 0)
    <h2>Penalități cf. contract</h2>
    <p class="muted" style="margin:4px 0 0">
        Nefacturate: <span class="unbilled">{{ number_format((float) ($ps['accrued'] ?? 0), 2, ',', '.') }} RON</span>
        · Facturate neîncasate: {{ number_format((float) ($ps['billed'] ?? 0), 2, ',', '.') }} RON
        · Încasate: {{ number_format((float) ($ps['paid'] ?? 0), 2, ',', '.') }} RON
    </p>
    <table>
        <thead>
        <tr>
            <th>Detaliu</th>
            <th>Zile</th>
            <th>Sumă</th>
            <th>Status / factură</th>
        </tr>
        </thead>
        <tbody>
        @foreach($penaltyRows as $row)
            <tr>
                <td class="{{ ! empty($row['is_unbilled']) ? 'unbilled' : '' }}">{{ $row['label'] }}</td>
                <td>{{ (int) ($row['days'] ?? 0) }}</td>
                <td class="{{ ! empty($row['is_unbilled']) ? 'unbilled' : '' }}">
                    {{ number_format((float) ($row['amount'] ?? 0), 2, ',', '.') }}
                </td>
                <td class="{{ ! empty($row['is_unbilled']) ? 'unbilled' : '' }}">
                    @if(! empty($row['is_unbilled']))
                        NEFACTURATE
                    @elseif(! empty($row['billed_document_number']))
                        {{ $row['status_label'] }} pe {{ $row['billed_document_number'] }}
                    @else
                        {{ $row['status_label'] }}
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

<p class="muted" style="margin-top:28px;font-size:10px"><a href="{{ rtrim((string) config('app.url'), '/') }}/" class="footer-brand-link" style="color:inherit;text-decoration:underline">Document generat cu DateConta Facturare</a></p>
</body>
</html>
