<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Plată card — {{ $document->number_full }}</title>
    <style>
        :root { --teal: #0f766e; --ink: #0f172a; --muted: #475569; --line: #e2e8f0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "Segoe UI", system-ui, sans-serif; background: linear-gradient(160deg, #f0fdfa, #f8fafc 40%, #eef2ff); color: var(--ink); min-height: 100vh; }
        .wrap { max-width: 28rem; margin: 0 auto; padding: 2.5rem 1.25rem; }
        .card { background: #fff; border: 1px solid var(--line); border-radius: 1rem; padding: 1.5rem; box-shadow: 0 10px 30px rgba(15, 23, 42, .06); }
        h1 { font-size: 1.25rem; margin: 0 0 .35rem; }
        .meta { color: var(--muted); font-size: .9rem; line-height: 1.45; margin-bottom: 1.25rem; }
        .amount { font-size: 1.75rem; font-weight: 700; letter-spacing: -.02em; margin: .75rem 0 1.25rem; }
        .processors { display: grid; gap: .65rem; }
        a.proc { display: block; text-decoration: none; color: inherit; border: 1px solid var(--line); border-radius: .7rem; padding: .9rem 1rem; transition: border-color .15s, background .15s; }
        a.proc:hover { border-color: var(--teal); background: #f0fdfa; }
        a.proc strong { display: block; color: var(--teal); }
        a.proc span { font-size: .8rem; color: var(--muted); }
        .foot { margin-top: 1.25rem; font-size: .75rem; color: var(--muted); text-align: center; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Plată cu cardul</h1>
        <div class="meta">
            <div><strong>{{ $document->company->name }}</strong></div>
            <div>{{ $document->type === 'proforma' ? 'Proformă' : 'Factură' }} {{ $document->number_full }}</div>
            <div>{{ $document->client_name }}</div>
        </div>
        <div class="amount">{{ number_format($amount, 2, ',', '.') }} {{ $document->currency }}</div>

        @if(session('warning'))
            <p style="color:#b91c1c;font-size:.875rem;margin:0 0 1rem;">{{ session('warning') }}</p>
        @endif

        <div class="processors">
            @forelse($links as $link)
                <a class="proc" href="{{ $link['url'] }}">
                    <strong>Plătește cu {{ $link['short'] }}</strong>
                    <span>{{ $link['label'] }} — checkout securizat</span>
                </a>
            @empty
                <p style="color:#b45309;font-size:.875rem;margin:0;">Plata cu cardul nu este disponibilă momentan. Contactează emitentul facturii.</p>
            @endforelse
        </div>
        <p class="foot">Plată procesată în siguranță. Nu stocăm datele cardului pe acest site.</p>
    </div>
</div>
</body>
</html>
