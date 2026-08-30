<label class="flex items-center gap-2 text-sm text-slate-800">
    <input type="checkbox" name="enabled" value="1" class="rounded border-slate-300"
           @checked(old('enabled', $integrations->getBool($company, 'stripe', 'enabled')))>
    Activează Stripe pentru plata facturilor
</label>

<div>
    <label class="dc-label" for="key">Publishable key</label>
    <input id="key" name="key" class="dc-input font-mono text-sm"
           value="{{ old('key', $integrations->get($company, 'stripe', 'key', '')) }}"
           placeholder="pk_live_… sau pk_test_…" autocomplete="off">
</div>

<div>
    <label class="dc-label" for="secret">Secret key</label>
    <input id="secret" name="secret" class="dc-input font-mono text-sm" value="{{ old('secret') }}"
           placeholder="{{ filled($integrations->get($company, 'stripe', 'secret')) ? '•••••••• (lasă gol ca să păstrezi cheia)' : 'sk_live_… sau sk_test_…' }}"
           autocomplete="off">
</div>

<div>
    <label class="dc-label" for="webhook_secret">Webhook signing secret (opțional)</label>
    <input id="webhook_secret" name="webhook_secret" class="dc-input font-mono text-sm" value="{{ old('webhook_secret') }}"
           placeholder="{{ filled($integrations->get($company, 'stripe', 'webhook_secret')) ? '•••••••• (lasă gol ca să păstrezi)' : 'whsec_…' }}"
           autocomplete="off">
    <p class="text-xs text-slate-500 mt-1">
        În Stripe Dashboard, adaugă endpoint-ul de mai jos pe contul firmei.
        Plata se confirmă și la întoarcerea din Checkout, chiar fără webhook.
    </p>
</div>

<p class="text-xs text-slate-500 space-y-1">
    <span class="block">Webhook (facturi clienți — contul Stripe al firmei):</span>
    <code class="text-[11px]">{{ url('/plata/stripe/webhook') }}</code><br>
    Evenimente: <code class="text-[11px]">checkout.session.completed</code>,
    <code class="text-[11px]">checkout.session.async_payment_succeeded</code>
</p>
