@php
    /** @var string $variant light|dark|plain */
    $variant = $variant ?? 'light';
    $class = match ($variant) {
        'dark' => 'mkt-lang mkt-lang--dark',
        'plain' => 'mkt-lang mkt-lang--plain',
        default => 'mkt-lang mkt-lang--light',
    };
@endphp
<form method="POST" action="{{ route('ui-locale.update') }}" class="{{ $class }}" title="{{ __('Limbă interfață') }}">
    @csrf
    <label class="sr-only" for="public-ui-locale-{{ $variant }}">{{ __('Limbă interfață') }}</label>
    <select id="public-ui-locale-{{ $variant }}"
            name="ui_locale"
            onchange="this.form.submit()"
            aria-label="{{ __('Limbă interfață') }}"
            title="{{ __('Limbă interfață') }}">
        @foreach(ui_locale_options() as $code => $label)
            <option value="{{ $code }}" @selected(app()->getLocale() === $code)>{{ $label }}</option>
        @endforeach
    </select>
</form>
