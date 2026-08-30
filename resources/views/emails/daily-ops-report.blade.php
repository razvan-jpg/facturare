@extends('emails.layouts.branded')

@php
    $dateLabel = $report['date']->format('d.m.Y');
    $t = $report['totals'];
    $v = $report['visitors'];
    $fmt = fn ($n) => number_format((float) $n, 2, ',', '.');
    $showPromise = false;
@endphp

@section('title', 'Raport DateConta Facturare pentru ziua '.$dateLabel)
@section('preheader', 'Recapitulare '.$dateLabel.': '.$t['documents'].' documente, '.$report['payments_count'].' încasări, '.$v['total'].' vizitatori')
@section('eyebrow', 'Raport zilnic platformă')
@section('headline', 'Raport DateConta Facturare pentru ziua '.$dateLabel)

@section('body')
    <p style="margin:0 0 14px 0;">
        Rezumat pentru <strong>toată platforma</strong> (toate societățile), ziua
        <strong>{{ $dateLabel }}</strong> (Europe/Bucharest).
        Raportul complet este atașat ca PDF
        (<code>raport-dateconta-{{ $report['date']->format('Y-m-d') }}.pdf</code>).
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 18px 0;border-collapse:collapse;">
        <tr>
            <td style="padding:10px 12px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:10px;">
                <div style="font-size:12px;color:#0369a1;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Documente emise</div>
                <div style="font-size:22px;font-weight:700;color:#0c4a6e;margin-top:4px;">{{ $t['documents'] }}</div>
                <div style="font-size:12px;color:#334e68;margin-top:4px;">
                    Manuale {{ $t['manual'] }} · Recurente {{ $t['recurring'] }} ·
                    Facturi {{ $t['invoices'] }} · Proforme {{ $t['proformas'] }}
                </div>
            </td>
        </tr>
        <tr><td style="height:10px;font-size:0;line-height:0;">&nbsp;</td></tr>
        <tr>
            <td style="padding:10px 12px;background:#f0fdfa;border:1px solid #99f6e4;border-radius:10px;">
                <div style="font-size:12px;color:#0f766e;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Încasări</div>
                <div style="font-size:22px;font-weight:700;color:#115e59;margin-top:4px;">{{ $fmt($report['payments_total']) }} RON</div>
                <div style="font-size:12px;color:#334e68;margin-top:4px;">{{ $report['payments_count'] }} înregistrări</div>
            </td>
        </tr>
        <tr><td style="height:10px;font-size:0;line-height:0;">&nbsp;</td></tr>
        <tr>
            <td style="padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;">
                <div style="font-size:12px;color:#475569;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">Vizitatori site</div>
                <div style="font-size:14px;color:#0f172a;margin-top:6px;line-height:1.5;">
                    <strong>{{ $v['new'] }}</strong> noi ·
                    <strong>{{ $v['returning'] }}</strong> reveniți ·
                    <strong>{{ $v['total'] }}</strong> totali
                    <span style="color:#64748b;">(≈ {{ number_format($v['page_views'], 0, ',', '.') }} pageviews)</span>
                </div>
            </td>
        </tr>
        <tr><td style="height:10px;font-size:0;line-height:0;">&nbsp;</td></tr>
        <tr>
            <td style="padding:10px 12px;background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;">
                <div style="font-size:12px;color:#c2410c;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">e-Factura (facturi emise azi)</div>
                @php $ef = $report['efactura']; @endphp
                <div style="font-size:14px;color:#0f172a;margin-top:6px;line-height:1.55;">
                    Eligible: <strong>{{ $ef['eligible'] }}</strong><br>
                    Trimise în e-Factura: <strong>{{ $ef['sent'] }}</strong>
                    · OK: <strong style="color:#047857;">{{ $ef['ok'] }}</strong>
                    · Cu eroare: <strong style="color:#b91c1c;">{{ $ef['errors'] }}</strong>
                    @if($ef['pending'] > 0)
                        · În curs: <strong>{{ $ef['pending'] }}</strong>
                    @endif
                    @if($ef['none'] > 0)
                        · Netremise: <strong>{{ $ef['none'] }}</strong>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    @if($report['efactura']['errors'] > 0)
        <h3 style="margin:0 0 8px 0;font-size:15px;color:#b91c1c;">Erori / respingeri e-Factura</h3>
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 16px 0;border-collapse:collapse;font-size:12px;">
            <tr style="background:#fef2f2;color:#7f1d1d;">
                <td style="padding:6px 8px;border-bottom:1px solid #fecaca;"><strong>Societate</strong></td>
                <td style="padding:6px 8px;border-bottom:1px solid #fecaca;"><strong>Factură</strong></td>
                <td style="padding:6px 8px;border-bottom:1px solid #fecaca;"><strong>Status</strong></td>
                <td style="padding:6px 8px;border-bottom:1px solid #fecaca;"><strong>Motiv</strong></td>
            </tr>
            @foreach($report['efactura']['error_rows'] as $err)
                <tr>
                    <td style="padding:6px 8px;border-bottom:1px solid #fee2e2;color:#334155;vertical-align:top;">
                        {{ $err->company_name }}
                        @if($err->company_cui)<br><span style="color:#94a3b8;">CUI {{ $err->company_cui }}</span>@endif
                    </td>
                    <td style="padding:6px 8px;border-bottom:1px solid #fee2e2;color:#0f172a;vertical-align:top;">
                        {{ $err->number_full }}
                        <div style="color:#94a3b8;font-size:11px;">{{ $err->client_name }}</div>
                    </td>
                    <td style="padding:6px 8px;border-bottom:1px solid #fee2e2;color:#b91c1c;vertical-align:top;text-transform:uppercase;">{{ $err->status }}</td>
                    <td style="padding:6px 8px;border-bottom:1px solid #fee2e2;color:#7f1d1d;vertical-align:top;">{{ $err->reason }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h3 style="margin:0 0 8px 0;font-size:15px;color:#0f172a;">Documente manuale (utilizator → societate)</h3>
    @if($report['documents_by_user']->isEmpty())
        <p style="margin:0 0 16px 0;color:#64748b;font-size:13px;">Nicio emitere manuală azi.</p>
    @else
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 16px 0;border-collapse:collapse;font-size:12px;">
            <tr style="background:#f1f5f9;color:#475569;">
                <td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;"><strong>Utilizator</strong></td>
                <td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;"><strong>Societate</strong></td>
                <td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;text-align:right;"><strong>Doc.</strong></td>
                <td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;text-align:right;"><strong>Total</strong></td>
            </tr>
            @foreach($report['documents_by_user'] as $row)
                <tr>
                    <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;color:#334155;">
                        {{ $row->user_name }}<br>
                        <span style="color:#94a3b8;">{{ $row->user_email }}</span>
                    </td>
                    <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;color:#334155;">
                        {{ $row->company_name }}
                        @if($row->company_cui)<br><span style="color:#94a3b8;">CUI {{ $row->company_cui }}</span>@endif
                    </td>
                    <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;text-align:right;color:#0f172a;">
                        {{ $row->count }}
                        <div style="color:#94a3b8;font-size:11px;">F {{ $row->invoices }} · P {{ $row->proformas }}</div>
                    </td>
                    <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;text-align:right;color:#0f172a;">{{ $fmt($row->total_ron) }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h3 style="margin:0 0 8px 0;font-size:15px;color:#0f172a;">Recurente emise (pe societate)</h3>
    @if($report['recurring_by_company']->isEmpty())
        <p style="margin:0 0 16px 0;color:#64748b;font-size:13px;">Nicio emitere recurentă azi.</p>
    @else
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 16px 0;border-collapse:collapse;font-size:12px;">
            <tr style="background:#f1f5f9;color:#475569;">
                <td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;"><strong>Societate</strong></td>
                <td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;text-align:right;"><strong>Doc.</strong></td>
                <td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;text-align:right;"><strong>Total</strong></td>
            </tr>
            @foreach($report['recurring_by_company'] as $row)
                <tr>
                    <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;color:#334155;">
                        {{ $row->company_name }}
                        @if($row->company_cui)<br><span style="color:#94a3b8;">CUI {{ $row->company_cui }}</span>@endif
                    </td>
                    <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;text-align:right;color:#0f172a;">
                        {{ $row->count }}
                        <div style="color:#94a3b8;font-size:11px;">F {{ $row->invoices }} · P {{ $row->proformas }}</div>
                    </td>
                    <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;text-align:right;color:#0f172a;">{{ $fmt($row->total_ron) }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h3 style="margin:0 0 8px 0;font-size:15px;color:#0f172a;">Încasări (pe societate)</h3>
    @if($report['payments_by_company']->isEmpty())
        <p style="margin:0 0 16px 0;color:#64748b;font-size:13px;">Nicio încasare înregistrată azi.</p>
    @else
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 16px 0;border-collapse:collapse;font-size:12px;">
            <tr style="background:#f1f5f9;color:#475569;">
                <td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;"><strong>Societate</strong></td>
                <td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;text-align:right;"><strong>Nr.</strong></td>
                <td style="padding:6px 8px;border-bottom:1px solid #e2e8f0;text-align:right;"><strong>Sumă</strong></td>
            </tr>
            @foreach($report['payments_by_company'] as $row)
                <tr>
                    <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;color:#334155;">
                        {{ $row->company_name }}
                        @if($row->company_cui)<br><span style="color:#94a3b8;">CUI {{ $row->company_cui }}</span>@endif
                    </td>
                    <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;text-align:right;color:#0f172a;">{{ $row->count }}</td>
                    <td style="padding:6px 8px;border-bottom:1px solid #f1f5f9;text-align:right;color:#0f172a;">{{ $fmt($row->amount) }} RON</td>
                </tr>
            @endforeach
        </table>
    @endif

    <h3 style="margin:0 0 8px 0;font-size:15px;color:#0f172a;">Conturi &amp; societăți noi</h3>
    <p style="margin:0 0 8px 0;font-size:13px;color:#334e68;">
        Utilizatori noi: <strong>{{ $report['new_users']->count() }}</strong> ·
        Societăți noi: <strong>{{ $report['new_companies']->count() }}</strong>
    </p>
    @if($report['new_users']->isNotEmpty())
        <ul style="margin:0 0 12px 18px;padding:0;color:#334e68;font-size:12px;">
            @foreach($report['new_users'] as $u)
                <li>{{ $u->name }} &lt;{{ $u->email }}&gt;</li>
            @endforeach
        </ul>
    @endif
    @if($report['new_companies']->isNotEmpty())
        <ul style="margin:0 0 4px 18px;padding:0;color:#334e68;font-size:12px;">
            @foreach($report['new_companies'] as $c)
                <li>{{ $c->name }}@if($c->cui) (CUI {{ $c->cui }})@endif</li>
            @endforeach
        </ul>
    @endif
@endsection

@section('footer')
    Raport automat zilnic · 23:55 Europe/Bucharest · DateConta Facturare
@endsection
