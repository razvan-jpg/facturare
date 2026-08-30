@php
    $name = $name ?? 'county';
    $value = dc_normalize_county(old($name, $value ?? ''));
    $required = $required ?? false;
    $counties = config('romania.counties', []);
    $hasValue = filled($value);
    $inList = $hasValue && in_array($value, $counties, true);
@endphp
<div>
    <label class="dc-label" for="{{ $name }}">{{ __('Județ') }}</label>
    <select name="{{ $name }}" id="{{ $name }}" class="dc-input" @if($required) required @endif>
        <option value="">— selectează județul —</option>
        @if($hasValue && ! $inList)
            <option value="{{ $value }}" selected>{{ $value }}</option>
        @endif
        @foreach($counties as $county)
            <option value="{{ $county }}" @selected($value === $county)>{{ $county }}</option>
        @endforeach
    </select>
</div>
