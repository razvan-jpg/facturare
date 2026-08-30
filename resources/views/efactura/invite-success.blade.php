<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SPV autorizat — DateConta Facturare</title>
    @include('partials.favicon')
    <style>
        body{font-family:DM Sans,system-ui,sans-serif;background:#0b3a45;color:#fff;margin:0;min-height:100vh;display:grid;place-items:center;padding:24px}
        .card{background:#fff;color:#0f172a;max-width:480px;padding:32px;border-radius:16px;text-align:center}
        img{width:88px;height:88px;border-radius:20px;object-fit:cover;margin:0 auto 16px}
        h1{margin:0 0 12px;font-size:1.5rem;font-family:Georgia,serif}
        p{line-height:1.5;color:#475569}
        a{color:#0F4C5C;font-weight:600}
    </style>
</head>
<body>
<div class="card">
    <img src="{{ asset('images/brand/dateconta-logo.png') }}" alt="DateConta Facturare">
    <h1>SPV autorizat</h1>
    <p>Autorizarea e-Factura pentru <strong>{{ $company->name }}</strong> a fost salvată. Facturile pot fi trimise acum din DateConta Facturare.</p>
    @auth
        <p><a href="{{ route('companies.edit', $company) }}">Înapoi la setările societății</a></p>
    @else
        <p><a href="{{ route('login') }}">Autentifică-te în aplicație</a></p>
    @endauth
</div>
</body>
</html>
