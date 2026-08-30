@php
    $emailTpl = app(\App\Services\DocumentEmailTemplate::class);
    $defaultSubject = $emailTpl->defaultSubject();
    $defaultBody = $emailTpl->defaultBody();
    $palette = \App\Services\DocumentEmailTemplate::variablePalette();
    $useCustom = (bool) old('mail_use_custom_smtp', $company->mail_use_custom_smtp);
@endphp

<div class="space-y-8" x-data="dcEmailSettings(@js($useCustom))">
    {{-- Text email --}}
    <form method="POST" action="{{ route('companies.update', $company) }}" class="dc-card p-6 space-y-4" id="email-text-form">
        @csrf @method('PUT')
        <input type="hidden" name="tab" value="email">
        <input type="hidden" name="email_section" value="text">

        <div>
            <h2 class="text-lg font-semibold text-slate-900">Text email</h2>
            <p class="text-sm text-slate-600 mt-1">
                Stabilește informațiile de pe documente care vor fi preluate automat atunci când trimiți email clienților.
            </p>
        </div>

        <div class="dc-email-layout">
            <div class="dc-email-main space-y-4">
                <div>
                    <label class="dc-label" for="email_invoice_subject">{{ __('Subiect') }}</label>
                    <input id="email_invoice_subject"
                           name="email_invoice_subject"
                           class="dc-input dc-email-field"
                           value="{{ old('email_invoice_subject', $company->email_invoice_subject ?: $defaultSubject) }}">
                </div>
                <div>
                    <label class="dc-label" for="email_invoice_body">{{ __('Mesaj') }}</label>
                    <textarea id="email_invoice_body"
                              name="email_invoice_body"
                              rows="12"
                              class="dc-input dc-email-field font-mono text-sm leading-relaxed">{{ old('email_invoice_body', $company->email_invoice_body ?: $defaultBody) }}</textarea>
                </div>
                <button type="submit" class="dc-btn-primary">Salvează text email</button>
            </div>

            <aside class="dc-email-vars">
                <div class="dc-email-vars-box">
                    <p class="dc-email-vars-intro">
                        Personalizează mesajul cu date preluate automat de pe documente folosind termeni variabili.
                    </p>
                    @foreach($palette as $group => $vars)
                        <div class="dc-email-vars-group">
                            <div class="dc-email-vars-group-title">{{ $group }}</div>
                            <div class="dc-email-vars-chips">
                                @foreach($vars as $label => $token)
                                    <button type="button"
                                            class="dc-email-chip"
                                            @click="insertVar({{ json_encode($token) }})">{{ $label }}</button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    <p class="dc-email-vars-hint">
                        Click pe un termen pentru a-l insera în subiect sau mesaj (în câmpul activ).
                        Variabilele apar ca <code>#tip document#</code>.
                    </p>
                </div>
            </aside>
        </div>
    </form>

    {{-- Server email --}}
    <form method="POST" action="{{ route('companies.update', $company) }}" class="dc-card p-6 space-y-4" id="email-smtp-form">
        @csrf @method('PUT')
        <input type="hidden" name="tab" value="email">
        <input type="hidden" name="email_section" value="smtp">

        <div>
            <h2 class="text-lg font-semibold text-slate-900">Server email</h2>
            <p class="text-sm text-slate-600 mt-1">
                Furnizorul serviciului de email (găzduire) îți poate da aceste informații.
                Dacă utilizezi servicii gratuite de mail, găsești pe internet setările predefinite pentru acestea.
            </p>
        </div>

        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
            <input type="checkbox"
                   name="mail_use_custom_smtp"
                   value="1"
                   class="rounded border-slate-300 text-sky-700 focus:ring-sky-600"
                   x-model="useCustom"
                   @checked($useCustom)>
            <span class="text-sm font-medium text-slate-800">Vreau să folosesc serverul meu de email</span>
        </label>

        <div class="space-y-4" x-show="useCustom" x-cloak>
            <div>
                <label class="dc-label" for="mail_smtp_username">Email utilizator</label>
                <input id="mail_smtp_username"
                       name="mail_smtp_username"
                       type="email"
                       class="dc-input"
                       placeholder="adresă email validă"
                       value="{{ old('mail_smtp_username', $company->mail_smtp_username) }}"
                       autocomplete="username">
            </div>
            <div>
                <label class="dc-label" for="mail_smtp_password">{{ __('Parolă') }}</label>
                <input id="mail_smtp_password"
                       name="mail_smtp_password"
                       type="password"
                       class="dc-input"
                       placeholder="{{ $company->mail_smtp_password ? '•••••••• (lasă gol pentru a păstra parola actuală)' : 'parola adresei introduse' }}"
                       autocomplete="new-password">
            </div>
            <div>
                <label class="dc-label" for="mail_smtp_host">Adresa server SMTP</label>
                <input id="mail_smtp_host"
                       name="mail_smtp_host"
                       class="dc-input"
                       placeholder="de ex: smtp.gmail.com"
                       value="{{ old('mail_smtp_host', $company->mail_smtp_host) }}">
            </div>
            <div class="flex flex-wrap items-end gap-4">
                <div class="w-40">
                    <label class="dc-label" for="mail_smtp_port">Port</label>
                    <select id="mail_smtp_port" name="mail_smtp_port" class="dc-input">
                        @foreach([25, 465, 587] as $port)
                            <option value="{{ $port }}" @selected((int) old('mail_smtp_port', $company->mail_smtp_port ?: 587) === $port)>{{ $port }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="inline-flex items-center gap-2 cursor-pointer pb-2 select-none" title="Activează criptarea TLS (recomandat pe portul 587).">
                    <input type="checkbox"
                           name="mail_smtp_tls"
                           value="1"
                           class="rounded border-slate-300 text-sky-700 focus:ring-sky-600"
                           @checked(old('mail_smtp_tls', $company->mail_smtp_tls))>
                    <span class="text-sm font-medium text-slate-800">TLS</span>
                </label>
            </div>
            <p class="text-xs text-slate-500">
                Port 465 folosește de obicei SSL; 587 + TLS este varianta modernă. Parola este stocată criptat.
            </p>
        </div>

        <button type="submit" class="dc-btn-primary">Salvează configurarea</button>
    </form>
</div>

<style>
.dc-email-layout {
    display: grid;
    gap: 1.25rem;
}
@media (min-width: 900px) {
    .dc-email-layout {
        grid-template-columns: minmax(0, 1fr) 260px;
        align-items: start;
    }
}
.dc-email-vars-box {
    background: #f0f7f9;
    border: 1px solid #c5dde4;
    border-radius: 0.75rem;
    padding: 1rem;
}
.dc-email-vars-intro {
    font-size: 0.8rem;
    color: #334e68;
    line-height: 1.45;
    margin: 0 0 0.85rem;
}
.dc-email-vars-group { margin-bottom: 0.85rem; }
.dc-email-vars-group-title {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #627d98;
    margin-bottom: 0.4rem;
}
.dc-email-vars-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
}
.dc-email-chip {
    border: 1px solid #9fb3c8;
    background: #fff;
    color: #0a3440;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 0.25rem 0.55rem;
    border-radius: 999px;
    cursor: pointer;
}
.dc-email-chip:hover { background: #d9eef3; border-color: #0f4c5c; }
.dc-email-vars-hint {
    font-size: 0.72rem;
    color: #627d98;
    margin: 0.5rem 0 0;
    line-height: 1.4;
}
.dc-email-vars-hint code {
    background: #fff;
    padding: 0.05rem 0.25rem;
    border-radius: 0.25rem;
    font-size: 0.7rem;
}
</style>

<script>
function dcEmailSettings(useCustom) {
    return {
        useCustom: !!useCustom,
        lastField: null,
        init() {
            this.$el.querySelectorAll('.dc-email-field').forEach((el) => {
                el.addEventListener('focus', () => { this.lastField = el; });
            });
            this.lastField = this.$el.querySelector('#email_invoice_body')
                || this.$el.querySelector('.dc-email-field');
        },
        insertVar(token) {
            const el = this.lastField || this.$el.querySelector('.dc-email-field');
            if (!el) return;
            el.focus();
            const start = el.selectionStart ?? el.value.length;
            const end = el.selectionEnd ?? start;
            const before = el.value.slice(0, start);
            const after = el.value.slice(end);
            el.value = before + token + after;
            const pos = start + token.length;
            el.setSelectionRange(pos, pos);
            el.dispatchEvent(new Event('input', { bubbles: true }));
        },
    };
}
</script>
