@php
    $variant = $variant ?? 'full'; // full|compact|icon
    $href = $href ?? route('home');
    $logo192 = asset('images/brand/dateconta-logo-192.png');
    $logo96 = asset('images/brand/dateconta-logo-96.png');
    $icon = asset('images/brand/dateconta-icon.png');
    $light = $light ?? false;
@endphp
<a href="{{ $href }}" class="{{ $class ?? 'inline-flex items-center gap-3 no-underline' }}" @if(!empty($title)) title="{{ $title }}" @endif>
    @if($variant === 'icon')
        <img src="{{ $icon }}" alt="DateConta Facturare" class="{{ $imgClass ?? 'h-10 w-10 rounded-xl object-cover shadow-sm' }}" width="40" height="40" loading="lazy" decoding="async">
    @elseif($variant === 'compact')
        <img
            src="{{ $logo96 }}"
            srcset="{{ $logo96 }} 96w, {{ $logo192 }} 192w"
            sizes="48px"
            alt="DateConta Facturare"
            class="{{ $imgClass ?? 'h-11 w-11 rounded-xl object-cover shadow-sm ring-1 ring-black/5' }}"
            width="44"
            height="44"
            decoding="async"
        >
        <span class="leading-tight">
            <span class="block font-display text-lg {{ $light ? 'text-white' : 'text-slate-900' }}">DateConta</span>
            <span class="block text-[10px] uppercase tracking-[0.18em] {{ $light ? 'text-white/70' : 'text-slate-500' }}">{{ __('Facturare') }}</span>
        </span>
    @else
        <img
            src="{{ $logo96 }}"
            srcset="{{ $logo96 }} 96w, {{ $logo192 }} 192w"
            sizes="56px"
            alt="DateConta Facturare"
            class="{{ $imgClass ?? 'h-14 w-14 rounded-2xl object-cover shadow-md ring-1 ring-black/5' }}"
            width="56"
            height="56"
            decoding="async"
        >
        <span class="leading-tight">
            <span class="block font-display text-2xl {{ $light ? 'text-white' : 'text-[var(--dc-teal)]' }}">DateConta</span>
            <span class="block text-xs uppercase tracking-[0.2em] {{ $light ? 'text-white/70' : 'text-slate-500' }}">{{ __('Facturare') }}</span>
        </span>
    @endif
</a>
