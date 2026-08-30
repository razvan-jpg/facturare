@php
    $registerUrl = $registerUrl ?? (rtrim(config('app.url'), '/').'/register');
@endphp
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 0 0;">
    <tr>
        <td style="padding:0 0 16px 0;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#0a3440;border:2px solid #1f7a6c;border-radius:14px;">
                <tr>
                    <td style="padding:16px 18px;text-align:center;">
                        <div style="font-size:11px;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:#ffd089;margin-bottom:8px;">
                            Recomandă &amp; câștigă
                        </div>
                        <div style="font-size:16px;line-height:1.35;font-weight:800;color:#ffffff;font-family:Georgia,'Times New Roman',serif;">
                            Adu clienți noi cu codul tău promoțional
                        </div>
                        <div style="margin-top:10px;font-size:13px;line-height:1.45;color:#d7f5ee;">
                            Ei primesc <strong style="color:#fff;">+2 săptămâni</strong>.
                            Tu primești <strong style="color:#fff;">+1 lună</strong> la fiecare 2 societăți aduse.
                        </div>
                        <div style="margin-top:12px;">
                            <a href="{{ $registerUrl }}" style="display:inline-block;background:#e08a1e;color:#1a1205;text-decoration:none;font-weight:800;font-size:12px;padding:10px 16px;border-radius:8px;">
                                Începe și recomandă
                            </a>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
