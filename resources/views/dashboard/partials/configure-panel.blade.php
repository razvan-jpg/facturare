@php
    /** @var array<string,mixed> $settings */
    $settings = $settings ?? [];
    $fields = (array) (config("dashboard.widgets.{$widgetKey}.configure") ?? []);
@endphp
<form method="POST" action="{{ route('dashboard.widgets.configure', $widgetKey) }}" class="dc-dash-cfg" id="{{ $formId ?? ('dc-cfg-'.$widgetKey) }}">
    @csrf
    @if(in_array('sort_by', $fields, true))
        <div class="dc-dash-cfg__group">
            <div class="dc-dash-cfg__label">{{ __('Sortează după') }}</div>
            <label class="dc-dash-cfg__radio"><input type="radio" name="sort_by" value="value" @checked(($settings['sort_by'] ?? 'value') === 'value')> {{ __('Valoare') }}</label>
            <label class="dc-dash-cfg__radio"><input type="radio" name="sort_by" value="qty" @checked(($settings['sort_by'] ?? 'value') === 'qty')> {{ __('Cantitate') }}</label>
        </div>
    @endif

    @if(in_array('sort', $fields, true))
        <div class="dc-dash-cfg__group">
            <div class="dc-dash-cfg__label">{{ __('Sortează') }}</div>
            <label class="dc-dash-cfg__radio"><input type="radio" name="sort" value="desc" @checked(($settings['sort'] ?? 'desc') === 'desc')> {{ __('Descrescător') }}</label>
            <label class="dc-dash-cfg__radio"><input type="radio" name="sort" value="asc" @checked(($settings['sort'] ?? 'desc') === 'asc')> {{ __('Crescător') }}</label>
        </div>
    @endif

    @if(in_array('only_overdue', $fields, true))
        <label class="dc-dash-cfg__check">
            <input type="checkbox" name="only_overdue" value="1" @checked(! empty($settings['only_overdue']))>
            {{ __('Afișează doar facturile cu scadență depășită') }}
        </label>
    @endif

    @if(in_array('ignore_before', $fields, true))
        <label class="dc-dash-cfg__check">
            <input type="checkbox" name="ignore_before_enabled" value="1" @checked(! empty($settings['ignore_before_enabled']))
                   x-on:change="$refs.ignoreDate.disabled = ! $event.target.checked">
            {{ __('Ignoră documentele emise înainte de data:') }}
        </label>
        <input type="date"
               name="ignore_before"
               class="dc-dash-cfg__date"
               x-ref="ignoreDate"
               value="{{ $settings['ignore_before'] ?? '' }}"
               @disabled(empty($settings['ignore_before_enabled']))>
    @endif

    @if(in_array('currency', $fields, true))
        <div class="dc-dash-cfg__group dc-dash-cfg__group--row">
            <div class="dc-dash-cfg__label">{{ __('Monedă') }}</div>
            <select name="currency" class="dc-dash-cfg__select">
                @foreach(['RON', 'EUR', 'USD', 'GBP'] as $cur)
                    <option value="{{ $cur }}" @selected(($settings['currency'] ?? 'RON') === $cur)>{{ $cur }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @if(in_array('activity_filters', $fields, true))
        <label class="dc-dash-cfg__check"><input type="checkbox" name="show_issues" value="1" @checked(($settings['show_issues'] ?? true))> {{ __('Afișează Emiteri') }}</label>
        <label class="dc-dash-cfg__check"><input type="checkbox" name="show_payments" value="1" @checked(($settings['show_payments'] ?? true))> {{ __('Afișează Încasări') }}</label>
        <label class="dc-dash-cfg__check"><input type="checkbox" name="show_edits" value="1" @checked(! empty($settings['show_edits']))> {{ __('Afișează Editări') }}</label>
        <label class="dc-dash-cfg__check"><input type="checkbox" name="show_cancels" value="1" @checked(! empty($settings['show_cancels']))> {{ __('Afișează Anulări') }}</label>
        <label class="dc-dash-cfg__check"><input type="checkbox" name="show_deletes" value="1" @checked(! empty($settings['show_deletes']))> {{ __('Afișează Ștergeri') }}</label>
        <p class="dc-dash-cfg__hint">{{ __('Editări / anulări / ștergeri vor apărea pe măsură ce sunt înregistrate în jurnal.') }}</p>
    @endif

    @if($fields === [])
        <p class="dc-dash-cfg__hint">{{ __('Acest widget nu are opțiuni suplimentare. Apasă ✓ pentru a reveni.') }}</p>
    @endif
</form>
