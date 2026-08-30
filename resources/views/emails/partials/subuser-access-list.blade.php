@if(empty($accessSummary))
    <p style="margin:0 0 14px 0;color:#627d98;font-size:14px;">
        Momentan nu aveți societăți alocate. Veți primi acces după ce proprietarul setează firmele.
    </p>
@else
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 8px 0;">
        @foreach($accessSummary as $row)
            <tr>
                <td style="padding:12px 14px;border:1px solid #d9e2ec;border-radius:10px;margin-bottom:10px;background:#fafbfc;">
                    <div style="font-weight:700;color:#0a3440;margin-bottom:6px;">
                        {{ $row['company'] }}
                        @if(!empty($row['cui']))
                            <span style="font-weight:500;color:#627d98;font-size:12px;font-family:Consolas,'Courier New',monospace;"> · {{ $row['cui'] }}</span>
                        @endif
                    </div>
                    <ul style="margin:0;padding-left:18px;color:#334e68;font-size:13px;line-height:1.55;">
                        @foreach($row['rights'] as $right)
                            <li>{{ $right }}</li>
                        @endforeach
                    </ul>
                </td>
            </tr>
            <tr><td style="height:10px;font-size:0;line-height:0;">&nbsp;</td></tr>
        @endforeach
    </table>
@endif
