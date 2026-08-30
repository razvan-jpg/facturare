@php
    /** @var \App\Models\User $user */
    $user = auth()->user();
    $accessGate = app(\App\Services\AccessGate::class);
    $companies = $user->companies()
        ->with('owner:id,name,email,plan,access_until,trial_ends_at,is_admin')
        ->orderBy('name')
        ->get();
@endphp
<div
    x-data="{
        show: true,
        timer: null,
        remaining: 60,
        copiedId: null,
        init() {
            this.timer = setInterval(() => {
                this.remaining -= 1;
                if (this.remaining <= 0) this.close();
            }, 1000);
        },
        close() {
            this.show = false;
            if (this.timer) { clearInterval(this.timer); this.timer = null; }
            document.body.classList.remove('overflow-y-hidden');
        },
        copyPromo(code, id) {
            const done = () => {
                this.copiedId = id;
                setTimeout(() => { if (this.copiedId === id) this.copiedId = null; }, 1600);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(code).then(done).catch(() => {
                    window.prompt('Copiază codul promoțional:', code);
                });
            } else {
                window.prompt('Copiază codul promoțional:', code);
            }
        }
    }"
    x-init="document.body.classList.add('overflow-y-hidden'); init()"
    x-on:keydown.escape.window="close()"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-[80] flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="login-account-title"
>
    <div class="absolute inset-0 bg-slate-900/55" x-on:click="close()"></div>

    <div class="relative w-full max-w-lg rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden"
         x-on:click.stop>
        <div class="px-5 py-4 border-b border-slate-100 flex items-start justify-between gap-3 bg-gradient-to-br from-teal-50 to-white">
            <div class="min-w-0">
                <h2 id="login-account-title" class="font-display text-xl text-slate-900">Contul tău</h2>
                <p class="text-xs text-slate-500 mt-0.5">Se închide automat în <span class="tabular-nums font-semibold text-teal-800" x-text="remaining"></span>s · ESC pentru a ieși</p>
            </div>
            <button type="button" class="dc-btn-secondary text-xs shrink-0" x-on:click="close()" aria-label="{{ __('Închide') }}">{{ __('Închide') }}</button>
        </div>

        <div class="px-5 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
            <dl class="grid grid-cols-1 gap-2 text-sm">
                <div class="rounded-lg border border-slate-100 bg-slate-50/80 px-3 py-2">
                    <dt class="text-[11px] uppercase tracking-wide text-slate-500">Nume și prenume</dt>
                    <dd class="font-semibold text-slate-900 mt-0.5">{{ $user->name }}</dd>
                </div>
                <div class="rounded-lg border border-slate-100 bg-slate-50/80 px-3 py-2">
                    <dt class="text-[11px] uppercase tracking-wide text-slate-500">Adresă de email</dt>
                    <dd class="font-semibold text-slate-900 mt-0.5 break-all">{{ $user->email }}</dd>
                </div>
            </dl>

            <div>
                <div class="text-[11px] uppercase tracking-wide text-slate-500 mb-2">Societăți active</div>
                @if($companies->isEmpty())
                    <p class="text-sm text-slate-600 rounded-lg border border-dashed border-slate-200 px-3 py-3">
                        Nu ai încă nicio societate. Adaugă una din meniul Societățile mele.
                    </p>
                @else
                    <ul class="space-y-2">
                        @foreach($companies as $company)
                            @php
                                $owner = $company->owner ?: $user;
                                $until = $accessGate->effectiveAccessUntil($owner);
                            @endphp
                            <li class="rounded-lg border border-slate-200 px-3 py-2.5">
                                <div class="flex flex-wrap items-start justify-between gap-2">
                                    <div class="font-semibold text-slate-900 text-sm min-w-0">{{ $company->name }}</div>
                                    <a href="{{ route('billing.order', $company) }}"
                                       class="dc-btn-primary text-xs px-2.5 py-1 inline-flex shrink-0"
                                       x-on:click="close()">{{ __('Comandă') }}</a>
                                </div>
                                <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-600">
                                    <span>
                                        Cod promo:
                                        @if(filled($company->promo_code))
                                            <button type="button"
                                                    class="font-mono font-semibold text-teal-800 hover:underline"
                                                    title="Click pentru a copia"
                                                    x-on:click="copyPromo(@js($company->promo_code), {{ $company->id }})">
                                                <span x-show="copiedId !== {{ $company->id }}">{{ $company->promo_code }}</span>
                                                <span x-show="copiedId === {{ $company->id }}" x-cloak class="text-teal-700">Copiat!</span>
                                            </button>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </span>
                                    <span>
                                        Expiră:
                                        <strong class="text-slate-800">
                                            @if($owner->is_admin)
                                                nelimitat
                                            @elseif($until)
                                                {{ dc_date($until) }}
                                            @elseif($owner->plan === 'paid')
                                                abonament activ
                                            @else
                                                —
                                            @endif
                                        </strong>
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
