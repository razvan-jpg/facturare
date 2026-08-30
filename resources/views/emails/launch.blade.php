@extends('emails.layouts.branded')

@section('title', 'DateConta Facturare — lansare')
@section('preheader', 'Gratuit până la 31.03.2027. Cel mai bun și cel mai ieftin soft de facturare — de la 1,99 EUR/lună + TVA după perioada de grație.')
@section('eyebrow', 'Lansare agresivă · locuri nelimitate acum')

@section('headline')
    Facturezi greu?<br>
    <span style="color:#ffb84d;">Atunci pierzi bani.</span>
@endsection

@section('body')
@php
    $appUrl = rtrim(config('app.url'), '/');
    $register = $appUrl.'/register';
@endphp
    <p style="margin:0 0 14px 0;">
        DateConta Facturare tocmai a fost lansat. Emite facturi, proforme, avize și chitanțe online —
        <strong style="color:#0a3440;">gratuit până la 31.03.2027</strong>.
        Fără card. Fără „încearcă 14 zile și gata”.
    </p>

    <div style="font-size:13px;font-weight:700;color:#b86a0a;text-transform:uppercase;letter-spacing:1px;margin:0 0 10px 0;">Adevărul direct</div>
    <p style="margin:0 0 14px 0;">
        Dacă încă facturezi din Word/Excel sau amâni documentele „pentru mâine”,
        clientul tău uită, plata întârzie, iar tu alergi după bani care erau ai tăi.
    </p>
    <p style="margin:0 0 18px 0;">
        DateConta Facturare taie scurt: creezi contul, adaugi societatea, emiti factura, o trimiți.
        Vezi ce e neplătit. Gata.
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f6f1e8;border-radius:12px;margin:0 0 22px 0;">
        <tr>
            <td style="padding:18px 20px;font-size:14px;line-height:1.6;color:#334e68;">
                <strong style="color:#0a3440;">Ce bagi în cont imediat:</strong><br>
                ✓ Facturi / proforme / avize / chitanțe<br>
                ✓ Multi-firmă + CUI din ANAF<br>
                ✓ PDF + email către client<br>
                ✓ Încasări + rapoarte<br>
                ✓ Acces 100% gratuit până pe 31.03.2027
            </td>
        </tr>
    </table>

    <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 auto 14px auto;">
        <tr>
            <td align="center" bgcolor="#e08a1e" style="border-radius:10px;">
                <a href="{{ $register }}" style="display:inline-block;padding:16px 28px;color:#1a1205;text-decoration:none;font-weight:800;font-size:16px;">
                    CREEAZĂ CONTUL GRATUIT ACUM
                </a>
            </td>
        </tr>
    </table>
    <p style="margin:0 0 18px 0;text-align:center;font-size:12px;color:#627d98;">
        Link: <a href="{{ $register }}" style="color:#0f4c5c;">{{ $register }}</a>
    </p>
    <p style="margin:0;font-size:14px;line-height:1.55;color:#334e68;">
        <strong style="color:#9a3412;">Atenție:</strong> după 1 aprilie 2027, noii utilizatori primesc doar 6 luni gratuite.
        Cine intră acum ia fereastra maximă. Nu „mai vedem”. Acum.
    </p>
@endsection

@section('footer')
@php
    $appUrl = rtrim(config('app.url'), '/');
    $launch = $appUrl.'/lansare';
    $contact = config('dateconta.contact_email');
@endphp
    Campanie: <a href="{{ $launch }}" style="color:#ffd089;">{{ $launch }}</a><br>
    Contact: <a href="mailto:{{ $contact }}" style="color:#ffd089;">{{ $contact }}</a><br>
    Operator: {{ config('dateconta.platform_operator.name') }} · CUI {{ config('dateconta.platform_operator.cui') }}
@endsection

@section('legal')
    Primești acest mesaj ca parte a lansării DateConta Facturare. Dacă nu e relevant, ignoră-l.
    Nu vindem liste și nu cerem date de card pentru perioada gratuită.
@endsection
