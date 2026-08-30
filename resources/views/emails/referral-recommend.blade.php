@extends('emails.layouts.branded')

@section('title', __('Recomandare DateConta Facturare'))
@section('preheader', __('Îți recomand călduros DateConta Facturare. Folosește codul :code la înregistrare — câștigăm amândoi perioadă promoțională.', ['code' => $promoCode]))
@section('eyebrow', __('Recomandare personală'))

@section('headline')
    {!! __('Îți recomand călduros<br><span style="color:#ffb84d;">DateConta Facturare</span>') !!}
@endsection

@section('body')
@php
    $senderName = trim((string) ($sender->name ?? '')) ?: __('Un coleg');
    $companyName = trim((string) ($company->name ?? '')) ?: __('firma mea');
    $until = \Illuminate\Support\Carbon::parse($promoFreeUntil)->format('d.m.Y');
@endphp
    <p style="margin:0 0 14px 0;">
        {{ __('Salut,') }}
    </p>
    <p style="margin:0 0 14px 0;">
        {!! __('Sunt <strong style="color:#0a3440;">:sender</strong> de la <strong style="color:#0a3440;">:company</strong>. Îți recomand călduros <strong>DateConta Facturare</strong> — emitem facturi, proforme, avize și chitanțe online, cu e-Factura ANAF, PDF și urmărirea încasărilor. Simplu, clar, făcut pentru firme din România.', [
            'sender' => e($senderName),
            'company' => e($companyName),
        ]) !!}
    </p>
    <p style="margin:0 0 18px 0;">
        {!! __('Platforma e <strong>gratuită până la :date</strong>. După aceea rămâne accesibilă, cu planuri de la 1,99&nbsp;EUR/lună + TVA.', [
            'date' => e($until),
        ]) !!}
    </p>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 22px 0;">
        <tr>
            <td style="background:#0a3440;border:3px solid #e08a1e;border-radius:16px;padding:22px 18px;text-align:center;">
                <div style="font-size:12px;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:#ffd089;margin-bottom:10px;">
                    {{ __('Codul tău promoțional') }}
                </div>
                <div style="font-family:Consolas,'Courier New',monospace;font-size:28px;line-height:1.2;font-weight:800;letter-spacing:0.12em;color:#ffffff;">
                    {{ $promoCode }}
                </div>
                <div style="margin-top:14px;font-size:14px;line-height:1.45;color:#d7f5ee;">
                    {!! __('<strong style="color:#fff;">Sfat:</strong> la înregistrare / la crearea societății folosește acest cod și vom avea <strong style="color:#fff;">amândoi de câștigat</strong> perioade promoționale gratuite.') !!}
                </div>
            </td>
        </tr>
    </table>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f6f1e8;border-radius:12px;margin:0 0 22px 0;">
        <tr>
            <td style="padding:18px 20px;font-size:14px;line-height:1.6;color:#334e68;">
                <strong style="color:#0a3440;">{{ __('Cum folosești codul:') }}</strong><br>
                1. {{ __('Creează-ți contul pe DateConta Facturare') }}<br>
                2. {{ __('La „Adaugă societate”, bifează că ai un cod promoțional') }}<br>
                3. {!! __('Introdu <strong style="font-family:Consolas,\'Courier New\',monospace;">:code</strong> și salvează', ['code' => e($promoCode)]) !!}<br><br>
                <strong style="color:#0a3440;">{{ __('Ce câștigăm:') }}</strong><br>
                ✓ {!! __('Tu primești <strong>+:days zile</strong> (2 săptămâni) la acces', ['days' => (int) $inviteeBonusDays]) !!}<br>
                ✓ {!! __('Eu primesc <strong>+:months lună</strong> la fiecare :every societăți aduse cu acest cod', [
                    'months' => (int) $referrerBonusMonths,
                    'every' => (int) $referrerEvery,
                ]) !!}
            </td>
        </tr>
    </table>

    <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 auto 14px auto;">
        <tr>
            <td align="center" bgcolor="#e08a1e" style="border-radius:10px;">
                <a href="{{ $registerUrl }}" style="display:inline-block;padding:16px 28px;color:#1a1205;text-decoration:none;font-weight:800;font-size:16px;">
                    {{ __('CREEAZĂ CONTUL ȘI FOLOSEȘTE CODUL') }}
                </a>
            </td>
        </tr>
    </table>
    <p style="margin:0 0 8px 0;text-align:center;font-size:12px;color:#627d98;">
        {{ __('Link:') }} <a href="{{ $registerUrl }}" style="color:#0f4c5c;">{{ $registerUrl }}</a>
    </p>
    <p style="margin:18px 0 0 0;font-size:14px;color:#334e68;">
        {{ __('Dacă ai întrebări, răspunde direct la acest email — ajunge la mine.') }}
    </p>
    <p style="margin:12px 0 0 0;font-size:14px;color:#0a3440;">
        {{ __('Cu drag,') }}<br>
        <strong>{{ $senderName }}</strong><br>
        <span style="color:#627d98;">{{ $companyName }}</span>
    </p>
@endsection

@section('footer')
@php
    $contact = config('dateconta.contact_email');
@endphp
    {{ __('Recomandare trimisă de :company prin DateConta Facturare.', ['company' => $company->name]) }}<br>
    {{ __('Contact platformă:') }} <a href="mailto:{{ $contact }}" style="color:#ffd089;">{{ $contact }}</a>
@endsection

@section('legal')
    {{ __('Primești acest mesaj pentru că :sender de la :company ți-a trimis o recomandare. Dacă nu e relevant, ignoră-l.', [
        'sender' => $sender->name ?: __('cineva'),
        'company' => $company->name,
    ]) }}
@endsection
