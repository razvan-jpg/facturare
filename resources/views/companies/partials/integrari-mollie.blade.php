<label class="flex items-center gap-2 text-sm text-slate-800">
    <input type="checkbox" name="enabled" value="1" class="rounded border-slate-300"
           @checked(old('enabled', $integrations->getBool($company, 'mollie', 'enabled')))>
    Activează Mollie pentru plata facturilor
</label>

<div>
    <label class="dc-label" for="key">API key</label>
    <input id="key" name="key" class="dc-input font-mono text-sm" value="{{ old('key') }}"
           placeholder="{{ filled($integrations->get($company, 'mollie', 'key')) ? '•••••••• (lasă gol ca să păstrezi cheia)' : 'live_… sau test_…' }}"
           autocomplete="off">
    <p class="text-xs text-slate-500 mt-1">
        Din <a href="https://my.mollie.com/dashboard/developers/api-keys" target="_blank" rel="noopener" class="text-teal-800 underline">Mollie Dashboard → API keys</a>.
    </p>
</div>

<p class="text-xs text-slate-500 space-y-1">
    <span class="block">Webhook (facturi clienți — contul Mollie al firmei):</span>
    <code class="text-[11px]">{{ url('/plata/mollie/webhook') }}</code>
</p>
