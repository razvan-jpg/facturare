@extends(auth()->check() ? 'layouts.app' : 'layouts.legal-public')

@php
    $pages = $pages ?? config('legal', []);
    $brand = $brand ?? config('dateconta.brand_name', 'DateConta Facturare');
    $contact = $contact ?? config('dateconta.contact_email');
    $operator = $operator ?? config('dateconta.platform_operator');
    $meta = $meta ?? ['title' => 'Legal', 'subtitle' => 'Documente legale pentru utilizarea DateConta Facturare'];
    $metaTitle = $meta['title'] ?? 'Legal';
    $metaSubtitle = $meta['subtitle'] ?? '';
@endphp

@section('title', __($metaTitle))
@section('heading', __($metaTitle))
@section('subheading', $metaSubtitle !== '' ? __($metaSubtitle) : '')
@section('meta_description', $metaSubtitle !== '' ? __($metaSubtitle) : __('Documente legale pentru utilizarea DateConta Facturare.'))
@section('canonical', url()->current())

@section('content')
<div class="help-shell">
    <aside class="help-toc dc-card">
        <div class="help-toc-title">{{ __('Legal') }}</div>
        <nav class="help-toc-nav">
            <a href="{{ route('legal.index') }}" class="{{ request()->routeIs('legal.index') ? 'is-active' : '' }}">{{ __('Prezentare') }}</a>
            @foreach($pages as $key => $sec)
                <a href="{{ route('legal.show', $key) }}" class="{{ ($current ?? null) === $key ? 'is-active' : '' }}">
                    {{ __($sec['title']) }}
                </a>
            @endforeach
        </nav>
        <div class="help-toc-meta">
            <div>{{ __('Operator: :name', ['name' => $operator['name'] ?? '']) }}</div>
            <div>{{ __('CUI') }} {{ $operator['cui'] ?? '' }}</div>
            <div><a href="mailto:{{ $contact }}" class="text-teal-800 hover:underline">{{ $contact }}</a></div>
        </div>
    </aside>

    <article class="help-article dc-card">
        @yield('legal')

        @if(! empty($prev) || ! empty($next))
            <div class="help-pager">
                @if(! empty($prev))
                    <a href="{{ route('legal.show', $prev) }}" class="dc-btn-secondary">← {{ __($pages[$prev]['title']) }}</a>
                @else
                    <span></span>
                @endif
                @if(! empty($next))
                    <a href="{{ route('legal.show', $next) }}" class="dc-btn-primary">{{ __($pages[$next]['title']) }} →</a>
                @endif
            </div>
        @endif
    </article>
</div>

<style>
.help-shell {
    display: grid;
    grid-template-columns: minmax(220px, 280px) minmax(0, 1fr);
    gap: 1.25rem;
    align-items: start;
}
@media (max-width: 960px) {
    .help-shell { grid-template-columns: 1fr; }
}
.help-toc { padding: 1rem; position: sticky; top: 0.75rem; }
.help-toc-title {
    font-size: 11px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
    color: #627d98; margin-bottom: .65rem;
}
.help-toc-nav { display: flex; flex-direction: column; gap: .15rem; max-height: 65vh; overflow: auto; }
.help-toc-nav a {
    display: block; padding: .45rem .55rem; border-radius: .45rem;
    font-size: 13px; color: #243b53; text-decoration: none; line-height: 1.35;
}
.help-toc-nav a:hover { background: #f0f4f8; }
.help-toc-nav a.is-active { background: #e6fffa; color: #0f766e; font-weight: 600; }
.help-toc-meta { margin-top: 1rem; padding-top: .75rem; border-top: 1px solid #e2e8f0; font-size: 11px; color: #829ab1; line-height: 1.5; }
.help-article { padding: 1.5rem 1.75rem; }
.help-article h2 { font-family: 'Source Serif 4', Georgia, serif; font-size: 1.65rem; color: #102a43; margin: 0 0 .35rem; }
.help-article .help-lead { color: #486581; margin-bottom: 1.25rem; font-size: .95rem; }
.help-article h3 { font-size: 1.1rem; font-weight: 700; color: #243b53; margin: 1.6rem 0 .55rem; }
.help-article h4 { font-size: .95rem; font-weight: 700; color: #334e68; margin: 1.15rem 0 .4rem; }
.help-article p, .help-article li { font-size: .92rem; line-height: 1.65; color: #334e68; }
.help-article p { margin: 0 0 .75rem; }
.help-article ul, .help-article ol { margin: 0 0 1rem 1.15rem; }
.help-article li { margin-bottom: .35rem; }
.help-article .help-note {
    border-left: 3px solid #14b8a6; background: #f0fdfa; padding: .75rem 1rem;
    border-radius: 0 .5rem .5rem 0; margin: 1rem 0; font-size: .88rem; color: #115e59;
}
.help-article .help-warn {
    border-left: 3px solid #f59e0b; background: #fffbeb; padding: .75rem 1rem;
    border-radius: 0 .5rem .5rem 0; margin: 1rem 0; font-size: .88rem; color: #92400e;
}
.help-article table.legal-table {
    width: 100%; border-collapse: collapse; font-size: .88rem; margin: 0 0 1rem;
}
.help-article table.legal-table th,
.help-article table.legal-table td {
    border: 1px solid #e2e8f0; padding: .55rem .65rem; text-align: left; vertical-align: top;
}
.help-article table.legal-table th { background: #f8fafc; color: #334e68; }
.help-pager {
    display: flex; justify-content: space-between; gap: .75rem; flex-wrap: wrap;
    margin-top: 2rem; padding-top: 1.25rem; border-top: 1px solid #e2e8f0;
}
.help-meta-line { font-size: 12px; color: #829ab1; margin-bottom: 1rem; }
</style>
@endsection
