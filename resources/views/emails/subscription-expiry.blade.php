@extends('emails.layouts.branded')

@section('title', 'Abonament care expiră — '.$brand)
@section('preheader', 'Accesul DateConta Facturare expiră pe '.dc_date($accessUntil))
@section('eyebrow', 'Notificare abonament')
@section('headline', 'Bună ziua, '.$user->name)

@section('body')
    <p style="margin:0 0 14px;">
        Îți reamintim că accesul la
        <strong style="color:#0a3440;">{{ $brand }}</strong>
        expiră
        <strong style="color:#0a3440;">
            @if($daysBefore === 1)
                mâine
            @else
                în {{ $daysBefore }} zile
            @endif
        </strong>
        — pe <strong>{{ dc_date($accessUntil) }}</strong>.
    </p>

    <div style="background:#fff7ed;border:1px solid #fdba74;border-radius:12px;padding:16px 18px;margin:0 0 18px;">
        <div style="font-size:12px;text-transform:uppercase;letter-spacing:1px;color:#9a3412;font-weight:700;">Expiră la</div>
        <div style="font-size:22px;font-weight:800;color:#7c2d12;margin-top:4px;">{{ dc_date($accessUntil) }}</div>
        <div style="font-size:13px;color:#9a3412;margin-top:4px;">
            Cont: {{ $user->email }}
        </div>
    </div>

    <p style="margin:0 0 14px;">
        Pentru a continua fără întrerupere, comandă un abonament —
        plată cu cardul (NETOPIA) sau prin OP.
    </p>

    <p style="margin:0 0 8px;">
        <a href="{{ $orderUrl }}" style="display:inline-block;background:#0f766e;color:#ffffff;text-decoration:none;font-weight:700;padding:12px 22px;border-radius:10px;">
            Comandă
        </a>
    </p>
    @if(!($hasCompany ?? false))
        <p style="margin:8px 0 0;font-size:12px;color:#627d98;">
            Dacă nu ai încă o societate, butonul te duce la Societățile mele.
        </p>
    @endif

    <p style="margin:18px 0 0;font-size:13px;color:#486581;">
        Întrebări: <a href="mailto:{{ $contact }}" style="color:#0f4c5c;">{{ $contact }}</a>
    </p>
@endsection
