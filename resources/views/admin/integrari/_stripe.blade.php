<label class="flex items-center gap-2 text-sm text-slate-800">
    <input type="checkbox" name="enabled" value="1" class="rounded border-slate-300"
           @checked(old('enabled', $settings->getBool('stripe.enabled', (bool) config('stripe.enabled'))))>
    Activează Stripe pentru plata abonamentului DateConta
</label>

<div>
    <label class="dc-label" for="key">Publishable key</label>
    <input id="key" name="key" class="dc-input font-mono text-sm"
           value="{{ old('key', $settings->get('stripe.key', config('stripe.key'))) }}"
           placeholder="pk_live_… sau pk_test_…" autocomplete="off">
</div>

<div>
    <label class="dc-label" for="secret">Secret key</label>
    <input id="secret" name="secret" class="dc-input font-mono text-sm" value="{{ old('secret') }}"
           placeholder="{{ filled($settings->get('stripe.secret', config('stripe.secret'))) ? '•••••••• (lasă gol ca să păstrezi cheia)' : 'sk_live_… sau sk_test_…' }}"
           autocomplete="off">
</div>

<div>
    <label class="dc-label" for="webhook_secret">Webhook signing secret</label>
    <input id="webhook_secret" name="webhook_secret" class="dc-input font-mono text-sm" value="{{ old('webhook_secret') }}"
           placeholder="{{ filled($settings->get('stripe.webhook_secret', config('stripe.webhook_secret'))) ? '•••••••• (lasă gol ca să păstrezi)' : 'whsec_…' }}"
           autocomplete="off">
    <p class="text-xs text-slate-500 mt-1">
        Din <a href="https://dashboard.stripe.com/webhooks" target="_blank" rel="noopener" class="text-teal-800 underline">Stripe → Developers → Webhooks</a>.
    </p>
</div>

<p class="text-xs text-slate-500">
    Webhook abonament DateConta: <code class="text-[11px]">{{ url('/billing/stripe/webhook') }}</code><br>
    Evenimente: <code class="text-[11px]">checkout.session.completed</code>,
    <code class="text-[11px]">checkout.session.async_payment_succeeded</code>,
    <code class="text-[11px]">invoice.paid</code>
</p>
