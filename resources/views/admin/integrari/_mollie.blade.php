<label class="flex items-center gap-2 text-sm text-slate-800">
    <input type="checkbox" name="enabled" value="1" class="rounded border-slate-300"
           @checked(old('enabled', $settings->getBool('mollie.enabled', (bool) config('mollie.enabled'))))>
    Activează Mollie pentru plata abonamentului DateConta
</label>

<div>
    <label class="dc-label" for="key">API key</label>
    <input id="key" name="key" class="dc-input font-mono text-sm"
           value="{{ old('key', $settings->get('mollie.key', config('mollie.key'))) }}"
           placeholder="live_… sau test_…" autocomplete="off">
    <p class="text-xs text-slate-500 mt-1">
        Din <a href="https://my.mollie.com/dashboard/developers/api-keys" target="_blank" rel="noopener" class="text-teal-800 underline">Mollie Dashboard → API keys</a>.
        Lasă gol la salvare doar dacă vrei să păstrezi cheia deja setată din .env (bifează/debifează Enabled).
    </p>
</div>

<p class="text-xs text-slate-500">
    Webhook abonament DateConta: <code class="text-[11px]">{{ url('/billing/mollie/webhook') }}</code>
</p>
