<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redirecționare Eu Plătesc…</title>
    <style>
        body { font-family: system-ui, sans-serif; display: grid; place-items: center; min-height: 100vh; margin: 0; background: #f8fafc; color: #0f172a; }
        .box { text-align: center; padding: 1.5rem; }
    </style>
</head>
<body>
    <div class="box">
        <p>Te redirecționăm către plata Eu Plătesc…</p>
    </div>
    <form id="ep-form" method="POST" action="{{ $form['url'] }}">
        @foreach($form['fields'] as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
    </form>
    <script>document.getElementById('ep-form').submit();</script>
</body>
</html>
