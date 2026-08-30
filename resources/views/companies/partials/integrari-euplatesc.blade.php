<label class="flex items-center gap-2 text-sm text-slate-800">
    <input type="checkbox" name="enabled" value="1" class="rounded border-slate-300"
           @checked(old('enabled', $integrations->getBool($company, 'euplatesc', 'enabled')))>
    Activează Eu Plătesc pentru plata facturilor
</label>

<label class="flex items-center gap-2 text-sm text-slate-800">
    <input type="checkbox" name="sandbox" value="1" class="rounded border-slate-300"
           @checked(old('sandbox', $integrations->getBool($company, 'euplatesc', 'sandbox', true)))>
    Mod test / sandbox
</label>

<div>
    <label class="dc-label" for="mid">Merchant ID (MID)</label>
    <input id="mid" name="mid" class="dc-input font-mono text-sm"
           value="{{ old('mid', $integrations->get($company, 'euplatesc', 'mid', '')) }}"
           placeholder="ex: 44840995429" autocomplete="off">
</div>

<div>
    <label class="dc-label" for="key">Cheie secretă (KEY, hex)</label>
    <input id="key" name="key" class="dc-input font-mono text-sm" value="{{ old('key') }}"
           placeholder="{{ filled($integrations->get($company, 'euplatesc', 'key')) ? '•••••••• (lasă gol ca să păstrezi cheia)' : '32+ caractere hex' }}"
           autocomplete="off">
    <p class="text-xs text-slate-500 mt-1">Lasă gol la salvare pentru a păstra cheia deja setată.</p>
</div>

<p class="text-xs text-slate-500 space-y-1">
    <span class="block">Silent URL (facturi clienți — panoul Eu Plătesc al firmei):</span>
    <code class="text-[11px]">{{ url('/plata/euplatesc/silent') }}</code>
</p>
