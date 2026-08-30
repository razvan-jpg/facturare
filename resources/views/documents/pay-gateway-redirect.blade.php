<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Redirecționare plată…' }}</title>
    <style>
        body { font-family: system-ui, sans-serif; display: grid; place-items: center; min-height: 100vh; margin: 0; background: #f8fafc; color: #0f172a; }
        .box { text-align: center; padding: 1.5rem; }
        button { margin-top: 1rem; padding: 0.65rem 1.25rem; background: #0f766e; color: #fff; border: 0; border-radius: 0.45rem; font-weight: 600; cursor: pointer; }
        a { color: #0f766e; font-weight: 600; }
    </style>
</head>
<body>
    <div class="box">
        <p>{{ $message ?? 'Te redirecționăm către plata cu cardul…' }}</p>
        @if(($type ?? 'form') === 'redirect')
            <p><a href="{{ $checkoutUrl }}">{{ __('Continuă către plată') }}</a></p>
            <script>window.location.replace(@json($checkoutUrl));</script>
        @else
            <form id="pay-form" method="POST" action="{{ $form['url'] }}" accept-charset="UTF-8">
                @if(! empty($form['fields']) && is_array($form['fields']))
                    @foreach($form['fields'] as $name => $value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach
                @else
                    <input type="hidden" name="env_key" value="{{ $form['env_key'] }}">
                    <input type="hidden" name="data" value="{{ $form['data'] }}">
                    <input type="hidden" name="cipher" value="{{ $form['cipher'] }}">
                    <input type="hidden" name="iv" value="{{ $form['iv'] }}">
                @endif
                <button type="submit">{{ __('Continuă către plată') }}</button>
            </form>
            <script>setTimeout(function () { document.getElementById('pay-form').submit(); }, 120);</script>
        @endif
    </div>
</body>
</html>
