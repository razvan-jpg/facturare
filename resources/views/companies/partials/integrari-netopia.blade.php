@php
    $netopiaStatus = $integrations->netopiaConfigurationStatus($company);
@endphp

@if(! ($netopiaStatus['ready'] ?? false))
    <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-950 space-y-1">
        <p class="font-semibold">NETOPIA nu e încă disponibilă pentru clienții tăi:</p>
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach(($netopiaStatus['missing'] ?? []) as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ul>
    </div>
@else
    <div class="rounded-lg border border-teal-200 bg-teal-50 px-3 py-2 text-xs text-teal-950 space-y-1">
        <p class="font-semibold">Gata pentru clienți.</p>
        <p>Pe factură / proformă bifează <strong>Permite plata cu cardul online</strong> — linkul NETOPIA apare pe PDF și în email.</p>
        @php
            $operatorCui = preg_replace('/\D+/', '', (string) config('dateconta.platform_operator.cui', ''));
            $companyCui = preg_replace('/\D+/', '', (string) ($company->cui ?? ''));
            $isPlatformOperator = $operatorCui !== '' && $operatorCui === $companyCui;
        @endphp
        @if($isPlatformOperator)
            <p class="font-medium text-teal-900">
                Ca firmă operator (FLY DAVID), aceste chei sunt folosite și la <strong>checkout-ul de abonament DateConta</strong>.
            </p>
        @endif
        <p>În panoul NETOPIA al firmei, Confirm URL:
            <code class="text-[11px]">{{ url('/plata/netopia/confirm') }}</code>
        </p>
    </div>
@endif

<label class="flex items-center gap-2 text-sm text-slate-800">
    <input type="checkbox" name="enabled" value="1" class="rounded border-slate-300"
           @checked(old('enabled', $integrations->getBool($company, 'netopia', 'enabled')))>
    Activează NETOPIA pentru plata facturilor
</label>

<label class="flex items-center gap-2 text-sm text-slate-800">
    <input type="checkbox" name="sandbox" value="1" class="rounded border-slate-300"
           @checked(old('sandbox', $integrations->getBool($company, 'netopia', 'sandbox', false)))>
    Mod sandbox (test) — debifează pentru plăți reale
</label>

<div>
    <label class="dc-label" for="signature">Semnătură merchant</label>
    <input id="signature" name="signature" class="dc-input font-mono text-sm"
           value="{{ old('signature', $integrations->get($company, 'netopia', 'signature', '')) }}"
           placeholder="XXXX-XXXX-XXXX-XXXX-XXXX" autocomplete="off">
</div>

<div class="grid sm:grid-cols-2 gap-3">
    <div>
        <label class="dc-label" for="public_cer">Certificat public (.cer)</label>
        <input id="public_cer" type="file" name="public_cer" accept=".cer,.pem,.crt" class="dc-input text-sm">
        <p class="text-xs text-slate-500 mt-1">
            @if(is_readable($integrations->netopiaPublicPath($company)))
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
            @if(is_readable($integrations->netopiaPrivatePath($company)))
                Fișier curent: există
            @else
                Lipsă — încarcă private.key
            @endif
        </p>
    </div>
</div>

<p class="text-xs text-slate-500 space-y-1">
    <span class="block">IPN confirm (facturi clienți — panoul NETOPIA al firmei):</span>
    <code class="text-[11px]">{{ url('/plata/netopia/confirm') }}</code>
</p>
