@extends('layouts.app')

@section('heading', $heading ?? __('Ajutor'))
@section('subheading', $subheading ?? __('Manual de utilizare DateConta Facturare'))

@section('content')
<div class="help-shell">
    <aside class="help-toc dc-card">
        <div class="help-toc-title">{{ __('Cuprins') }}</div>
        <nav class="help-toc-nav">
            <a href="{{ route('help.whats-new') }}" class="{{ request()->routeIs('help.whats-new') ? 'is-active' : '' }}">{{ __('Ce este nou…') }}</a>
            <a href="{{ route('help.index') }}" class="{{ request()->routeIs('help.index') ? 'is-active' : '' }}">{{ __('Prezentare generală') }}</a>
            @foreach($sections as $key => $sec)
                <a href="{{ route('help.show', $key) }}" class="{{ ($current ?? null) === $key ? 'is-active' : '' }}">
                    {{ $sec['title'] }}
                </a>
            @endforeach
        </nav>
        <div class="help-toc-meta">
            <div>{{ __('Versiune') }}: v{{ config('dateconta.version') }}</div>
            <div>{{ __('Contact') }}: {{ config('dateconta.contact_email') }}</div>
        </div>
    </aside>

    <article class="help-article dc-card">
        @yield('help')

        @if(! empty($prev) || ! empty($next))
            <div class="help-pager">
                @if(! empty($prev))
                    <a href="{{ route('help.show', $prev) }}" class="dc-btn-secondary">← {{ $sections[$prev]['title'] }}</a>
                @else
                    <span></span>
                @endif
                @if(! empty($next))
                    <a href="{{ route('help.show', $next) }}" class="dc-btn-primary">{{ $sections[$next]['title'] }} →</a>
                @endif
            </div>
        @endif
    </article>
</div>

<style>
.help-shell {
    display: grid;
    grid-template-columns: minmax(220px, 260px) minmax(0, 1fr);
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
    display: block; padding: .4rem .55rem; border-radius: .45rem;
    font-size: 13px; color: #243b53; text-decoration: none;
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
.help-steps { counter-reset: step; list-style: none; margin-left: 0; padding: 0; }
.help-steps > li {
    counter-increment: step; position: relative; padding: .65rem .75rem .65rem 2.6rem;
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: .55rem; margin-bottom: .55rem;
}
.help-steps > li::before {
    content: counter(step); position: absolute; left: .65rem; top: .65rem;
    width: 1.45rem; height: 1.45rem; border-radius: 999px; background: #0f766e; color: #fff;
    font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center;
}
.help-figure {
    margin: 1rem 0 1.35rem; border: 1px solid #d9e2ec; border-radius: .75rem; overflow: hidden;
    background: #f8fafc; box-shadow: 0 8px 24px rgba(15,42,67,.06);
}
.help-figure img { display: block; width: 100%; height: auto; }
.help-figure-link { display: block; }
.help-figure-placeholder {
    min-height: 180px; display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: .35rem; color: #829ab1; font-size: 13px; padding: 2rem; background:
        repeating-linear-gradient(-45deg, #f8fafc, #f8fafc 12px, #f0f4f8 12px, #f0f4f8 24px);
}
.help-figure figcaption {
    padding: .65rem .9rem; font-size: 12px; color: #486581; border-top: 1px solid #e2e8f0; background: #fff;
}
.help-figure figcaption strong { display: block; color: #102a43; margin-bottom: .1rem; }
.help-pager {
    display: flex; justify-content: space-between; gap: .75rem; flex-wrap: wrap;
    margin-top: 2rem; padding-top: 1.25rem; border-top: 1px solid #e2e8f0;
}
.help-kbd {
    display: inline-block; padding: .05rem .35rem; border: 1px solid #cbd5e1; border-bottom-width: 2px;
    border-radius: .3rem; font-size: 11px; background: #fff; color: #334e68; font-family: ui-monospace, monospace;
}
.help-grid-cards {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: .75rem; margin: 1rem 0 1.25rem;
}
.help-grid-cards a {
    display: block; padding: .85rem; border: 1px solid #e2e8f0; border-radius: .65rem;
    text-decoration: none; color: inherit; background: #fff;
}
.help-grid-cards a:hover { border-color: #99f6e4; background: #f0fdfa; }
.help-grid-cards strong { display: block; color: #0f766e; margin-bottom: .25rem; font-size: .9rem; }
.help-grid-cards span { font-size: .8rem; color: #627d98; line-height: 1.4; }
</style>
@endsection
