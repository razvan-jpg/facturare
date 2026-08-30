<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="0;url={{ $checkoutUrl }}">
    <title>Redirecționare Mollie…</title>
    <style>
        body { font-family: system-ui, sans-serif; display: grid; place-items: center; min-height: 100vh; margin: 0; background: #f8fafc; color: #0f172a; }
        .box { text-align: center; padding: 1.5rem; }
        a { color: #0f766e; font-weight: 600; }
    </style>
</head>
<body>
    <div class="box">
        <p>Te redirecționăm către plata Mollie…</p>
        <p><a id="mollie-link" href="{{ $checkoutUrl }}">Continuă către Mollie</a></p>
    </div>
    <script>
        window.location.replace(@json($checkoutUrl));
    </script>
</body>
</html>
