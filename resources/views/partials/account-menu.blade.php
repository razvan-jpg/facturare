@php
    /** @var \App\Models\Company|null $current */
    /** @var \Illuminate\Support\Collection $companies */
    /** @var array $subscription */
    $embedded = $embedded ?? false;
    $user = auth()->user();
    $days = $subscription['days_remaining'] ?? null;
    $progress = (int) ($subscription['progress'] ?? 0);
    $planLabel = $subscription['plan_label'] ?? '—';
@endphp

<div class="dc-account-panel {{ $embedded ? 'is-embedded' : '' }}"
     @unless($embedded)
         x-show="accountOpen"
         x-cloak
         x-transition:enter="dc-drop-enter"
         x-transition:enter-start="dc-drop-enter-start"
         x-transition:enter-end="dc-drop-enter-end"
         x-transition:leave="dc-drop-leave"
         x-transition:leave-start="dc-drop-leave-start"
         x-transition:leave-end="dc-drop-leave-end"
         @click.stop
     @endunless
     role="menu"
     aria-label="{{ __('Cont și societate') }}">

    @unless($embedded)
        <div class="dc-account-panel-caret" aria-hidden="true"></div>
    @endunless

    <div class="dc-account-section dc-account-head">
        <div class="dc-account-user">{{ $user->name }}</div>
        <div class="dc-account-email">{{ $user->email }}</div>
        @if($current)
            <div class="dc-account-company" title="{{ $current->name }}">{{ $current->name }}</div>
        @else
            <div class="dc-account-company dc-account-muted">{{ __('Nicio societate activă') }}</div>
        @endif
    </div>

    <div class="dc-account-section">
        <div class="dc-account-meta-row">
            <span class="dc-account-meta-label">{{ __('Cod promoțional') }}</span>
            @if(filled($current?->promo_code))
                <button type="button"
                        class="dc-account-code"
                        title="{{ __('Click pentru a copia codul') }}"
                        x-data="{ copied: false }"
                        @click.stop="
                            navigator.clipboard.writeText(@js($current->promo_code)).then(() => {
                                copied = true;
                                setTimeout(() => copied = false, 1600);
                            }).catch(() => {
                                window.prompt(@js(__('Copiază codul promoțional:')), @js($current->promo_code));
                            })
                        ">
                    <span x-show="!copied">{{ $current->promo_code }}</span>
                    <span x-cloak x-show="copied">{{ __('Copiat!') }}</span>
                </button>
            @else
                <span class="dc-account-code is-empty">—</span>
            @endif
        </div>
        <div class="dc-account-promos">
            <div class="dc-account-meta-label">{{ __('Promoții primite') }}</div>
            <ul>
                @php
                    $promoLines = $subscription['promotions'] ?? [];
                    if ($current?->referred_by_company_id) {
                        $promoLines[] = __('Bonus la creare: +2 săptămâni (cod recomandare)');
                    }
                    if ($current) {
                        $brought = (int) ($current->referred_companies_count
                            ?? $current->referredCompanies()->count());
                        $rewards = (int) ($current->referral_rewards_granted ?? 0);
                        if ($brought > 0) {
                            $promoLines[] = $brought === 1
                                ? __('1 societate adusă prin codul tău')
                                : __(':count societăți aduse prin codul tău', ['count' => $brought]);
                        }
                        if ($rewards > 0) {
                            $promoLines[] = $rewards === 1
                                ? __('1 × bonus recomandare (+1 lună)')
                                : __(':count × bonus recomandare (+1 lună fiecare)', ['count' => $rewards]);
                        }
                    }
                    $promoLines = array_values(array_unique($promoLines));
                @endphp
                @forelse($promoLines as $promo)
                    <li>{{ $promo }}</li>
                @empty
                    <li class="dc-account-muted">{{ __('Nicio promoție activă') }}</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="dc-account-section dc-account-plan">
        <div class="dc-account-plan-row">
            <span class="dc-account-plan-name">
                <svg viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M10 1.6 12.2 7h5.7l-4.6 3.4 1.8 5.5L10 12.8 4.9 15.9l1.8-5.5L2.1 7h5.7L10 1.6Z"/></svg>
                {{ $planLabel }}
            </span>
            <span class="dc-account-plan-days">
                @if($days === null)
                    {{ __('Nelimitat') }}
                @elseif($days <= 0)
                    {{ __('Expirat') }}
                @else
                    {{ $days }} {{ $days === 1 ? __('zi rămasă') : __('zile rămase') }}
                @endif
            </span>
        </div>
        <div class="dc-account-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progress }}" aria-label="{{ __('Perioadă abonament') }}">
            <span style="width: {{ $progress }}%"></span>
        </div>
        @if(! empty($subscription['ends_at']))
            <div class="dc-account-ends">{{ __('Valabil până la') }} {{ dc_date($subscription['ends_at']) }}</div>
        @endif
    </div>

    <div class="dc-account-section dc-account-actions">
        @if(filled($current?->promo_code))
            <button type="button"
                    class="dc-account-action"
                    @click.stop="
                        closeAll();
                        window.dispatchEvent(new CustomEvent('open-referral-mail', {
                            detail: {
                                id: {{ (int) $current->id }},
                                name: @js($current->name),
                                code: @js($current->promo_code)
                            }
                        }));
                    ">
                <svg viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M2.5 4.5A1.5 1.5 0 0 1 4 3h12a1.5 1.5 0 0 1 1.5 1.5v11A1.5 1.5 0 0 1 16 17H4A1.5 1.5 0 0 1 2.5 15.5v-11Zm1.6.5 5.6 4.2a.5.5 0 0 0 .6 0L15.9 5H4.1Zm11.4 1.7-4.9 3.7a2 2 0 0 1-2.4 0L3.3 6.7v8.3c0 .1.1.3.2.3h12.2c.2 0 .3-.1.3-.3V6.7Z"/></svg>
                <span>{{ __('Trimite mail recomandare') }}</span>
            </button>
        @endif

        <button type="button"
                class="dc-account-action"
                @click="switchOpen = !switchOpen"
                :aria-expanded="switchOpen ? 'true' : 'false'">
            <svg viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M7.2 4.2 3.5 8l3.7 3.8 1.3-1.3L6.9 9.1h7.4V7.1H6.9l1.6-1.6L7.2 4.2Zm5.6 11.6 3.7-3.8-3.7-3.8-1.3 1.3 1.6 1.6H5.7v2h7.4l-1.6 1.6 1.3 1.3Z"/></svg>
            <span>{{ __('Schimbă firma') }}</span>
            <svg class="dc-account-action-caret" :class="switchOpen && 'is-open'" viewBox="0 0 12 12" aria-hidden="true"><path d="M2.5 4.5 6 8l3.5-3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </button>

        <div class="dc-account-switch-list" x-show="switchOpen" x-cloak>
            @forelse($companies as $c)
                <button type="button"
                        class="dc-account-switch-item {{ $current && $current->id === $c->id ? 'is-current' : '' }}"
                        @if(! $current || $current->id !== $c->id)
                            @click="switchCompany({{ (int) $c->id }}); closeAll()"
                        @endif
                        @disabled($current && $current->id === $c->id)>
                    <span class="dc-account-switch-name">{{ $c->name }}</span>
                    @if($c->promo_code)
                        <span class="dc-account-switch-code">{{ $c->promo_code }}</span>
                    @endif
                </button>
            @empty
                <div class="dc-account-switch-empty">{{ __('Nu ai alte societăți.') }}</div>
            @endforelse
            <a href="{{ route('companies.index', ['all' => 1]) }}" class="dc-account-switch-all" @click="closeAll()">{{ __('Societățile mele') }}</a>
        </div>

        @unless($user->isSubUser())
        <a href="{{ route('companies.create') }}" class="dc-account-action" @click="closeAll()">
            <svg viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M9 3h2v6h6v2h-6v6H9v-6H3V9h6V3Z"/></svg>
            <span>{{ __('Adaugă o firmă nouă') }}</span>
        </a>
        @endunless

        <a href="{{ route('profile.edit') }}" class="dc-account-action" @click="closeAll()">
            <svg viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M10 2a4 4 0 1 1 0 8 4 4 0 0 1 0-8Zm0 10c3.5 0 6.5 1.8 6.5 4v1.2H3.5V16c0-2.2 3-4 6.5-4Z"/></svg>
            <span>{{ __('Contul meu') }}</span>
        </a>
    </div>

    <div class="dc-account-section dc-account-footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="dc-account-action dc-account-logout">
                <svg viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M8 3H4.5A1.5 1.5 0 0 0 3 4.5v11A1.5 1.5 0 0 0 4.5 17H8v-2H5V6.5h3V3Zm3.2 2.3-1.4 1.4L12.1 9H7v2h5.1l-2.3 2.3 1.4 1.4L16 10l-4.8-4.7Z"/></svg>
                <span>{{ __('Deconectare') }}</span>
            </button>
        </form>
    </div>
</div>
