<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Plată — {{ $document->number_full }}</title>
    <style>
        body { margin: 0; font-family: system-ui, sans-serif; background: #f8fafc; color: #0f172a; min-height: 100vh; display: grid; place-items: center; }
        .box { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1.75rem; max-width: 26rem; text-align: center; box-shadow: 0 10px 30px rgba(15,23,42,.06); }
        h1 { font-size: 1.2rem; margin: 0 0 .5rem; }
        p { color: #475569; font-size: .95rem; line-height: 1.45; }
        .ok { color: #0f766e; font-weight: 700; }
    </style>
</head>
<body>
<div class="box">
    @if($alreadyPaid ?? false)
        <h1 class="ok">Plată înregistrată</h1>
        <p>Documentul <strong>{{ $document->number_full }}</strong> este achitat (sau plata a fost confirmată).</p>
    @elseif(($checkout->status ?? '') === 'failed')
        <h1>Plata nu a fost finalizată</h1>
        <p>Poți reîncerca din linkul de pe factură / proformă.</p>
    @else
        <h1>Mulțumim</h1>
        <p id="dc-pay-wait">Am înregistrat întoarcerea de la procesator. Verificăm confirmarea plății…</p>
    @endif
    <p style="margin-top:1.25rem;font-size:.85rem;">{{ $document->company->name ?? '' }} · {{ $document->number_full }}</p>
</div>
@if(! ($alreadyPaid ?? false) && ($checkout->status ?? '') !== 'failed')
<script>
(function () {
    var n = 0, max = 12;
    var t = setInterval(function () {
        n++;
        if (n >= max) {
            clearInterval(t);
            var el = document.getElementById('dc-pay-wait');
            if (el) el.textContent = 'Dacă ai plătit și documentul nu e încă achitat, reîncarcă pagina peste un minut.';
            return;
        }
        window.location.reload();
    }, 2500);
})();
</script>
@endif
</body>
</html>
