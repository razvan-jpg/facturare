@php
    $name = $name ?? 'date';
    $id = $id ?? $name;
    $label = $label ?? 'Data';
    $required = $required ?? false;
    $value = dc_date_input(old($name, $value ?? null));
@endphp
<div>
    <label class="dc-label" for="{{ $id }}">{{ $label }}</label>
    <input
        type="text"
        name="{{ $name }}"
        id="{{ $id }}"
        value="{{ $value }}"
        class="dc-input dc-date-input"
        inputmode="numeric"
        autocomplete="off"
        placeholder="zz/ll/aaaa"
        pattern="\d{1,2}/\d{1,2}/\d{4}"
        title="Format: zz/ll/aaaa"
        @if($required) required @endif
    >
</div>
