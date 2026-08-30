@php
    $widgetKey = $widgetKey ?? '';
    $meta = (array) config("dashboard.widgets.{$widgetKey}", []);
    $span = max(1, (int) ($meta['span'] ?? 1));
    $title = (string) ($meta['title'] ?? $widgetKey);
    $settings = ($widgetSettings[$widgetKey] ?? []) ?: (array) config('dashboard.default_settings', []);
    $formId = 'dc-cfg-'.$widgetKey;
@endphp
<div class="dc-dash-widget {{ $span >= 2 ? 'dc-dash-widget--wide' : '' }}"
     data-widget="{{ $widgetKey }}"
     draggable="false"
     x-data="{ menu: false, mode: 'view' }"
     :class="{ 'is-panel': mode !== 'view' }"
     @keydown.escape.window="menu = false; if (mode !== 'view') mode = 'view'">

    {{-- Chrome normal --}}
    <div class="dc-dash-widget__head dc-dash-widget__chrome" x-show="mode === 'view'" data-drag-handle title="{{ __('Trage pentru a muta') }}">
        <div class="dc-dash-widget__head-left">
            <button type="button" class="dc-dash-widget__drag" data-drag-handle aria-label="{{ __('Mută widget') }}" title="{{ __('Trage pentru a muta') }}">
                <svg width="10" height="16" viewBox="0 0 10 16" aria-hidden="true">
                    <circle cx="2.5" cy="2.5" r="1.2" fill="currentColor"/><circle cx="7.5" cy="2.5" r="1.2" fill="currentColor"/>
                    <circle cx="2.5" cy="8" r="1.2" fill="currentColor"/><circle cx="7.5" cy="8" r="1.2" fill="currentColor"/>
                    <circle cx="2.5" cy="13.5" r="1.2" fill="currentColor"/><circle cx="7.5" cy="13.5" r="1.2" fill="currentColor"/>
                </svg>
            </button>
            <div class="dc-dash-widget__title">{{ $title }}</div>
        </div>
        <div class="dc-dash-widget__head-right" @click.stop>
            <button type="button" class="dc-dash-widget__icon-btn" onclick="window.location.reload()" title="{{ __('Reîmprospătează') }}" aria-label="{{ __('Reîmprospătează') }}">
                <svg width="15" height="15" viewBox="0 0 20 20" aria-hidden="true"><path fill="currentColor" d="M10 3a7 7 0 0 1 6.56 4.57l1.7-.62A9 9 0 0 0 3.4 6.4L1.96 4.96 1 8.5l3.54-.96L3.4 6.4A7 7 0 0 1 10 3Zm0 14a7 7 0 0 1-6.56-4.57l-1.7.62A9 9 0 0 0 16.6 13.6l1.44 1.44.96-3.54-3.54.96 1.14 1.14A7 7 0 0 1 10 17Z"/></svg>
            </button>
            <div class="dc-dash-widget__menu-wrap">
                <button type="button" class="dc-dash-widget__icon-btn" @click="menu = !menu" :aria-expanded="menu.toString()" title="{{ __('Opțiuni') }}" aria-label="{{ __('Opțiuni') }}">
                    <svg width="4" height="14" viewBox="0 0 4 14" aria-hidden="true">
                        <circle cx="2" cy="2" r="1.5" fill="currentColor"/><circle cx="2" cy="7" r="1.5" fill="currentColor"/><circle cx="2" cy="12" r="1.5" fill="currentColor"/>
                    </svg>
                </button>
                <div class="dc-dash-widget__menu" x-cloak x-show="menu" x-transition.opacity.duration.100ms @click.outside="menu = false" role="menu">
                    <button type="button" role="menuitem" class="dc-dash-widget__menu-item" @click="menu = false; mode = 'configure'">{{ __('Configurează') }}</button>
                    <button type="button" role="menuitem" class="dc-dash-widget__menu-item" @click="menu = false; mode = 'details'">{{ __('Detalii') }}</button>
                    <form method="POST" action="{{ route('dashboard.widgets.destroy', $widgetKey) }}" role="none">
                        @csrf
                        @method('DELETE')
                        <button type="submit" role="menuitem" class="dc-dash-widget__menu-item dc-dash-widget__menu-item--danger">{{ __('Șterge') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Chrome panou Configurări / Detalii --}}
    <div class="dc-dash-widget__panel-head" x-cloak x-show="mode !== 'view'">
        <div class="dc-dash-widget__panel-titles">
            <div class="dc-dash-widget__title dc-dash-widget__title--light">{{ $title }}</div>
            <div class="dc-dash-widget__panel-sub" x-text="mode === 'configure' ? @js(__('Configurări')) : @js(__('Detalii'))"></div>
        </div>
        <button type="submit"
                form="{{ $formId }}"
                class="dc-dash-widget__panel-done"
                x-show="mode === 'configure'"
                title="{{ __('Salvează') }}"
                aria-label="{{ __('Salvează') }}">✓</button>
        <button type="button"
                class="dc-dash-widget__panel-done"
                x-show="mode === 'details'"
                @click="mode = 'view'"
                title="{{ __('Închide') }}"
                aria-label="{{ __('Închide') }}">✓</button>
    </div>

    <div x-show="mode === 'view'">
        @include('dashboard.widgets.'.$widgetKey)
    </div>
    <div class="dc-dash-widget__panel-body" x-cloak x-show="mode === 'configure'">
        @include('dashboard.partials.configure-panel', ['widgetKey' => $widgetKey, 'settings' => $settings, 'formId' => $formId])
    </div>
    <div class="dc-dash-widget__panel-body" x-cloak x-show="mode === 'details'">
        @include('dashboard.partials.details-panel', ['widgetKey' => $widgetKey])
    </div>
</div>
