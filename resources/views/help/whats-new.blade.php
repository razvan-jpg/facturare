@extends('help._layout')

@section('heading', __('Ce este nou…'))
@section('subheading', __('Istoricul versiunilor aplicației'))

@section('help')
<h2>{{ __('Ce este nou…') }}</h2>
<p class="help-lead">
    {{ __('Istoricul versiunilor DateConta Facturare, de la 1.0.000 până la cea curentă (cele mai recente primele).') }}
    {{ __('Versiune curentă') }}: <strong>v{{ $currentVersion }}</strong>
    · {{ count($changelog) }} {{ __('versiuni') }}
</p>

@forelse($changelog as $entry)
    @php
        $isCurrent = ($entry['version'] ?? '') === $currentVersion;
        $dateLabel = ! empty($entry['date'])
            ? \Illuminate\Support\Carbon::parse($entry['date'])->locale(app()->getLocale())->translatedFormat('d M Y')
            : null;
    @endphp
    <article class="wn-entry {{ $isCurrent ? 'is-current' : '' }}">
        <header class="wn-entry-head">
            <div class="wn-version">
                <span class="wn-badge">v{{ $entry['version'] }}</span>
                @if($isCurrent)
                    <span class="wn-current">{{ __('curentă') }}</span>
                @endif
            </div>
            @if($dateLabel)
                <time datetime="{{ $entry['date'] }}" class="wn-date">{{ $dateLabel }}</time>
            @endif
        </header>
        @if(! empty($entry['title']))
            <h3 class="wn-title">{{ $entry['title'] }}</h3>
        @endif
        @if(! empty($entry['changes']) && is_array($entry['changes']))
            <ul class="wn-changes">
                @foreach($entry['changes'] as $change)
                    <li>{{ $change }}</li>
                @endforeach
            </ul>
        @endif
    </article>
@empty
    <div class="help-note">{{ __('Nu există încă înregistrări în istoric.') }}</div>
@endforelse

<style>
.wn-entry {
    border: 1px solid #e2e8f0;
    border-radius: .75rem;
    padding: 1rem 1.15rem;
    margin: 0 0 .9rem;
    background: #fff;
}
.wn-entry.is-current {
    border-color: #99f6e4;
    background: linear-gradient(180deg, #f0fdfa 0%, #fff 55%);
    box-shadow: 0 6px 18px rgba(15, 118, 110, .06);
}
.wn-entry-head {
    display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between;
    gap: .5rem .75rem; margin-bottom: .45rem;
}
.wn-version { display: flex; align-items: center; gap: .45rem; }
.wn-badge {
    display: inline-flex; align-items: center;
    font-size: 12px; font-weight: 700; letter-spacing: .02em;
    padding: .2rem .55rem; border-radius: .4rem;
    background: #102a43; color: #f0f4f8;
}
.wn-entry.is-current .wn-badge { background: #0f766e; }
.wn-current {
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
    color: #0f766e;
}
.wn-date { font-size: 12px; color: #627d98; }
.wn-title { font-size: 1.05rem; margin: .15rem 0 .55rem; color: #243b53; }
.wn-changes { margin: 0 0 0 1.1rem; }
.wn-changes li { margin-bottom: .3rem; }
</style>
@endsection
