<div class="dc-card p-6 mb-4">
    @include('partials.anaf-lookup')
</div>
<form method="POST" action="{{ route('companies.update', $company) }}" class="dc-card p-6 space-y-4">
    @csrf @method('PUT')
    <input type="hidden" name="tab" value="generale">
    <h2 class="text-lg font-semibold">Date firmă</h2>
    <div class="grid sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2"><label class="dc-label">Denumire</label><input name="name" value="{{ old('name', $company->name) }}" class="dc-input" required></div>
        <div class="sm:col-span-2">
            <label class="dc-label">{{ __('Cod promoțional') }}</label>
            @if(filled($company->promo_code))
                <button type="button"
                        class="dc-input bg-slate-50 font-mono tracking-wider text-left cursor-pointer hover:bg-slate-100 transition-colors"
                        title="{{ __('Click pentru a copia codul') }}"
                        x-data="{ copied: false }"
                        @click="
                            navigator.clipboard.writeText(@js($company->promo_code)).then(() => {
                                copied = true;
                                setTimeout(() => copied = false, 1600);
                            }).catch(() => {
                                window.prompt(@js(__('Copiază codul promoțional:')), @js($company->promo_code));
                            })
                        ">
                    <span x-show="!copied">{{ $company->promo_code }}</span>
                    <span x-cloak x-show="copied">{{ __('Copiat!') }}</span>
                </button>
            @else
                <div class="dc-input bg-slate-50 font-mono tracking-wider">—</div>
            @endif
            <p class="text-xs text-slate-500 mt-1">Click pe cod pentru a-l copia (email, WhatsApp etc.). Generat automat, unic — nu se poate modifica.</p>
            @if(filled($company->promo_code))
                <button type="button"
                        class="dc-btn-primary text-sm mt-3"
                        @click="window.dispatchEvent(new CustomEvent('open-referral-mail', {
                            detail: {
                                id: {{ (int) $company->id }},
                                name: @js($company->name),
                                code: @js($company->promo_code)
                            }
                        }))">
                    Trimite mail recomandare
                </button>
            @endif
        </div>
        <div>
            <label class="dc-label">CUI</label>
            <input name="cui"
                   value="{{ old('cui', dc_format_cui($company->cui, (bool) $company->vat_payer)) }}"
                   class="dc-input"
                   data-vat-payer="{{ $company->vat_payer ? '1' : '0' }}"
                   placeholder="{{ $company->vat_payer ? 'RO12345678' : '12345678' }}">
            <p class="text-xs text-slate-500 mt-1">
                @if($company->vat_payer)
                    Plătitor TVA: CUI-ul se afișează cu prefix <strong>RO</strong>.
                @else
                    Neplătitor TVA: CUI-ul se afișează fără prefix RO.
                @endif
                Statusul se schimbă din tab-ul <em>{{ __('Cote TVA') }}</em>.
            </p>
        </div>
        <div><label class="dc-label">{{ __('Reg. Com.') }}</label><input name="reg_com" value="{{ old('reg_com', $company->reg_com) }}" class="dc-input"></div>
        <div class="sm:col-span-2"><label class="dc-label">{{ __('Adresă') }}</label><input name="address" value="{{ old('address', $company->address) }}" class="dc-input"></div>
        <div><label class="dc-label">Localitate</label><input name="city" value="{{ old('city', $company->city) }}" class="dc-input"></div>
        @include('partials.county-select', ['value' => $company->county])
        <div><label class="dc-label">{{ __('Țară') }}</label><input name="country" value="{{ old('country', $company->country) }}" class="dc-input"></div>
        <div><label class="dc-label">Capital social</label><input name="capital_social" value="{{ old('capital_social', $company->capital_social) }}" class="dc-input"></div>
        <div><label class="dc-label">{{ __('Telefon') }}</label><input name="phone" value="{{ old('phone', $company->phone) }}" class="dc-input"></div>
        <div><label class="dc-label">Email</label><input name="email" type="email" value="{{ old('email', $company->email) }}" class="dc-input"></div>
        <div class="sm:col-span-2"><label class="dc-label">Website</label><input name="website" value="{{ old('website', $company->website) }}" class="dc-input" placeholder="https://"></div>
    </div>
    <button class="dc-btn-primary">{{ __('Salvează') }}</button>
</form>
