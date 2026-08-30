<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>@yield('title', config('dateconta.brand_name', 'DateConta Facturare'))</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td { font-family: Arial, Helvetica, sans-serif !important; }
    </style>
    <![endif]-->
</head>
@php
    $logo = $logo ?? config('dateconta.logo_url');
    $brand = $brand ?? config('dateconta.brand_name', 'DateConta Facturare');
    $appUrl = rtrim((string) config('app.url'), '/');
    $contact = config('dateconta.contact_email', 'contact.facturare@dateconta.ro');
    $bg = $appUrl.'/images/brand/dateconta-logo.png';
    $showPromise = $showPromise ?? true;
@endphp
<body style="margin:0;padding:0;background-color:#041f27;font-family:Arial,Helvetica,sans-serif;color:#102a43;">
@hasSection('preheader')
<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#041f27;opacity:0;">
    @yield('preheader')
</div>
@endif
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" bgcolor="#041f27" style="background-color:#041f27;background-image:url('{{ $bg }}');background-repeat:repeat;background-size:140px 140px;">
    <tr>
        <td align="center" bgcolor="#041f27" style="padding:28px 12px;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;width:100%;">
                {{-- Header brand + logo watermark --}}
                <tr>
                    <td background="{{ $bg }}" bgcolor="#0a3440" style="background-color:#0a3440;background-image:url('{{ $bg }}');background-repeat:no-repeat;background-position:right -20px top -30px;background-size:260px auto;border-radius:18px 18px 0 0;padding:0;">
                        <!--[if gte mso 9]>
                        <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width:600px;">
                        <v:fill type="frame" src="{{ $bg }}" color="#0a3440" />
                        <v:textbox style="mso-fit-shape-to-text:true" inset="0,0,0,0">
                        <![endif]-->
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#0a3440cc;">
                            <tr>
                                <td align="center" style="padding:28px 24px 10px 24px;">
                                    <img src="{{ $logo }}" width="96" height="96" alt="{{ $brand }}" style="display:block;margin:0 auto;border-radius:22px;border:3px solid #ffd089;">
                                    <div style="font-family:Georgia,'Times New Roman',serif;font-size:30px;color:#ffffff;margin-top:14px;line-height:1;">DateConta</div>
                                    <div style="font-size:11px;letter-spacing:0.24em;text-transform:uppercase;color:#ffd089;margin-top:6px;">Facturare</div>
                                    @hasSection('eyebrow')
                                        <div style="font-size:12px;letter-spacing:1.6px;text-transform:uppercase;color:#ffd089;font-weight:700;margin-top:16px;">
                                            @yield('eyebrow')
                                        </div>
                                    @endif
                                    @hasSection('headline')
                                        <div style="font-size:26px;line-height:1.15;font-weight:700;margin-top:12px;font-family:Georgia,'Times New Roman',serif;color:#ffffff;">
                                            @yield('headline')
                                        </div>
                                    @endif
                                </td>
                            </tr>
                            @if($showPromise)
                            <tr>
                                <td style="padding:16px 20px 20px 20px;">
                                    @include('emails.partials.promise')
                                    @include('emails.partials.referral')
                                </td>
                            </tr>
                            @endif
                        </table>
                        <!--[if gte mso 9]>
                        </v:textbox></v:rect>
                        <![endif]-->
                    </td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td bgcolor="#ffffff" style="background:#ffffff;padding:28px;font-size:15px;line-height:1.55;color:#334e68;">
                        @yield('body')
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td bgcolor="#0f4c5c" style="background:#0f4c5c;border-radius:0 0 18px 18px;padding:20px 28px;color:#dceef2;font-size:12px;line-height:1.55;">
                        <table role="presentation" cellspacing="0" cellpadding="0">
                            <tr>
                                <td style="padding-right:12px;vertical-align:middle;">
                                    <img src="{{ $logo }}" width="40" height="40" alt="{{ $brand }}" style="display:block;border-radius:10px;border:0;">
                                </td>
                                <td style="vertical-align:middle;">
                                    <div style="font-family:Georgia,'Times New Roman',serif;font-size:18px;color:#ffffff;">{{ $brand }}</div>
                                </td>
                            </tr>
                        </table>
                        <div style="margin-top:10px;">
                            @hasSection('footer')
                                @yield('footer')
                            @else
                                Documente, e-Factura și încasări — dintr-un singur loc.<br>
                                <a href="{{ $appUrl }}" style="color:#ffd089;">{{ $appUrl }}</a>
                                · <a href="mailto:{{ $contact }}" style="color:#ffd089;">{{ $contact }}</a>
                            @endif
                        </div>
                    </td>
                </tr>

                <tr>
                    <td align="center" style="padding:16px 8px 0 8px;font-size:11px;line-height:1.4;color:#8aa4b5;">
                        @yield('legal', 'Mesaj trimis de DateConta Facturare. Dacă nu e relevant, îl puteți ignora.')
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
