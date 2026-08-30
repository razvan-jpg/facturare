@extends('emails.layouts.branded')

@php
    $dateLabel = $date->format('d.m.Y');
    $count = $failures->count();
@endphp

@section('title', 'Alertă email recurente '.$dateLabel)
@section('preheader', $count.' documente recurente fără email confirmat pe '.$dateLabel)
@section('eyebrow', 'Alertă email')
@section('headline', 'Emailuri recurente netrimise — '.$dateLabel)

@section('body')
    <p style="margin:0 0 12px 0;">
        După fereastra de emitere, următoarele documente recurente <strong>nu au email confirmat</strong>
        către beneficiar. Se reîncearcă trimiterea (max. 3×), apoi urmează raportul zilnic.
    </p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:13px;color:#334e68;">
        <thead>
            <tr>
                <th align="left" style="padding:6px 4px;border-bottom:1px solid #d9e2ec;">Document</th>
                <th align="left" style="padding:6px 4px;border-bottom:1px solid #d9e2ec;">Societate</th>
                <th align="left" style="padding:6px 4px;border-bottom:1px solid #d9e2ec;">Cauză</th>
            </tr>
        </thead>
        <tbody>
            @foreach($failures as $row)
                <tr>
                    <td style="padding:6px 4px;border-bottom:1px solid #f0f4f8;vertical-align:top;">
                        {{ $row['number'] }}<br>
                        <span style="color:#627d98;font-size:12px;">{{ $row['client'] }}</span>
                    </td>
                    <td style="padding:6px 4px;border-bottom:1px solid #f0f4f8;vertical-align:top;">{{ $row['company'] }}</td>
                    <td style="padding:6px 4px;border-bottom:1px solid #f0f4f8;vertical-align:top;color:#9b2226;">{{ $row['cause'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection

@section('footer')
    Alertă automată DateConta Facturare · {{ config('dateconta.recurring_email_alert_to', 'razvan@dateconta.ro') }}
@endsection
