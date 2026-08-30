@if(!empty($netopiaStatus) && ($netopiaStatus['ready'] ?? false))
    <div class="rounded-lg border border-teal-200 bg-teal-50 px-3 py-2 text-xs text-teal-950 space-y-1">
        <p class="font-semibold">Checkout folosește acum:</p>
        <ul class="list-disc pl-4 space-y-0.5">
            <li>Sursă: {{ ($netopiaStatus['source'] ?? '') === 'operator_company' ? 'FLY DAVID (Setări → Integrări)' : 'Admin platformă / .env' }}</li>
            <li>Mod: {{ ($netopiaStatus['sandbox'] ?? false) ? 'sandbox (test)' : 'live' }}</li>
            @if(!empty($netopiaStatus['payment_url']))
                <li>URL: <code class="text-[11px]">{{ $netopiaStatus['payment_url'] }}</code></li>
            @endif
        </ul>
        <p class="pt-1 text-teal-900/80">Cheile de test trebuie folosite cu sandbox bifat; cheile live cu sandbox debifat.</p>
    </div>
@elseif(!empty($netopiaStatus) && ! ($netopiaStatus['ready'] ?? false))
    <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-950 space-y-1">
        <p class="font-semibold">NETOPIA nu e încă selectabilă la checkout:</p>
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach(($netopiaStatus['missing'] ?? []) as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ul>
    </div>
@endif

<label class="flex items-center gap-2 text-sm text-slate-800">
    <input type="checkbox" name="enabled" value="1" class="rounded border-slate-300"
           @checked(old('enabled', $settings->getBool('netopia.enabled', (bool) config('netopia.enabled'))))>
    Activează NETOPIA pentru plata abonamentului DateConta
</label>

<label class="flex items-center gap-2 text-sm text-slate-800">
    <input type="checkbox" name="sandbox" value="1" class="rounded border-slate-300"
           @checked(old('sandbox', $settings->getBool('netopia.sandbox', (bool) config('netopia.sandbox'))))>
    Mod sandbox (test)
</label>

<div>
    <label class="dc-label" for="signature">Semnătură merchant</label>
    <input id="signature" name="signature" class="dc-input font-mono text-sm"
           value="{{ old('signature', $settings->get('netopia.signature', config('netopia.signature'))) }}"
           placeholder="XXXX-XXXX-XXXX-XXXX-XXXX" autocomplete="off">
</div>

<div class="grid sm:grid-cols-2 gap-3">
    <div>
        <label class="dc-label" for="public_cer">Certificat public (.cer)</label>
        <input id="public_cer" type="file" name="public_cer" accept=".cer,.pem,.crt" class="dc-input text-sm">
        <p class="text-xs text-slate-500 mt-1">
            @if(is_readable(config('netopia.public_key_path')))
                Fișier curent: există
            @else
                Lipsă — încarcă public.cer
            @endif
        </p>
    </div>
    <div>
        <label class="dc-label" for="private_key">Cheie privată (.key)</label>
        <input id="private_key" type="file" name="private_key" accept=".key,.pem" class="dc-input text-sm">
        <p class="text-xs text-slate-500 mt-1">
            @if(is_readable(config('netopia.private_key_path')))
                Fișier curent: există
            @else
                Lipsă — încarcă private.key
            @endif
        </p>
    </div>
</div>

<p class="text-xs text-slate-500">
    IPN confirm abonament DateConta: <code class="text-[11px]">{{ url('/billing/netopia/confirm') }}</code>
</p>
