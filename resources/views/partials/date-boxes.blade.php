@php
    $name = $name ?? 'date';
    $label = $label ?? 'Data';
    $required = (bool) ($required ?? false);
    $id = $id ?? $name;
    $raw = old($name, $value ?? null);
    $iso = '';
    $d = $m = $y = '';
    if (filled($raw)) {
        try {
            if (is_string($raw) && preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $raw)) {
                [$d, $m, $y] = array_map('intval', explode('/', $raw));
                $iso = sprintf('%04d-%02d-%02d', $y, $m, $d);
            } else {
                $dt = \Illuminate\Support\Carbon::parse($raw);
                $d = (int) $dt->format('d');
                $m = (int) $dt->format('m');
                $y = (int) $dt->format('Y');
                $iso = $dt->format('Y-m-d');
            }
        } catch (\Throwable) {
            // leave empty
        }
    }
    $dPad = $d !== '' && $d !== 0 ? str_pad((string) $d, 2, '0', STR_PAD_LEFT) : '';
    $mPad = $m !== '' && $m !== 0 ? str_pad((string) $m, 2, '0', STR_PAD_LEFT) : '';
@endphp
<div class="dc-dateboxes" id="{{ $id }}" data-dateboxes="{{ $id }}">
    @if($label !== false && $label !== '')
        <label class="dc-label" for="{{ $id }}_d">{{ $label }}</label>
    @endif
    <div class="dc-dateboxes-row">
        <input type="text" inputmode="numeric" maxlength="2" class="dc-datebox" data-part="d" id="{{ $id }}_d" value="{{ $dPad }}" placeholder="zz" aria-label="Zi">
        <span class="dc-datebox-sep">/</span>
        <input type="text" inputmode="numeric" maxlength="2" class="dc-datebox" data-part="m" value="{{ $mPad }}" placeholder="ll" aria-label="Lună">
        <span class="dc-datebox-sep">/</span>
        <input type="text" inputmode="numeric" maxlength="4" class="dc-datebox dc-datebox-year" data-part="y" value="{{ $y ?: '' }}" placeholder="aaaa" aria-label="An">
        <label class="dc-datebox-cal" title="Alege din calendar">
            <input type="date" class="dc-datebox-native" value="{{ $iso }}" tabindex="-1">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        </label>
        <input type="hidden" name="{{ $name }}" class="dc-datebox-value" value="{{ $iso }}" @if($required) required @endif>
    </div>
</div>
