@php
    $registerUrl = $registerUrl ?? (rtrim(config('app.url'), '/').'/register');
@endphp
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 0 0;">
    <tr>
        <td style="padding:0 0 16px 0;">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#fff7ed;border:2px solid #f59e0b;border-radius:14px;">
                <tr>
                    <td style="padding:16px 18px;text-align:center;">
                        <div style="font-size:11px;font-weight:800;letter-spacing:2px;text-transform:uppercase;color:#b45309;margin-bottom:8px;">
                            Promisiunea noastră
                        </div>
                        <div style="font-size:16px;line-height:1.35;font-weight:800;color:#7c2d12;font-family:Georgia,'Times New Roman',serif;">
                            Să devenim cel mai bun și cel mai ieftin soft de facturare de pe piață!!!
                        </div>
                        <div style="margin-top:10px;font-size:13px;line-height:1.45;color:#9a3412;">
                            După perioada de grație: abonamente începând de la
                            <strong style="color:#7c2d12;font-size:15px;">1,99 EUR / lună + TVA</strong>
                        </div>
                        <div style="margin-top:12px;">
                            <a href="{{ $registerUrl }}" style="display:inline-block;background:#e08a1e;color:#1a1205;text-decoration:none;font-weight:800;font-size:12px;padding:10px 16px;border-radius:8px;">
                                Începe gratuit acum
                            </a>
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
