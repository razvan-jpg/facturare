<label class="flex items-center gap-2 text-sm text-slate-800">
    <input type="checkbox" name="enabled" value="1" class="rounded border-slate-300"
           @checked(old('enabled', $settings->getBool('euplatesc.enabled', (bool) config('euplatesc.enabled'))))>
    Activează Eu Plătesc pentru plata abonamentului DateConta
</label>

<label class="flex items-center gap-2 text-sm text-slate-800">
    <input type="checkbox" name="sandbox" value="1" class="rounded border-slate-300"
           @checked(old('sandbox', $settings->getBool('euplatesc.sandbox', (bool) config('euplatesc.sandbox'))))>
    Mod test / sandbox
</label>

<div>
    <label class="dc-label" for="mid">Merchant ID (MID)</label>
    <input id="mid" name="mid" class="dc-input font-mono text-sm"
           value="{{ old('mid', $settings->get('euplatesc.mid', config('euplatesc.mid'))) }}"
           placeholder="ex: 44840995429" autocomplete="off">
</div>

<div>
    <label class="dc-label" for="key">Cheie secretă (KEY, hex)</label>
    <input id="key" name="key" class="dc-input font-mono text-sm"
           value="{{ old('key', $settings->get('euplatesc.key', config('euplatesc.key'))) }}"
           placeholder="32+ caractere hex" autocomplete="off">
    <p class="text-xs text-slate-500 mt-1">Lasă gol la salvare pentru a păstra cheia deja setată (.env sau DB).</p>
</div>

<p class="text-xs text-slate-500 space-y-1">
    <span class="block font-medium text-slate-600">URL-uri abonament DateConta (nu pentru facturi clienți):</span>
    <span class="block">Silent URL: <code class="text-[11px]">{{ url('/billing/euplatesc/silent') }}</code></span>
    <span class="block">Return URL: <code class="text-[11px]">{{ url('/billing/euplatesc/return/{order}') }}</code></span>
</p>
