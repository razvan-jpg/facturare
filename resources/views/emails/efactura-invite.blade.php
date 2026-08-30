@extends('emails.layouts.branded')

@section('title', 'Autorizare e-Factura SPV — '.$company->name)
@section('preheader', 'Invitație autorizare SPV pentru '.$company->name)
@section('eyebrow', 'Invitație autorizare SPV')
@section('headline', 'Vă rugăm să autorizați firma în SPV ANAF')

@section('body')
    <p style="margin:0 0 14px 0;">
        Ați fost invitat să autorizați societatea
        <strong style="color:#0a3440;">{{ $company->name }}</strong>
        @if($company->cui)(CUI <strong>{{ $company->cui }}</strong>)@endif
        în Spațiul Privat Virtual ANAF, pentru trimiterea facturilor electronice din aplicația
        <strong>{{ config('dateconta.brand_name', 'DateConta Facturare') }}</strong>.
    </p>
    <p style="margin:0 0 22px 0;">
        Aveți nevoie de certificat digital înrolat în SPV, cu drepturi pe acest CUI.
        Autorizarea durează câteva minute și se face o singură dată.
    </p>
    <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 auto 18px auto;">
        <tr>
            <td align="center" bgcolor="#e08a1e" style="border-radius:10px;">
                <a href="{{ $url }}" style="display:inline-block;padding:16px 28px;color:#1a1205;text-decoration:none;font-weight:800;font-size:15px;">
                    Autorizează SPV acum
                </a>
            </td>
        </tr>
    </table>
    <p style="margin:0;font-size:12px;line-height:1.5;color:#829ab1;">
        Linkul expiră la <strong>{{ dc_datetime($invite->expires_at) }}</strong>.<br>
        Dacă butonul nu funcționează, copiați adresa:<br>
        <a href="{{ $url }}" style="color:#0f4c5c;word-break:break-all;">{{ $url }}</a>
    </p>
@endsection

@section('footer')
    Dacă nu vă așteptați la acest email, îl puteți ignora.<br>
    <a href="{{ rtrim(config('app.url'), '/') }}" style="color:#ffd089;">{{ rtrim(config('app.url'), '/') }}</a>
@endsection
