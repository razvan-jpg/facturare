@php
    $companies = auth()->user()?->companies()
        ->select(
            'companies.id',
            'companies.name',
            'companies.promo_code',
            'companies.referred_by_company_id',
            'companies.referral_rewards_granted'
        )
        ->withCount('referredCompanies')
        ->orderBy('name')
        ->get() ?? collect();
    $current = $currentCompany ?? null;
    if (! $current && auth()->check()) {
        try {
            $current = app(\App\Services\CompanyContext::class)->current(auth()->user());
        } catch (\Throwable $e) {
            $current = null;
        }
    }
    if (! $current && isset($company) && $company instanceof \App\Models\Company) {
        $current = $company;
    }
    if ($current) {
        $fromList = $companies->firstWhere('id', $current->id);
        if ($fromList && (blank($current->promo_code) || ! isset($current->referred_companies_count))) {
            $current = $fromList;
        }
    }

    $subscription = auth()->check()
        ? app(\App\Services\AccessGate::class)->subscriptionSummary(auth()->user())
        : [
            'plan' => 'none',
            'plan_label' => '—',
            'ends_at' => null,
            'days_remaining' => null,
            'progress' => 0,
            'promotions' => [],
            'label' => null,
        ];

    $nav = config('nav', []);
    $perm = app(\App\Services\CompanyPermission::class);
    $navUser = auth()->user();
    $navAbilityMap = config('company_permissions.nav', []);
    $canNavSection = function (string $key) use ($perm, $navUser, $current, $navAbilityMap): bool {
        if (! isset($navAbilityMap[$key])) {
            return true;
        }
        if (! $current) {
            return false;
        }

        return $perm->canAny($navUser, $current, $navAbilityMap[$key]);
    };
    $canSettingsTab = function (array $item) use ($perm, $navUser, $current): bool {
        // owners_only + show_locked: rămâne vizibil (blocat) pentru subuseri.
        if (! empty($item['owners_only']) && ! $navUser?->canManageCompanyUsers() && empty($item['show_locked'])) {
            return false;
        }
        $tab = $item['tab'] ?? null;
        if (! $tab) {
            return true;
        }
        // Preferințe personale: orice membru.
        if ($tab === 'preferinte-personale') {
            return $current && $perm->can($navUser, $current, 'access');
        }
        // e-Factura: drept dedicat sau setări.
        if ($tab === 'efactura') {
            return $current && (
                $perm->can($navUser, $current, 'efactura_view')
                || $perm->can($navUser, $current, 'settings_view')
            );
        }

        // Restul tab-urilor de firmă: vizualizare setări (sau owner/admin).
        return $current && $perm->can($navUser, $current, 'settings_view');
    };
    $isLockedNavItem = function (array $item) use ($navUser): bool {
        return ! empty($item['owners_only'])
            && ! empty($item['show_locked'])
            && $navUser
            && ! $navUser->canManageCompanyUsers();
    };

    $activePanel = null;
    foreach ($nav as $key => $section) {
        foreach ($section['match'] ?? [] as $pattern) {
            if (request()->routeIs($pattern)) {
                if ($key === 'liste' && request()->routeIs('recurring.create')) {
                    continue;
                }
                $activePanel = $key;
                break 2;
            }
        }
        if ($key === 'setari' && request()->routeIs('companies.*') && ! request()->boolean('all')) {
            $activePanel = 'setari';
            break;
        }
        if ($key === 'setari' && request()->routeIs('companies.index') && request()->boolean('all')) {
            $activePanel = 'setari';
            break;
        }
        if ($key === 'setari' && request()->routeIs('company-users.*', 'billing.seats*')) {
            $activePanel = 'setari';
            break;
        }
    }
    if (request()->routeIs('recurring.create')) {
        $activePanel = 'emite';
    }
    if (request()->routeIs('dashboard')) {
        $activePanel = null;
    }

    $resolveUrl = function (array $item) use ($current): string {
        if (! empty($item['tab'])) {
            if (! $current) {
                return route('companies.index');
            }

            return route('companies.edit', ['company' => $current, 'tab' => $item['tab']]);
        }

        if (($item['url'] ?? '') === '/billing/locuri') {
            if ($current) {
                return route('billing.seats', $current);
            }

            return route('companies.index', ['all' => 1]);
        }

        return url($item['url'] ?? '/dashboard');
    };

    $itemActive = function (array $item) use ($current): bool {
        if (! empty($item['tab'])) {
            return request()->routeIs('companies.edit')
                && $current
                && (int) request()->route('company')?->id === (int) $current->id
                && request('tab', 'generale') === $item['tab'];
        }

        $match = $item['match'] ?? null;
        if (! $match || ! request()->routeIs($match)) {
            return false;
        }
        if (! empty($item['except']) && request()->routeIs($item['except'])) {
            return false;
        }
        if (! empty($item['type'])) {
            return request('type', 'invoice') === $item['type'];
        }
        if (($item['url'] ?? '') === '/companies?all=1') {
            return request()->boolean('all');
        }
        // Pentru Ajutor / Legal: evidențiază doar URL-ul curent.
        if (! empty($item['url']) && (
            request()->routeIs('help.*') || request()->routeIs('legal.*')
            || str_starts_with((string) ($item['match'] ?? ''), 'help.')
            || str_starts_with((string) ($item['match'] ?? ''), 'legal.')
        )) {
            return rtrim(request()->path(), '/') === trim($item['url'], '/');
        }

        return true;
    };
@endphp

<header class="dc-topnav"
        x-data="{
            panel: null,
            mobileOpen: false,
            accountOpen: false,
            switchOpen: false,
            notifOpen: false,
            toggle(key) { this.accountOpen = false; this.switchOpen = false; this.notifOpen = false; this.panel = this.panel === key ? null : key; },
            toggleAccount() { this.panel = null; this.notifOpen = false; this.accountOpen = !this.accountOpen; if (!this.accountOpen) this.switchOpen = false; },
            toggleNotif() { this.panel = null; this.accountOpen = false; this.switchOpen = false; this.notifOpen = !this.notifOpen; },
            closeAll() { this.panel = null; this.mobileOpen = false; this.accountOpen = false; this.switchOpen = false; this.notifOpen = false; }
        }"
        @keydown.escape.window="closeAll()"
        @click.outside="panel = null; accountOpen = false; switchOpen = false; notifOpen = false">

    <div class="dc-topnav-bar">
        <a href="{{ route('home') }}" class="dc-topnav-brand" title="{{ __('Pagina principală') }}" @click="closeAll()">
            <img src="{{ asset('images/brand/dateconta-icon.png') }}" alt="DateConta">
            <span class="dc-topnav-brand-text">
                <strong>DateConta</strong>
                <em>{{ __('Facturare') }}</em>
            </span>
        </a>

        <nav class="dc-topnav-menu" :class="mobileOpen ? 'is-open' : ''" aria-label="{{ __('Navigare') }}">
            <a href="{{ route('dashboard') }}"
               class="dc-topnav-item {{ request()->routeIs('dashboard') ? 'is-current' : '' }}"
               title="{{ __('Acasă') }}"
               @click="closeAll()">
                @include('partials.nav-icon', ['name' => 'home'])
                <span class="dc-topnav-label">{{ __('Acasă') }}</span>
            </a>

            @foreach($nav as $key => $section)
                @continue(! $canNavSection($key) && in_array($key, ['emite', 'liste', 'catalog', 'rapoarte'], true))
                <div class="dc-nav-dd" @click.stop>
                    <button type="button"
                            class="dc-topnav-item"
                            title="{{ __($section['label']) }}"
                            :class="panel === @js($key) ? 'is-open' : (@js($activePanel) === @js($key) ? 'is-current' : '')"
                            :aria-expanded="panel === @js($key) ? 'true' : 'false'"
                            @click.stop="toggle(@js($key))">
                        @include('partials.nav-icon', ['name' => $section['icon']])
                        <span class="dc-topnav-label">{{ __($section['label']) }}</span>
                        <svg class="dc-topnav-caret" viewBox="0 0 12 12" aria-hidden="true"><path d="M2.5 4.5 6 8l3.5-3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>

                    <div class="dc-dropdown"
                         x-show="panel === @js($key)"
                         x-cloak
                         x-transition:enter="dc-drop-enter"
                         x-transition:enter-start="dc-drop-enter-start"
                         x-transition:enter-end="dc-drop-enter-end"
                         x-transition:leave="dc-drop-leave"
                         x-transition:leave-start="dc-drop-leave-start"
                         x-transition:leave-end="dc-drop-leave-end"
                         @click.stop>
                        <div class="dc-dropdown-head">{{ __($section['label']) }}</div>

                        @if($key === 'setari' && $companies->isNotEmpty())
                            <div class="dc-dropdown-company">
                                <label>{{ __('Societate') }}</label>
                                <select onchange="switchCompany(this.value)" aria-label="{{ __('Societate') }}">
                                    @foreach($companies as $c)
                                        <option value="{{ $c->id }}" @selected($current && $current->id === $c->id)>{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @foreach($section['groups'] as $group)
                            @continue(! empty($group['admin_only']) && ! auth()->user()?->is_admin)
                            @php
                                $visibleItems = collect($group['items'] ?? [])->filter(function ($item) use ($key, $canSettingsTab, $perm, $navUser, $current) {
                                    if ($key === 'setari') {
                                        return $canSettingsTab($item);
                                    }
                                    // Filtrare fină pe iteme Emite/Liste/Catalog.
                                    $label = $item['label'] ?? '';
                                    if ($key === 'catalog') {
                                        if ($label === 'Clienți') {
                                            return $perm->can($navUser, $current, 'clients_view');
                                        }
                                        if (str_contains($label, 'Produse')) {
                                            return $perm->can($navUser, $current, 'products_view');
                                        }
                                    }
                                    if ($key === 'emite') {
                                        if (($item['match'] ?? '') === 'payments.index') {
                                            return $perm->can($navUser, $current, 'payments_view');
                                        }
                                        if (str_contains($label, 'Încasare') || ($item['match'] ?? '') === 'payments.create') {
                                            return $perm->can($navUser, $current, 'payments_manage');
                                        }
                                        if (str_contains($label, 'recurent')) {
                                            return $perm->can($navUser, $current, 'recurring_manage');
                                        }

                                        return $perm->can($navUser, $current, 'documents_manage');
                                    }
                                    if ($key === 'liste') {
                                        if (str_contains($label, 'Recurent')) {
                                            return $perm->can($navUser, $current, 'recurring_view');
                                        }

                                        return $perm->can($navUser, $current, 'documents_view');
                                    }
                                    if ($key === 'rapoarte') {
                                        return $perm->can($navUser, $current, 'reports_view');
                                    }

                                    return true;
                                });
                            @endphp
                            @continue($visibleItems->isEmpty())
                            @if(! empty($group['title']))
                                <div class="dc-dropdown-group">{{ __($group['title']) }}</div>
                            @endif
                            @foreach($visibleItems as $item)
                                @php
                                    $href = $resolveUrl($item);
                                    $active = $itemActive($item);
                                    if (! empty($item['url']) && str_contains((string) $item['url'], '/admin/integrari/')) {
                                        $active = request()->is(ltrim($item['url'], '/'));
                                    }
                                    if (($item['match'] ?? null) === 'company-users.*') {
                                        $active = request()->routeIs('company-users.*');
                                    }
                                    if (($item['match'] ?? null) === 'billing.seats*') {
                                        $active = request()->routeIs('billing.seats*');
                                    }
                                    $locked = $isLockedNavItem($item);
                                @endphp
                                @if($locked)
                                    <span class="dc-dropdown-link opacity-50 cursor-not-allowed"
                                          title="{{ __('Doar proprietarul contului poate gestiona această secțiune.') }}"
                                          aria-disabled="true">
                                        {{ __($item['label']) }}
                                        <em class="block text-[10px] font-normal not-italic text-slate-400">{{ __('doar proprietar') }}</em>
                                    </span>
                                @else
                                    <a href="{{ $href }}"
                                       class="dc-dropdown-link {{ $active ? 'is-active' : '' }}"
                                       @click="closeAll()">
                                        {{ __($item['label']) }}
                                    </a>
                                @endif
                            @endforeach
                        @endforeach
                    </div>
                </div>
            @endforeach

            @if(auth()->user()?->is_admin)
                <div class="dc-nav-dd" @click.stop>
                    <button type="button"
                            class="dc-topnav-item"
                            title="{{ __('Admin') }}"
                            :class="panel === 'admin' ? 'is-open' : (@js(request()->routeIs('admin.*')) ? 'is-current' : '')"
                            :aria-expanded="panel === 'admin' ? 'true' : 'false'"
                            @click.stop="toggle('admin')">
                        @include('partials.nav-icon', ['name' => 'admin'])
                        <span class="dc-topnav-label">{{ __('Admin') }}</span>
                        <svg class="dc-topnav-caret" viewBox="0 0 12 12" aria-hidden="true"><path d="M2.5 4.5 6 8l3.5-3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <div class="dc-dropdown"
                         x-show="panel === 'admin'"
                         x-cloak
                         x-transition:enter="dc-drop-enter"
                         x-transition:enter-start="dc-drop-enter-start"
                         x-transition:enter-end="dc-drop-enter-end"
                         x-transition:leave="dc-drop-leave"
                         x-transition:leave-start="dc-drop-leave-start"
                         x-transition:leave-end="dc-drop-leave-end"
                         @click.stop>
                        <div class="dc-dropdown-head">{{ __('Admin') }}</div>
                        <a href="{{ route('admin.stats') }}" class="dc-dropdown-link {{ request()->routeIs('admin.stats') ? 'is-active' : '' }}" @click="closeAll()">{{ __('Statistici') }}</a>
                        <a href="{{ route('admin.companies') }}" class="dc-dropdown-link {{ request()->routeIs('admin.companies*') ? 'is-active' : '' }}" @click="closeAll()">{{ __('Societăți & promoții') }}</a>
                        <a href="{{ route('admin.users') }}" class="dc-dropdown-link {{ request()->routeIs('admin.users*') ? 'is-active' : '' }}" @click="closeAll()">{{ __('Utilizatori') }}</a>
                        <a href="{{ route('admin.orders') }}" class="dc-dropdown-link {{ request()->routeIs('admin.orders*') ? 'is-active' : '' }}" @click="closeAll()">{{ __('Comenzi OP / abonament') }}</a>
                        <button type="button"
                                class="dc-dropdown-link w-full text-left"
                                @click="closeAll(); window.dispatchEvent(new CustomEvent('open-admin-promo-mail'))">
                            Trimite mail reclamă
                        </button>
                        <div class="dc-dropdown-group">{{ __('Abonament DateConta (FLY DAVID)') }}</div>
                        <a href="{{ route('admin.integrari.show', 'netopia') }}" class="dc-dropdown-link {{ request()->is('admin/integrari/netopia') ? 'is-active' : '' }}" @click="closeAll()">NETOPIA</a>
                        <a href="{{ route('admin.integrari.show', 'euplatesc') }}" class="dc-dropdown-link {{ request()->is('admin/integrari/euplatesc') ? 'is-active' : '' }}" @click="closeAll()">Eu Plătesc</a>
                        <a href="{{ route('admin.integrari.show', 'mollie') }}" class="dc-dropdown-link {{ request()->is('admin/integrari/mollie') ? 'is-active' : '' }}" @click="closeAll()">Mollie</a>
                        <a href="{{ route('admin.integrari.show', 'stripe') }}" class="dc-dropdown-link {{ request()->is('admin/integrari/stripe') ? 'is-active' : '' }}" @click="closeAll()">Stripe</a>
                    </div>
                </div>
            @endif

            <div class="dc-topnav-mobile-account lg:hidden">
                <form method="POST" action="{{ route('ui-locale.update') }}" class="px-3 py-2">
                    @csrf
                    <label class="block text-xs text-slate-400 mb-1">{{ __('Limbă interfață') }}</label>
                    <select name="ui_locale" onchange="this.form.submit()" class="w-full rounded-lg border border-slate-600 bg-slate-800 text-white text-sm px-2 py-1.5" aria-label="{{ __('Limbă interfață') }}">
                        @foreach(ui_locale_options() as $code => $label)
                            <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
                <div class="dc-account-mobile-embed">
                    @include('partials.account-menu', ['embedded' => true])
                </div>
            </div>
        </nav>

        <div class="dc-topnav-right">
            @php
                $unreadNotifs = auth()->user()?->unreadNotifications?->take(8) ?? collect();
                $unreadCount = auth()->user()?->unreadNotifications?->count() ?? 0;
            @endphp
            <div class="dc-nav-dd relative" @click.stop>
                <button type="button"
                        class="dc-topnav-notif"
                        title="{{ __('Notificări') }}"
                        :class="notifOpen ? 'is-open' : ''"
                        :aria-expanded="notifOpen ? 'true' : 'false'"
                        @click.stop="toggleNotif()">
                    <svg class="dc-topnav-tool-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M12 2a6 6 0 0 0-6 6v3.1l-1.6 3.2A1 1 0 0 0 5.3 16H18.7a1 1 0 0 0 .9-1.7L18 11.1V8a6 6 0 0 0-6-6Zm0 20a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Z"/>
                    </svg>
                    @if($unreadCount > 0)
                        <span class="dc-topnav-notif-badge">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                    @endif
                </button>
                <div class="dc-dropdown !right-0 !left-auto w-[min(22rem,calc(100vw-1.5rem))]"
                     x-show="notifOpen"
                     x-cloak
                     x-transition
                     @click.stop>
                    <div class="dc-dropdown-head flex items-center justify-between gap-2">
                        <span>{{ __('Notificări') }}</span>
                        @if($unreadCount > 0)
                            <form method="POST" action="{{ route('notifications.read-all') }}">
                                @csrf
                                <button type="submit" class="text-[11px] font-semibold text-teal-700 hover:underline">Marchează citite</button>
                            </form>
                        @endif
                    </div>
                    @forelse($unreadNotifs as $note)
                        @php($data = $note->data)
                        <div class="px-3 py-2.5 border-b border-slate-100 last:border-0">
                            <div class="text-sm font-semibold text-slate-900">{{ $data['title'] ?? 'Notificare' }}</div>
                            <p class="text-xs text-slate-600 mt-0.5 leading-snug">{{ $data['body'] ?? '' }}</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @if(! empty($data['order_url']))
                                    <a href="{{ $data['order_url'] }}" class="dc-btn-primary text-[11px] px-2.5 py-1" @click="closeAll()">{{ __('Comandă') }}</a>
                                @endif
                                <form method="POST" action="{{ route('notifications.read', $note->id) }}">
                                    @csrf
                                    <button type="submit" class="dc-btn-secondary text-[11px] px-2.5 py-1">Am înțeles</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="px-3 py-4 text-sm text-slate-500">Nu ai notificări noi.</div>
                    @endforelse
                </div>
            </div>

            <form method="POST" action="{{ route('ui-locale.update') }}" class="dc-topnav-tool dc-topnav-lang" title="{{ __('Limbă interfață') }}">
                @csrf
                <svg class="dc-topnav-tool-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2.5a9.5 9.5 0 1 1 0 19 9.5 9.5 0 0 1 0-19Zm0 1.8c-.7 1.5-1.1 3.3-1.2 5.2h2.4c-.1-1.9-.5-3.7-1.2-5.2Zm-3.1.7c-.9 1.4-1.5 3.2-1.7 5.2H4.4a7.7 7.7 0 0 1 4.5-5.2Zm6.2 0a7.7 7.7 0 0 1 4.5 5.2h-2.8c-.2-2-.8-3.8-1.7-5.2ZM4.4 11.5h2.8c.1 1.9.6 3.8 1.4 5.3A7.7 7.7 0 0 1 4.4 11.5Zm4.6 0h2.5c.1 2 .5 3.9 1.2 5.5-.8-1.4-1.3-3.3-1.4-5.5h-.1c-.1 2.2-.6 4.1-1.4 5.5-.7-1.6-1.1-3.5-1.2-5.5Zm4.2 0h2.5c-.1 2-.6 3.9-1.4 5.5.8-1.4 1.3-3.3 1.4-5.5Zm4.5 0h2.8a7.7 7.7 0 0 1-4.2 5.3c.8-1.5 1.3-3.4 1.4-5.3Z"/></svg>
                <select name="ui_locale" onchange="this.form.submit()" aria-label="{{ __('Limbă interfață') }}" title="{{ __('Limbă interfață') }}">
                    @foreach(ui_locale_options() as $code => $label)
                        <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>

            <div class="dc-account-dd" @click.stop>
                <button type="button"
                        class="dc-topnav-company-trigger"
                        :class="accountOpen ? 'is-open' : ''"
                        :aria-expanded="accountOpen ? 'true' : 'false'"
                        title="{{ __('Societate și cont') }}"
                        @click.stop="toggleAccount()">
                    <span class="dc-topnav-company-name">{{ $current?->name ?: __('Alege societatea') }}</span>
                    <svg class="dc-topnav-caret" viewBox="0 0 12 12" aria-hidden="true"><path d="M2.5 4.5 6 8l3.5-3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                @include('partials.account-menu')
            </div>

            <button type="button"
                    class="dc-topnav-burger"
                    @click="mobileOpen = !mobileOpen; accountOpen = false; if (!mobileOpen) panel = null"
                    aria-label="{{ __('Meniu') }}"
                    title="{{ __('Meniu') }}">
                <svg x-show="!mobileOpen" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M4 6.5h16a1 1 0 1 1 0 2H4a1 1 0 1 1 0-2Zm0 4.5h16a1 1 0 1 1 0 2H4a1 1 0 1 1 0-2Zm0 4.5h16a1 1 0 1 1 0 2H4a1 1 0 1 1 0-2Z"/></svg>
                <svg x-cloak x-show="mobileOpen" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5"><path d="M6.2 5.1 12 10.9l5.8-5.8a1 1 0 1 1 1.4 1.4L13.4 12l5.8 5.8a1 1 0 1 1-1.4 1.4L12 13.4l-5.8 5.8a1 1 0 1 1-1.4-1.4L10.6 12 4.8 6.2a1 1 0 0 1 1.4-1.1Z"/></svg>
            </button>
        </div>
    </div>

    <div class="dc-topnav-scrim lg:hidden" x-show="mobileOpen" x-cloak @click="closeAll()"></div>
</header>
