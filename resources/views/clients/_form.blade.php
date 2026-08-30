@php $c = $client ?? null; @endphp
<div class="sm:col-span-2"><label class="dc-label">Denumire / Nume</label><input name="name" value="{{ old('name', $c->name ?? '') }}" class="dc-input" required></div>
<div><label class="dc-label">Tip</label>
    <select name="type" class="dc-input">
        <option value="company" @selected(old('type', $c->type ?? 'company')==='company')>Persoană juridică</option>
        <option value="person" @selected(old('type', $c->type ?? '')==='person')>Persoană fizică</option>
    </select>
</div>
<div><label class="dc-label">CUI</label><input name="cui" value="{{ old('cui', $c->cui ?? '') }}" class="dc-input"></div>
<div><label class="dc-label">{{ __('Reg. Com.') }}</label><input name="reg_com" value="{{ old('reg_com', $c->reg_com ?? '') }}" class="dc-input"></div>
<div><label class="dc-label">Nume administrator</label><input name="admin_last_name" value="{{ old('admin_last_name', $c->admin_last_name ?? '') }}" class="dc-input" autocomplete="off"></div>
<div><label class="dc-label">Prenume administrator</label><input name="admin_first_name" value="{{ old('admin_first_name', $c->admin_first_name ?? '') }}" class="dc-input" autocomplete="off"></div>
<div><label class="dc-label">CNP administrator</label><input name="cnp" value="{{ old('cnp', $c->cnp ?? '') }}" class="dc-input"></div>
<div class="sm:col-span-2"><label class="dc-label">{{ __('Adresă') }}</label><input name="address" value="{{ old('address', $c->address ?? '') }}" class="dc-input"></div>
<div><label class="dc-label">Localitate</label><input name="city" value="{{ old('city', $c->city ?? '') }}" class="dc-input"></div>
@include('partials.county-select', ['value' => $c->county ?? ''])
<div><label class="dc-label">{{ __('Țară') }}</label><input name="country" value="{{ old('country', $c->country ?? 'România') }}" class="dc-input"></div>
<div><label class="dc-label">{{ __('Telefon') }}</label><input name="phone" value="{{ old('phone', $c->phone ?? '') }}" class="dc-input"></div>
<div class="sm:col-span-2">
    <label class="dc-label">Email</label>
    <input name="email" type="text" inputmode="email" value="{{ old('email', $c->email ?? '') }}" class="dc-input" placeholder="ex: office@firma.ro, contabil@firma.ro" autocomplete="off">
    <p class="mt-1 text-xs text-slate-500">Poți introduce mai multe adrese, separate prin virgulă. Factura se trimite la toate.</p>
</div>
<div>
    <label class="dc-label">IBAN / cont bancar</label>
    <input name="iban" id="client-iban" value="{{ old('iban', $c->iban ?? '') }}" class="dc-input"
           data-iban-bank="#client-bank-name" placeholder="RO49 AAAA ..." autocomplete="off">
</div>
<div>
    <label class="dc-label">{{ __('Bancă') }}</label>
    <input name="bank_name" id="client-bank-name" value="{{ old('bank_name', $c->bank_name ?? '') }}" class="dc-input"
           placeholder="se completează din IBAN">
</div>
<div>
    <label class="dc-label">{{ __('Sold inițial') }}</label>
    <input name="opening_balance" type="text" inputmode="decimal"
           value="{{ old('opening_balance', $c->opening_balance ?? '0') }}"
           placeholder="0" class="dc-input tabular-nums" autocomplete="off">
    <p class="mt-1 text-xs text-slate-500">Dacă nu completezi, este 0. Data implicită = data creării clientului. Completează doar datoria care nu e deja în facturile din DateConta.</p>
</div>
@include('partials.date-input', [
    'name' => 'opening_balance_date',
    'label' => 'Data soldului inițial',
    'value' => $c ? $c->effectiveOpeningBalanceDate() : now()->toDateString(),
    'required' => false,
])
@php
    $instAmountOld = old('opening_installment_amount', $c->opening_installment_amount ?? '');
    if ($instAmountOld !== '' && $instAmountOld !== null) {
        $instAmountOld = rtrim(rtrim(number_format((float) $instAmountOld, 2, '.', ''), '0'), '.');
    }
    $instCountOld = old('opening_installment_count', $c->opening_installment_count ?? '');
@endphp
<div>
    <label class="dc-label">Tranșă lunară sold (sumă)</label>
    <input name="opening_installment_amount" type="text" inputmode="decimal"
           value="{{ $instAmountOld }}"
           placeholder="ex: 242" class="dc-input tabular-nums" autocomplete="off">
    <p class="mt-1 text-xs text-slate-500">Opțional. Pentru penalități: soldul e tratat ca N facturi lunare egale.</p>
</div>
<div>
    <label class="dc-label">Nr. tranșe lunare</label>
    <input name="opening_installment_count" type="number" min="1" max="120" step="1"
           value="{{ $instCountOld }}"
           placeholder="ex: 10" class="dc-input tabular-nums" autocomplete="off">
    <p class="mt-1 text-xs text-slate-500">Ultima scadență 11.08.2026; anterioare lunar pe data de 11.</p>
</div>
@php
    $penaltyPercentOld = old('penalty_percent', $c->penalty_percent ?? '');
    if ($penaltyPercentOld !== '' && $penaltyPercentOld !== null) {
        $penaltyPercentOld = rtrim(rtrim(number_format((float) $penaltyPercentOld, 4, '.', ''), '0'), '.');
    }
    $penaltyOn = (string) old('penalty_billing_enabled', ($c->penalty_billing_enabled ?? false) ? '1' : '0') === '1';
@endphp
<div>
    <label class="dc-label">Procent penalizare cf contract</label>
    <div class="relative">
        <input name="penalty_percent" type="text" inputmode="decimal"
               value="{{ $penaltyPercentOld }}"
               placeholder="ex: 0.1" class="dc-input tabular-nums pr-10" autocomplete="off">
        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-500">%</span>
    </div>
    <p class="mt-1 text-xs text-slate-500">Procent pe zi de întârziere (ex. 0,1%). Gol = fără calcul.</p>
</div>
<div>
    <label class="dc-label">Se calculeaza / factureaza:</label>
    <input type="hidden" name="penalty_billing_enabled" value="0">
    <label class="dc-onoff mt-1" data-dc-onoff>
        <input type="checkbox" name="penalty_billing_enabled" value="1" class="dc-onoff-input" @checked($penaltyOn)>
        <span class="dc-onoff-track" aria-hidden="true">
            <span class="dc-onoff-label dc-onoff-off">OFF</span>
            <span class="dc-onoff-label dc-onoff-on">ON</span>
            <span class="dc-onoff-knob"></span>
        </span>
    </label>
    <p class="mt-1 text-xs text-slate-500">ON = apar pe următoarea factură (fără TVA). OFF = calculul continuă, fără facturare.</p>
</div>
<div class="sm:col-span-2"><label class="dc-label">Note</label><textarea name="notes" rows="2" class="dc-input">{{ old('notes', $c->notes ?? '') }}</textarea></div>
<style>
.dc-onoff { display:inline-flex; cursor:pointer; user-select:none; }
.dc-onoff-input { position:absolute; opacity:0; width:0; height:0; }
.dc-onoff-track {
    position:relative; display:inline-flex; align-items:center; width:76px; height:32px;
    border-radius:999px; background:#cbd5e1; border:1px solid #94a3b8;
    transition: background .15s ease, border-color .15s ease;
}
.dc-onoff-label {
    position:absolute; top:0; bottom:0; display:flex; align-items:center;
    font-size:11px; font-weight:700; letter-spacing:.04em; color:#475569;
}
.dc-onoff-off { left:8px; }
.dc-onoff-on { right:8px; opacity:0; color:#0f766e; }
.dc-onoff-knob {
    position:absolute; top:3px; left:3px; width:24px; height:24px; border-radius:999px;
    background:#fff; box-shadow:0 1px 3px rgba(15,23,42,.25);
    transition: transform .15s ease;
}
.dc-onoff-input:checked + .dc-onoff-track {
    background:#ccfbf1; border-color:#0f766e;
}
.dc-onoff-input:checked + .dc-onoff-track .dc-onoff-knob { transform: translateX(44px); }
.dc-onoff-input:checked + .dc-onoff-track .dc-onoff-off { opacity:0; }
.dc-onoff-input:checked + .dc-onoff-track .dc-onoff-on { opacity:1; }
.dc-onoff-input:focus-visible + .dc-onoff-track { outline:2px solid #0f766e; outline-offset:2px; }
</style>
