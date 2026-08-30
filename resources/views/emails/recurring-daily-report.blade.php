@extends('emails.layouts.branded')

@php
    $dateLabel = $report['date']->format('d.m.Y');
    $grand = (int) ($report['grand_total'] ?? 0);
    $inv = (int) ($report['totals']['invoice'] ?? 0);
    $pro = (int) ($report['totals']['proforma'] ?? 0);
    $emailSent = (int) ($report['email_totals']['sent'] ?? 0);
    $emailFailed = (int) ($report['email_totals']['failed'] ?? 0);
    $platformCc = $report['platform_cc'] ?? 'facturare@fly-david.ro';
@endphp

@section('title', 'Raport emitere recurente '.$dateLabel)
@section('preheader', $grand.' documente recurente emise pe '.$dateLabel)
@section('eyebrow', 'Raport zilnic')
@section('headline', 'Emitere documente recurente — '.$dateLabel)

@section('body')
    <p style="margin:0 0 12px 0;">
        Rezumat pentru <strong>toate societățile</strong> din DateConta Facturare, documente generate din abonamente recurente
        cu data emiterii <strong>{{ $dateLabel }}</strong>.
        Emailurile către beneficiari folosesc CC <strong>{{ $platformCc }}</strong>.
    </p>
    <ul style="margin:0 0 16px 18px;padding:0;color:#334e68;">
        <li>Total documente: <strong>{{ $grand }}</strong></li>
        <li>Facturi: <strong>{{ $inv }}</strong> · Proforme: <strong>{{ $pro }}</strong></li>
        <li>Email beneficiar: <strong>{{ $emailSent }}</strong> trimise
            @if($emailFailed > 0)
                · <strong style="color:#9b2226;">{{ $emailFailed }} eșuate</strong>
            @endif
        </li>
    </ul>
    <p style="margin:0 0 8px 0;font-size:13px;color:#627d98;">
        În PDF: agregare e-Factura + detaliu pe document (To, Cc, status email, încercări, erori).
    </p>
@endsection

@section('footer')
    Raport automat după verificare/retry email · emitere 04:00–10:00 · finalize ~10:25 (Europe/Bucharest).
@endsection
