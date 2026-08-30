@extends('emails.layouts.branded')

@section('title', 'Cont creat — DateConta Facturare')
@section('preheader', $creator->name.' v-a creat ca utilizator în DateConta Facturare.')
@section('eyebrow', 'Cont nou')

@section('headline')
    Bun venit în<br>
    <span style="color:#ffb84d;">DateConta Facturare</span>
@endsection

@section('body')
@php
    $creatorName = trim((string) $creator->name) ?: 'Un utilizator';
    $companyName = trim((string) $creatorCompanyName) ?: 'societatea sa';
@endphp
    <p style="margin:0 0 14px 0;">Salut{{ filled($recipient->name) ? ', '.$recipient->name : '' }},</p>
    <p style="margin:0 0 14px 0;">
        <strong style="color:#0a3440;">{{ $creatorName }}</strong> de la
        <strong style="color:#0a3440;">{{ $companyName }}</strong>
        v-a creat ca utilizator al aplicației <strong>DateConta Facturare</strong>.
    </p>
    <p style="margin:0 0 14px 0;">
        Vă puteți autentifica la adresa
        <a href="{{ $loginUrl }}" style="color:#0f4c5c;font-weight:700;">{{ $loginUrl }}</a>
        cu următoarele date:
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 22px 0;background:#f6f1e8;border-radius:12px;">
        <tr>
            <td style="padding:18px 20px;font-size:14px;line-height:1.7;color:#334e68;">
                <strong style="color:#0a3440;">Email (utilizator):</strong>
                <span style="font-family:Consolas,'Courier New',monospace;">{{ $recipient->email }}</span><br>
                <strong style="color:#0a3440;">Parolă:</strong>
                <span style="font-family:Consolas,'Courier New',monospace;">{{ $plainPassword }}</span>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 10px 0;font-size:13px;color:#627d98;">
        Vă recomandăm să schimbați parola după prima autentificare, din <strong>Contul meu</strong>.
    </p>

    <p style="margin:18px 0 10px 0;">
        Aveți acces la următoarele societăți, cu drepturile aferente fiecăreia:
    </p>
    @include('emails.partials.subuser-access-list', ['accessSummary' => $accessSummary])

    <p style="margin:18px 0 0 0;">
        Din aplicație puteți emite documente, gestiona clienți și produse și urmări încasările —
        în limita drepturilor alocate. Dacă ceva nu e clar, contactați-l pe
        <strong>{{ $creatorName }}</strong> sau scrieți-ne la
        <a href="mailto:{{ config('dateconta.contact_email') }}" style="color:#0f4c5c;">{{ config('dateconta.contact_email') }}</a>.
    </p>

    <p style="margin:22px 0 0 0;font-size:15px;color:#0a3440;">
        Cu drag,<br>
        <strong>Echipa DateConta</strong>
    </p>
@endsection
