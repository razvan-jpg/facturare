@php
    $guides = $guides ?? config('public_guides.guides', []);
    $current = $current ?? null;
    $isFaq = ($current ?? null) === null && request()->routeIs('faq');
@endphp
<aside class="help-toc dc-card">
    <div class="help-toc-title">{{ __('Resurse publice') }}</div>
    <nav class="help-toc-nav">
        <a href="{{ route('faq') }}" class="{{ $isFaq ? 'is-active' : '' }}">{{ __('Întrebări frecvente') }}</a>
        @foreach($guides as $key => $sec)
            <a href="{{ route('guides.show', $key) }}" class="{{ ($current ?? null) === $key ? 'is-active' : '' }}">
                {{ __($sec['nav'] ?? $sec['title']) }}
            </a>
        @endforeach
    </nav>
    <div class="help-toc-meta">
        <div class="mb-2">{{ __('Gratuit până la 31.03.2027') }}</div>
        <a href="{{ route('register') }}" class="dc-btn-primary text-xs px-3 py-1.5 inline-flex">{{ __('Creează cont') }}</a>
        <div class="mt-3"><a href="{{ route('pricing') }}" class="text-teal-800 hover:underline">{{ __('Prețuri') }}</a></div>
    </div>
</aside>
