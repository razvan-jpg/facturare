<div class="space-y-6">
<form method="POST" action="{{ route('companies.update', $company) }}" class="dc-card p-6 space-y-4">
    @csrf @method('PUT')
    <input type="hidden" name="tab" value="efactura">
    <h2 class="text-lg font-semibold">e-Factura / SPV ANAF</h2>
    <div class="rounded-lg bg-slate-50 p-4 text-sm">
        <div class="text-xs uppercase tracking-wide text-slate-500 mb-1">Status SPV</div>
        @if($company->isAnafAuthorized())
            <div class="font-semibold text-emerald-700">Autorizat</div>
            <div class="text-slate-600 mt-1">
                @if($company->anaf_authorized_at)
                    Conectat: {{ dc_datetime($company->anaf_authorized_at) }}
                    @if($company->anaf_authorized_by) · {{ $company->anaf_authorized_by }} @endif
                @endif
            </div>
            @if($company->anaf_token_expires_at)
                <div class="text-slate-600 mt-1">
                    Valabilă până la: <strong>{{ dc_datetime($company->anaf_token_expires_at) }}</strong>
                    @if($company->anaf_token_expires_at->isPast())
                        <span class="text-amber-700">(expirată — prelungește sau reautorizează)</span>
                    @endif
                </div>
            @endif
        @else
            <div class="font-semibold text-slate-700">Neautorizat</div>
            <div class="text-slate-600 mt-1">Necesită certificat digital SPV pe CUI-ul firmei.</div>
        @endif
        <p class="text-slate-600 mt-3 text-xs leading-relaxed">
            Înainte de autorizare: închide tab-urile deschise pe anaf.ro, folosește certificatul cu drept SPV pe acest CUI
            și nu deschide linkul într-un tab nou. Dacă ANAF arată „session finished” / hangup, reîncearcă după restart browser
            sau inviți contabilul pe email.
        </p>
    </div>
    @error('efactura')
        <div class="rounded-lg border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 text-sm">{{ $message }}</div>
    @enderror
    <div>
        <label class="dc-label">Mod trimitere e-Factura</label>
        <select name="efactura_send_mode" class="dc-input">
            @foreach(\App\Models\Company::EFACTURA_SEND_MODES as $value => $label)
                <option value="{{ $value }}" @selected(old('efactura_send_mode', $company->efacturaSendMode()) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <button class="dc-btn-primary">Salvează modul de trimitere</button>
</form>

<div class="dc-card p-6">
    <div class="flex flex-wrap gap-3 mb-6">
        @if($company->isAnafAuthorized())
            <form method="POST" action="{{ route('companies.anaf.extend', $company) }}">
                @csrf
                <button class="dc-btn-primary" {{ $anafConfigured ? '' : 'disabled' }}>Prelungește conectarea</button>
            </form>
            <form method="POST" action="{{ route('companies.anaf.revoke', $company) }}" onsubmit="return confirm('Revoci conectarea SPV? Tokenurile vor fi șterse din aplicație.')">
                @csrf
                <button class="dc-btn-secondary">Revocă conectarea</button>
            </form>
            <a href="{{ route('anaf.oauth.redirect', $company) }}" class="dc-btn-secondary {{ $anafConfigured ? '' : 'opacity-50 pointer-events-none' }}">
                Reautorizează SPV
            </a>
        @else
            <a href="{{ route('anaf.oauth.redirect', $company) }}" class="dc-btn-primary {{ $anafConfigured ? '' : 'opacity-50 pointer-events-none' }}">
                Autorizează SPV
            </a>
        @endif
    </div>
    <div class="border-t border-slate-100 pt-5">
        <h3 class="font-semibold mb-2">Invită contabilul pe email</h3>
        <p class="text-sm text-slate-600 mb-3">Contabilul primește un link de autorizare SPV (valabil 7 zile). După trimitere vezi confirmarea cu data și ora.</p>
        <form method="POST" action="{{ route('companies.efactura.invite', $company) }}" class="flex flex-col sm:flex-row gap-3">
            @csrf
            <input type="email" name="email" value="{{ old('email') }}" class="dc-input" placeholder="contabil@firma.ro" required {{ $anafConfigured ? '' : 'disabled' }}>
            <button class="dc-btn-secondary whitespace-nowrap" {{ $anafConfigured ? '' : 'disabled' }}>Trimite invitația</button>
        </form>
        @error('email')
            <div class="mt-3 rounded-lg border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 text-sm">{{ $message }}</div>
        @enderror
        @if(session('efactura_invite_email'))
            <div class="mt-4 rounded-lg border border-teal-200 bg-teal-50 text-teal-900 px-4 py-3 text-sm space-y-2">
                @if(session('efactura_invite_sent_at'))
                    <div>
                        <strong>Confirmare trimitere:</strong>
                        email către <strong>{{ session('efactura_invite_email') }}</strong>
                        la <strong>{{ session('efactura_invite_sent_at') }}</strong>.
                    </div>
                @else
                    <div>Invitație creată pentru <strong>{{ session('efactura_invite_email') }}</strong>.</div>
                @endif
                @if(session('efactura_invite_url'))
                    <div>
                        <div class="text-xs uppercase tracking-wide text-teal-700 mb-1">Link autorizare (poți copia / trimite manual)</div>
                        <a href="{{ session('efactura_invite_url') }}" class="break-all underline" target="_blank" rel="noopener">{{ session('efactura_invite_url') }}</a>
                    </div>
                @endif
            </div>
        @endif
        @if(($pendingInvites ?? collect())->isNotEmpty())
            <ul class="mt-4 space-y-2 text-sm text-slate-600">
                @foreach($pendingInvites as $invite)
                    <li>
                        În așteptare: {{ $invite->email }}
                        · expiră {{ dc_date($invite->expires_at) }}
                        @if($invite->sent_at)
                            · trimis {{ dc_datetime($invite->sent_at) }}
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
</div>
