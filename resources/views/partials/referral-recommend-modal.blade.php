@php
    $inviteeDays = (int) config('dateconta.referral.invitee_bonus_days', 14);
    $referrerEvery = (int) config('dateconta.referral.referrer_every', 2);
    $referrerMonths = (int) config('dateconta.referral.referrer_bonus_months', 1);
@endphp
<div x-data="{
        open: false,
        companyId: null,
        companyName: '',
        promoCode: '',
        emails: '',
        sending: false,
        openFor(detail) {
            this.companyId = detail.id;
            this.companyName = detail.name || '';
            this.promoCode = detail.code || '';
            this.emails = '';
            this.sending = false;
            this.open = true;
            this.$nextTick(() => this.$refs.emails?.focus());
        },
        close() { this.open = false; this.sending = false; },
        actionUrl() { return '/companies/' + this.companyId + '/referral-recommend'; }
     }"
     x-on:open-referral-mail.window="openFor($event.detail || {})"
     x-on:keydown.escape.window="if (open) close()"
     x-cloak>
    <div x-show="open"
         class="fixed inset-0 z-[80] flex items-center justify-center p-4"
         style="display:none;">
        <div class="absolute inset-0 bg-slate-900/45" @click="close()"></div>
        <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden"
             role="dialog" aria-modal="true" aria-labelledby="referral-mail-title"
             @click.stop>
            <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-teal-50 to-amber-50">
                <h2 id="referral-mail-title" class="font-display text-xl text-slate-900">Trimite mail recomandare</h2>
                <p class="text-sm text-slate-600 mt-1">
                    Mesaj personalizat de la <strong x-text="companyName"></strong>, cu codul
                    <code class="font-mono tracking-wider text-teal-800" x-text="promoCode"></code>.
                </p>
            </div>
            <form method="POST" :action="actionUrl()" @submit="sending = true" class="p-5 space-y-4">
                @csrf
                <div>
                    <label class="dc-label" for="referral_emails">Adrese email destinatare</label>
                    <textarea id="referral_emails" name="emails" x-ref="emails" x-model="emails" rows="3"
                              class="dc-input font-mono text-sm" required
                              placeholder="ex: coleg@firma.ro, prieten@email.com"
                              autocomplete="email"></textarea>
                    <p class="text-xs text-slate-500 mt-1">
                        Poți introduce una sau mai multe adrese, separate prin virgulă, spațiu sau linie nouă (max. 10).
                    </p>
                </div>
                <div class="rounded-xl border border-amber-200 bg-amber-50/70 px-4 py-3 text-sm text-amber-950">
                    Destinatarii primesc invitația cu codul tău mare și vizibil, plus instrucțiuni:
                    la înregistrare / creare societate folosesc codul — ei +{{ $inviteeDays }} zile,
                    tu +{{ $referrerMonths }} lună la fiecare {{ $referrerEvery }} societăți aduse.
                </div>
                <div class="flex flex-wrap justify-end gap-2 pt-1">
                    <button type="button" class="dc-btn-secondary" @click="close()" :disabled="sending">{{ __('Anulează') }}</button>
                    <button type="submit" class="dc-btn-primary" :disabled="sending || !emails.trim()">
                        <span x-show="!sending">Trimite mailul</span>
                        <span x-cloak x-show="sending">Se trimite…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
