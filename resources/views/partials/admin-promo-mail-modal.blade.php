<div x-data="{
        open: false,
        emails: '',
        sending: false,
        openModal() {
            this.emails = '';
            this.sending = false;
            this.open = true;
            this.$nextTick(() => this.$refs.emails?.focus());
        },
        close() { this.open = false; this.sending = false; },
     }"
     x-on:open-admin-promo-mail.window="openModal()"
     x-on:keydown.escape.window="if (open) close()"
     x-cloak>
    <div x-show="open"
         class="fixed inset-0 z-[80] flex items-center justify-center p-4"
         style="display:none;">
        <div class="absolute inset-0 bg-slate-900/45" @click="close()"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden"
             role="dialog" aria-modal="true" aria-labelledby="admin-promo-mail-title"
             @click.stop>
            <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-teal-50 to-amber-50">
                <h2 id="admin-promo-mail-title" class="font-display text-xl text-slate-900">Trimite mail reclamă</h2>
                <p class="text-sm text-slate-600 mt-1">
                    De la <strong>Razvan Ivan — FLY DAVID SRL</strong>, fără cod promoțional
                    (max. 20 adrese).
                </p>
            </div>
            <form method="POST" action="{{ route('admin.promo-mail') }}" @submit="sending = true" class="p-5 space-y-4">
                @csrf
                <div>
                    <label class="dc-label" for="admin_promo_emails">Adrese email destinatare</label>
                    <textarea id="admin_promo_emails" name="emails" x-ref="emails" x-model="emails" rows="4"
                              class="dc-input font-mono text-sm" required
                              placeholder="ex: coleg@firma.ro, prieten@email.com"
                              autocomplete="email"></textarea>
                    <p class="text-xs text-slate-500 mt-1">
                        Separă adresele prin virgulă, spațiu sau linie nouă (maximum 20).
                    </p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                    Mesajul e similar cu recomandarea din aplicație, dar <strong>fără cod promo</strong>:
                    invitație la DateConta Facturare + CTA de înregistrare.
                </div>
                <div class="flex flex-wrap justify-end gap-2 pt-1">
                    <button type="button" class="dc-btn-secondary" @click="close()" :disabled="sending">{{ __('Anulează') }}</button>
                    <button type="submit" class="dc-btn-primary" :disabled="sending || !emails.trim()">
                        <span x-show="!sending">{{ __('Trimite') }}</span>
                        <span x-cloak x-show="sending">Se trimite…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
