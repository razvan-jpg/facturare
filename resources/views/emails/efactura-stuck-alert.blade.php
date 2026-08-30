@extends('emails.layouts.branded')

@php
    $count = $documents->count();
@endphp

@section('title', 'e-Factura documente blocate')
@section('preheader', $count.' documente e-Factura neacceptate după reîncercări automate')
@section('eyebrow', 'Alertă e-Factura')
@section('headline', 'Documente neacceptate de ANAF')

@section('body')
    <p style="margin:0 0 12px 0;">
        După reîncercări automate (corectare XML/date + retrimitere), următoarele documente
        <strong>nu sunt încă Acceptate ANAF</strong>. Verifică datele și corectează manual dacă e nevoie —
        sistemul va reîncerca din nou mâine.
    </p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:13px;color:#334e68;">
        <thead>
            <tr>
                <th align="left" style="padding:6px 4px;border-bottom:1px solid #d9e2ec;">Document</th>
                <th align="left" style="padding:6px 4px;border-bottom:1px solid #d9e2ec;">Societate</th>
                <th align="left" style="padding:6px 4px;border-bottom:1px solid #d9e2ec;">Status</th>
                <th align="left" style="padding:6px 4px;border-bottom:1px solid #d9e2ec;">Cauză</th>
            </tr>
        </thead>
        <tbody>
            @foreach($documents as $doc)
                <tr>
                    <td style="padding:6px 4px;border-bottom:1px solid #f0f4f8;vertical-align:top;">
                        {{ $doc->number_full ?: ($doc->series.'-'.$doc->number) }}
                    </td>
                    <td style="padding:6px 4px;border-bottom:1px solid #f0f4f8;vertical-align:top;">
                        {{ $doc->company?->name ?: '—' }}
                    </td>
                    <td style="padding:6px 4px;border-bottom:1px solid #f0f4f8;vertical-align:top;">
                        {{ $doc->efacturaStatusLabel() }}
                    </td>
                    <td style="padding:6px 4px;border-bottom:1px solid #f0f4f8;vertical-align:top;color:#9b2226;font-size:12px;">
                        {{ \Illuminate\Support\Str::limit((string) $doc->efactura_error, 180) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endsection

@section('footer')
    Alertă automată DateConta Facturare · e-Factura reconcile
@endsection
