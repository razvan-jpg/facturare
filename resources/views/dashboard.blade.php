@extends('layouts.app')

@section('heading', __('Dashboard'))
@section('subheading')
    {{ $company->name }} · {{ $accessLabel }}
@endsection

@section('actions')
    <div class="dc-new-doc" x-data="{ open: false }" @keydown.escape.window="open = false">
        <button type="button"
                class="dc-btn-primary dc-new-doc-btn"
                @click="open = !open"
                :aria-expanded="open.toString()"
                aria-haspopup="menu">
            {{ __('Document nou') }}
            <svg class="dc-new-doc-caret" width="12" height="12" viewBox="0 0 12 12" aria-hidden="true">
                <path d="M2.5 4.5 6 8l3.5-3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <div class="dc-new-doc-menu" x-cloak x-show="open" x-transition.opacity.duration.120ms
             @click.outside="open = false" role="menu">
            <a role="menuitem" href="{{ route('documents.create', ['type' => 'invoice']) }}" class="dc-new-doc-item" @click="open = false">{{ __('Factură nouă') }}</a>
            <a role="menuitem" href="{{ route('documents.create', ['type' => 'proforma']) }}" class="dc-new-doc-item" @click="open = false">{{ __('Proformă nouă') }}</a>
            <a role="menuitem" href="{{ route('recurring.create') }}" class="dc-new-doc-item" @click="open = false">{{ __('Abonament nou') }}</a>
            <a role="menuitem" href="{{ route('payments.create') }}" class="dc-new-doc-item" @click="open = false">{{ __('Încasare nouă') }}</a>
            <a role="menuitem" href="{{ route('documents.create', ['type' => 'delivery']) }}" class="dc-new-doc-item" @click="open = false">{{ __('Aviz nou') }}</a>
            <a role="menuitem" href="{{ route('documents.corrections.create', ['kind' => 'storno']) }}" class="dc-new-doc-item" @click="open = false">{{ __('Factură storno') }}</a>
            <a role="menuitem" href="{{ route('documents.corrections.create', ['kind' => 'credit_note']) }}" class="dc-new-doc-item" @click="open = false">{{ __('Notă de creditare') }}</a>
        </div>
    </div>
    @if($draftCount > 0)
        <a href="{{ route('documents.index') }}" class="dc-btn-secondary text-sm">{{ __('Draft-uri') }} ({{ $draftCount }})</a>
    @endif
@endsection

@php
    $fmt = fn ($n, $dec = 2) => number_format((float) $n, $dec, ',', '.');
    $fmtInt = fn ($n) => number_format((float) $n, 0, ',', '.');
@endphp

@section('content')
<style>
    .dc-new-doc { position: relative; display: inline-flex; }
    .dc-new-doc-btn { display: inline-flex; align-items: center; gap: .45rem; }
    .dc-new-doc-caret { opacity: .9; transition: transform .15s ease; }
    .dc-new-doc-btn[aria-expanded="true"] .dc-new-doc-caret { transform: rotate(180deg); }
    .dc-new-doc-menu {
        position: absolute; top: calc(100% + .35rem); right: 0; z-index: 40;
        min-width: 12.5rem; padding: .35rem;
        background: #fff; border: 1px solid #d9e2ec; border-radius: .65rem;
        box-shadow: 0 10px 28px rgba(15, 42, 67, .12);
    }
    .dc-new-doc-item {
        display: block; padding: .55rem .75rem; border-radius: .45rem;
        font-size: .9rem; font-weight: 600; color: #243b53; text-decoration: none;
        white-space: nowrap;
    }
    .dc-new-doc-item:hover { background: #f0fdfa; color: #0f766e; }

    .dc-dash { display: flex; flex-direction: column; gap: 1rem; min-width: 0; }
    .dc-dash-grid {
        display: grid; gap: 1rem; min-width: 0;
        grid-template-columns: minmax(0, 1fr);
    }
    @media (min-width: 900px) {
        .dc-dash-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (min-width: 1280px) {
        .dc-dash-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .dc-dash-widget--wide { grid-column: span 2; }
    }
    .dc-dash-widget {
        position: relative;
        background: #fff; border: 1px solid #e2e8f0; border-radius: .75rem;
        box-shadow: 0 1px 3px rgba(15, 42, 67, .06);
        display: flex; flex-direction: column; min-width: 0; min-height: 0; overflow: visible;
    }
    .dc-dash-widget.is-dragging { opacity: .55; box-shadow: 0 10px 28px rgba(15, 42, 67, .18); }
    .dc-dash-widget.is-drag-over { outline: 2px dashed #0f766e; outline-offset: 2px; }
    .dc-dash-widget__chrome {
        padding: .55rem .65rem .55rem .45rem;
        border-bottom: 1px solid #eef2f6;
        display: flex; align-items: center; justify-content: space-between; gap: .4rem;
        min-width: 0; cursor: grab; user-select: none; background: #fff;
        border-radius: .75rem .75rem 0 0;
    }
    .dc-dash-widget__chrome:active { cursor: grabbing; }
    .dc-dash-widget__head-left { display: flex; align-items: center; gap: .35rem; min-width: 0; flex: 1; }
    .dc-dash-widget__head-right { display: flex; align-items: center; gap: .1rem; flex-shrink: 0; }
    .dc-dash-widget__drag {
        appearance: none; border: 0; background: transparent; color: #94a3b8;
        width: 1.5rem; height: 1.5rem; display: inline-flex; align-items: center; justify-content: center;
        cursor: grab; border-radius: .3rem; flex-shrink: 0; padding: 0;
    }
    .dc-dash-widget__drag:hover { color: #64748b; background: #f1f5f9; }
    .dc-dash-widget__title { font-size: .72rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #475569; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dc-dash-widget__icon-btn {
        appearance: none; border: 0; background: transparent; color: #94a3b8;
        width: 1.7rem; height: 1.7rem; display: inline-flex; align-items: center; justify-content: center;
        border-radius: .35rem; cursor: pointer; padding: 0;
    }
    .dc-dash-widget__icon-btn:hover { color: #475569; background: #f1f5f9; }
    .dc-dash-widget__menu-wrap { position: relative; }
    .dc-dash-widget__menu {
        position: absolute; top: calc(100% + .25rem); right: 0; z-index: 30;
        min-width: 9.5rem; padding: .3rem;
        background: #fff; border: 1px solid #e2e8f0; border-radius: .5rem;
        box-shadow: 0 10px 28px rgba(15, 42, 67, .14);
    }
    .dc-dash-widget__menu-item {
        display: block; width: 100%; text-align: left; padding: .5rem .7rem; border: 0; border-radius: .35rem;
        background: transparent; color: #475569; font-size: .82rem; font-weight: 500;
        text-decoration: none; cursor: pointer;
    }
    .dc-dash-widget__menu-item:hover { background: #f8fafc; color: #0f172a; }
    .dc-dash-widget__menu-item--danger { color: #b91c1c; }
    .dc-dash-widget__menu-item--danger:hover { background: #fef2f2; color: #991b1b; }
    .dc-dash-widget.is-panel { overflow: hidden; }
    .dc-dash-widget__panel-head {
        display: flex; align-items: flex-start; justify-content: space-between; gap: .75rem;
        padding: .75rem .9rem; background: #334155; color: #fff;
        border-radius: .75rem .75rem 0 0;
    }
    .dc-dash-widget__panel-titles { min-width: 0; }
    .dc-dash-widget__title--light { color: #fff; }
    .dc-dash-widget__panel-sub { margin-top: .15rem; font-size: .75rem; font-weight: 500; color: rgba(255,255,255,.82); }
    .dc-dash-widget__panel-done {
        appearance: none; border: 0; background: transparent; color: #fff;
        width: 1.75rem; height: 1.75rem; font-size: 1.1rem; font-weight: 700;
        border-radius: .35rem; cursor: pointer; line-height: 1; flex-shrink: 0;
    }
    .dc-dash-widget__panel-done:hover { background: rgba(255,255,255,.12); }
    .dc-dash-widget__panel-body {
        padding: .9rem 1rem 1rem; background: #f8fafc; flex: 1; min-height: 10rem;
        max-height: 18rem; overflow-y: auto;
    }
    .dc-dash-cfg { display: grid; gap: .75rem; font-size: .82rem; color: #334155; }
    .dc-dash-cfg__group { display: grid; gap: .35rem; }
    .dc-dash-cfg__group--row { grid-template-columns: auto 1fr; align-items: center; gap: .75rem; }
    .dc-dash-cfg__label { font-weight: 700; color: #0f172a; }
    .dc-dash-cfg__radio, .dc-dash-cfg__check {
        display: flex; align-items: flex-start; gap: .45rem; cursor: pointer; line-height: 1.35;
    }
    .dc-dash-cfg__radio input, .dc-dash-cfg__check input { margin-top: .15rem; }
    .dc-dash-cfg__date, .dc-dash-cfg__select {
        width: 100%; max-width: 14rem; border: 1px solid #cbd5e1; border-radius: .45rem;
        padding: .4rem .55rem; background: #fff; font-size: .82rem;
    }
    .dc-dash-cfg__hint { margin: 0; color: #64748b; font-size: .75rem; line-height: 1.4; }
    .dc-dash-details { font-size: .8rem; color: #334155; line-height: 1.45; }
    .dc-dash-details h4 { margin: 0 0 .35rem; font-size: .78rem; font-weight: 700; color: #0f172a; }
    .dc-dash-details ul { margin: 0 0 .85rem; padding-left: 1.1rem; }
    .dc-dash-details li { margin-bottom: .25rem; }
    .dc-dash-widget__menu-item { width: 100%; }
    .dc-dash-widget__sub { font-size: .72rem; color: #64748b; flex-shrink: 0; }
    .dc-dash-widget__body { padding: .85rem 1rem 1rem; flex: 1; min-width: 0; min-height: 0; overflow: hidden; }
    .dc-dash-big { font-size: 1.65rem; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; line-height: 1.2; }
    .dc-dash-big span { font-size: .95rem; font-weight: 600; color: #64748b; margin-left: .2rem; }
    .dc-dash-bar { margin-top: .75rem; }
    .dc-dash-bar__labels { display: flex; justify-content: space-between; font-size: .72rem; color: #64748b; margin-bottom: .25rem; }
    .dc-dash-bar__track { display: flex; height: .55rem; border-radius: 999px; overflow: hidden; background: #e2e8f0; }
    .dc-dash-bar__seg { height: 100%; }
    .dc-dash-bar__seg--over { background: #60a5fa; }
    .dc-dash-bar__seg--ok { background: #14b8a6; }
    .dc-dash-bar__vals { display: flex; justify-content: space-between; font-size: .78rem; font-weight: 600; color: #334155; margin-top: .25rem; font-variant-numeric: tabular-nums; }
    .dc-dash-kv { display: grid; gap: .35rem; font-size: .78rem; }
    .dc-dash-kv div { display: flex; justify-content: space-between; gap: .75rem; color: #64748b; }
    .dc-dash-kv strong { font-weight: 600; color: #334155; font-variant-numeric: tabular-nums; }
    .dc-dash-rank { display: grid; gap: .65rem; min-width: 0; max-width: 100%; }
    .dc-dash-rank__row { display: grid; gap: .2rem; min-width: 0; max-width: 100%; }
    .dc-dash-rank__meta { display: flex; justify-content: space-between; align-items: baseline; gap: .5rem; font-size: .78rem; min-width: 0; max-width: 100%; }
    .dc-dash-rank__name { color: #334155; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0; flex: 1 1 auto; }
    .dc-dash-rank__val { font-weight: 600; color: #0f172a; font-variant-numeric: tabular-nums; flex: 0 1 auto; max-width: 45%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; text-align: right; }
    .dc-dash-rank__bar { height: .35rem; border-radius: 999px; background: #e2e8f0; overflow: hidden; max-width: 100%; }
    .dc-dash-rank__fill { height: 100%; max-width: 100%; border-radius: 999px; background: linear-gradient(90deg, #38bdf8, #0ea5e9); }
    .dc-dash-rank__fill--green { background: linear-gradient(90deg, #34d399, #10b981); }
    .dc-dash-mini-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .65rem; }
    .dc-dash-mini { padding: .75rem; border: 1px solid #e2e8f0; border-radius: .65rem; background: #fafbfc; }
    .dc-dash-mini__label { font-size: .68rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; font-weight: 600; }
    .dc-dash-mini__val { margin-top: .35rem; font-size: 1.05rem; font-weight: 700; color: #0f172a; font-variant-numeric: tabular-nums; }
    .dc-dash-mini__val span { font-size: .75rem; font-weight: 600; color: #64748b; }
    .dc-dash-cash { text-align: center; padding: .5rem 0; }
    .dc-dash-cash .dc-dash-big { font-size: 2rem; }
    .dc-dash-cash__link { display: inline-block; margin-top: .5rem; font-size: .78rem; color: #0f766e; font-weight: 600; text-decoration: none; }
    .dc-dash-cash__link:hover { text-decoration: underline; }
    .dc-dash-activity { display: grid; gap: .55rem; max-height: 14rem; overflow-y: auto; }
    .dc-dash-activity__item { display: grid; grid-template-columns: auto 1fr; gap: .55rem; font-size: .78rem; align-items: start; }
    .dc-dash-activity__icon { width: 1.25rem; height: 1.25rem; border-radius: 999px; background: #ecfdf5; color: #0f766e; display: flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: .1rem; font-size: .65rem; font-weight: 700; }
    .dc-dash-activity__icon--auto { background: #eff6ff; color: #2563eb; }
    .dc-dash-activity__label { font-weight: 600; color: #334155; }
    .dc-dash-activity__detail { color: #64748b; margin-top: .1rem; word-break: break-word; }
    .dc-dash-activity__meta { color: #94a3b8; font-size: .7rem; margin-top: .15rem; }
    .dc-dash-statline { display: flex; flex-wrap: wrap; gap: .75rem 1.25rem; font-size: .78rem; color: #64748b; margin-top: .5rem; }
    .dc-dash-statline strong { color: #334155; font-variant-numeric: tabular-nums; }
    .dc-dash-chart-wrap { height: 5.5rem; position: relative; margin-top: .65rem; }
    .dc-dash-chart-wrap--lg { height: 9rem; }
    .dc-dash-unpaid { max-height: 18rem; overflow-y: auto; }
    .dc-dash-unpaid table { width: 100%; font-size: .78rem; }
    .dc-dash-unpaid th { text-align: left; color: #64748b; font-weight: 600; padding: .35rem 0; border-bottom: 1px solid #f1f5f9; }
    .dc-dash-unpaid td { padding: .55rem 0; border-bottom: 1px solid #f8fafc; vertical-align: top; }
    .dc-dash-unpaid .text-right { text-align: right; }
    .dc-dash-unpaid a { color: #0f766e; font-weight: 600; text-decoration: none; }
    .dc-dash-unpaid a:hover { text-decoration: underline; }
    .dc-dash-badge { display: inline-block; padding: .15rem .45rem; border-radius: .35rem; font-size: .68rem; font-weight: 700; background: #ffedd5; color: #c2410c; white-space: nowrap; }
    .dc-dash-empty { color: #94a3b8; font-size: .82rem; padding: .5rem 0; }

    .dc-dash-add-tile {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: .55rem; min-height: 11rem; padding: 1.25rem;
        background: #fff; border: 1px dashed #cbd5e1; border-radius: .75rem;
        box-shadow: 0 1px 3px rgba(15, 42, 67, .04);
        color: #0f172a; cursor: pointer; text-align: center;
        transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
    }
    .dc-dash-add-tile:hover { border-color: #0f766e; background: #f0fdfa; box-shadow: 0 6px 18px rgba(15, 118, 110, .08); }
    .dc-dash-add-tile__plus { font-size: 1.75rem; font-weight: 700; line-height: 1; color: #0f172a; }
    .dc-dash-add-tile__label { font-size: .92rem; font-weight: 600; }

    .dc-dash-modal-root { position: fixed; inset: 0; z-index: 80; display: flex; align-items: center; justify-content: center; padding: 1rem; }
    .dc-dash-modal-backdrop { position: absolute; inset: 0; background: rgba(15, 23, 42, .45); }
    .dc-dash-modal {
        position: relative; z-index: 1; width: min(960px, 100%); max-height: min(90vh, 720px);
        display: flex; flex-direction: column; background: #fff; border-radius: .85rem;
        box-shadow: 0 24px 60px rgba(15, 23, 42, .25); overflow: hidden;
    }
    .dc-dash-modal__head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.15rem; border-bottom: 1px solid #e2e8f0; }
    .dc-dash-modal__head h2 { margin: 0; font-size: 1.05rem; font-weight: 700; color: #0f172a; }
    .dc-dash-modal__close { width: 2rem; height: 2rem; border: 0; background: transparent; font-size: 1.4rem; color: #64748b; cursor: pointer; border-radius: .4rem; }
    .dc-dash-modal__close:hover { background: #f1f5f9; color: #0f172a; }
    .dc-dash-modal__tabs { display: flex; flex-wrap: wrap; gap: .15rem 1rem; padding: .65rem 1.15rem 0; border-bottom: 1px solid #e2e8f0; }
    .dc-dash-modal__tab { appearance: none; border: 0; background: transparent; padding: .55rem 0 .7rem; font-size: .82rem; font-weight: 600; color: #64748b; cursor: pointer; border-bottom: 2px solid transparent; }
    .dc-dash-modal__tab.is-active { color: #2563eb; border-bottom-color: #2563eb; }
    .dc-dash-modal__tab-count { font-weight: 500; opacity: .8; margin-left: .15rem; }
    .dc-dash-modal__body { display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(0, .85fr); min-height: 0; flex: 1; overflow: hidden; }
    @media (max-width: 800px) {
        .dc-dash-modal__body { grid-template-columns: 1fr; }
        .dc-dash-modal__preview { display: none; }
    }
    .dc-dash-modal__list { overflow-y: auto; border-right: 1px solid #f1f5f9; padding: .5rem; }
    .dc-dash-modal__item {
        display: grid; grid-template-columns: 3.25rem 1fr; gap: .75rem; align-items: start;
        width: 100%; text-align: left; padding: .7rem .65rem; border: 0; border-radius: .55rem;
        background: transparent; cursor: pointer;
    }
    .dc-dash-modal__item:hover { background: #f8fafc; }
    .dc-dash-modal__item.is-selected { background: #eff6ff; }
    .dc-dash-modal__thumb {
        width: 3.25rem; height: 2.4rem; border-radius: .35rem; border: 1px solid #e2e8f0; background: #f8fafc;
        background-image: linear-gradient(90deg, #cbd5e1 20%, transparent 20%), linear-gradient(#e2e8f0, #e2e8f0);
        background-size: 100% 2px, 70% 40%; background-position: center 70%, 15% 25%; background-repeat: no-repeat;
    }
    .dc-dash-modal__thumb[data-thumb="chart"] {
        background-image: linear-gradient(135deg, transparent 40%, #93c5fd 40%, #93c5fd 55%, transparent 55%), linear-gradient(90deg, #bfdbfe, #60a5fa);
        background-size: 100% 100%, 100% 3px; background-position: center, center 75%;
    }
    .dc-dash-modal__thumb[data-thumb="bars"] {
        background-image: linear-gradient(#60a5fa, #60a5fa), linear-gradient(#14b8a6, #14b8a6);
        background-size: 45% 8px, 45% 8px; background-position: 12% 55%, 55% 55%; background-repeat: no-repeat;
    }
    .dc-dash-modal__item-title { font-size: .78rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #0f172a; }
    .dc-dash-modal__item-desc { margin-top: .2rem; font-size: .75rem; color: #64748b; line-height: 1.35; }
    .dc-dash-modal__item-status { margin-top: .35rem; font-size: .72rem; font-weight: 600; color: #0f766e; }
    .dc-dash-modal__preview { padding: 1rem 1.15rem; overflow-y: auto; background: #f8fafc; }
    .dc-dash-modal__preview-label { text-align: center; font-size: .78rem; font-weight: 600; color: #64748b; margin-bottom: .75rem; }
    .dc-dash-modal__preview-card { background: #fff; border: 1px solid #e2e8f0; border-radius: .65rem; padding: 1rem; box-shadow: 0 4px 14px rgba(15, 42, 67, .06); }
    .dc-dash-modal__preview-title { font-size: .78rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: #0f172a; }
    .dc-dash-modal__preview-desc { margin: .55rem 0 .85rem; font-size: .8rem; color: #64748b; line-height: 1.4; }
    .dc-dash-modal__preview-fake { height: 5.5rem; border-radius: .45rem; background: linear-gradient(180deg, #eff6ff, #fff); border: 1px dashed #bfdbfe; }
    .dc-dash-modal__add-form { margin-top: .9rem; }
    .dc-dash-modal__add {
        width: 100%; appearance: none; border: 0; border-radius: .55rem; padding: .7rem 1rem;
        background: #1e293b; color: #fff; font-weight: 700; font-size: .9rem; cursor: pointer;
    }
    .dc-dash-modal__add:hover:not(:disabled) { background: #0f172a; }
    .dc-dash-modal__add:disabled { background: #94a3b8; cursor: not-allowed; }
    .dc-dash-modal__foot { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .75rem 1.15rem; border-top: 1px solid #e2e8f0; background: #fff; }
    .dc-dash-modal__reset { appearance: none; border: 0; background: transparent; color: #64748b; font-size: .78rem; font-weight: 600; cursor: pointer; text-decoration: underline; }
    .dc-dash-modal__slots { font-size: .78rem; color: #64748b; }
    .dc-dash-modal__slots strong { color: #0f172a; }
</style>

<div class="dc-dash" x-data="{ open: false }">
    <div class="dc-dash-grid" id="dc-dash-grid">
        @foreach($widgetKeys as $widgetKey)
            @include('dashboard.partials.widget-shell', ['widgetKey' => $widgetKey])
        @endforeach

        @unless($dashboardFull)
            <button type="button" class="dc-dash-add-tile" id="dc-dash-add-tile" @click="open = true">
                <span class="dc-dash-add-tile__plus" aria-hidden="true">+</span>
                <span class="dc-dash-add-tile__label">{{ __('Adaugă element nou') }}</span>
            </button>
        @else
            <button type="button" class="dc-dash-add-tile" id="dc-dash-add-tile" @click="open = true" title="{{ __('Dashboard plin — poți înlocui widget-uri din catalog') }}">
                <span class="dc-dash-add-tile__plus" aria-hidden="true">+</span>
                <span class="dc-dash-add-tile__label">{{ __('Adaugă element nou') }}</span>
                <span class="text-xs text-slate-500">{{ $dashboardSlotsUsed }} / {{ $dashboardSlotsMax }}</span>
            </button>
        @endunless
    </div>

    @include('dashboard.partials.add-modal')
</div>

@push('scripts')
<script src="{{ asset('js/chart.umd.min.js') }}?v=4.4.1"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart !== 'undefined') {
        var sales = @json($dailySales);
        var payments = @json($dailyPayments);
        var blue = '#0ea5e9';
        var teal = '#0f766e';

        function lineChart(id, data, color, fill) {
            var el = document.getElementById(id);
            if (!el || !data.labels || !data.labels.length) return;
            new Chart(el, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.values,
                        borderColor: color,
                        backgroundColor: fill,
                        fill: true,
                        tension: 0.3,
                        pointRadius: 0,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
                    scales: {
                        x: { display: data.labels.length <= 15, ticks: { maxTicksLimit: 6, font: { size: 10 } }, grid: { display: false } },
                        y: { display: true, beginAtZero: true, ticks: { maxTicksLimit: 4, font: { size: 10 } }, grid: { color: '#f1f5f9' } }
                    }
                }
            });
        }

        lineChart('dc-chart-sales', sales, blue, 'rgba(14,165,233,0.12)');
        lineChart('dc-chart-payments', payments, teal, 'rgba(15,118,110,0.12)');
        lineChart('dc-chart-payments-lg', payments, blue, 'rgba(14,165,233,0.08)');
    }

    // Drag & drop reorder pe bara de sus a tile-urilor
    (function () {
        var grid = document.getElementById('dc-dash-grid');
        if (!grid) return;
        var reorderUrl = @json(route('dashboard.widgets.reorder'));
        var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        var dragEl = null;

        function widgetsOrder() {
            return Array.from(grid.querySelectorAll('.dc-dash-widget[data-widget]'))
                .map(function (el) { return el.getAttribute('data-widget'); });
        }

        function saveOrder() {
            fetch(reorderUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ widgets: widgetsOrder() })
            }).catch(function () {});
        }

        grid.addEventListener('mousedown', function (e) {
            var handle = e.target.closest('[data-drag-handle]');
            if (!handle) return;
            if (e.target.closest('.dc-dash-widget__head-right')) return;
            var widget = handle.closest('.dc-dash-widget');
            if (!widget) return;
            widget.setAttribute('draggable', 'true');
        });

        grid.addEventListener('dragstart', function (e) {
            var widget = e.target.closest('.dc-dash-widget');
            if (!widget || widget.getAttribute('draggable') !== 'true') {
                e.preventDefault();
                return;
            }
            dragEl = widget;
            widget.classList.add('is-dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', widget.getAttribute('data-widget') || '');
        });

        grid.addEventListener('dragend', function () {
            if (dragEl) {
                dragEl.classList.remove('is-dragging');
                dragEl.setAttribute('draggable', 'false');
            }
            grid.querySelectorAll('.is-drag-over').forEach(function (el) {
                el.classList.remove('is-drag-over');
            });
            dragEl = null;
            saveOrder();
        });

        grid.addEventListener('dragover', function (e) {
            if (!dragEl) return;
            e.preventDefault();
            var over = e.target.closest('.dc-dash-widget');
            if (!over || over === dragEl) return;
            over.classList.add('is-drag-over');
            var rect = over.getBoundingClientRect();
            var before = (e.clientY - rect.top) < rect.height / 2
                || (e.clientX - rect.left) < rect.width / 2;
            if (before) {
                grid.insertBefore(dragEl, over);
            } else {
                grid.insertBefore(dragEl, over.nextSibling);
            }
            // ține tile-ul „Adaugă” la final
            var add = document.getElementById('dc-dash-add-tile');
            if (add) grid.appendChild(add);
        });

        grid.addEventListener('dragleave', function (e) {
            var over = e.target.closest('.dc-dash-widget');
            if (over) over.classList.remove('is-drag-over');
        });

        grid.addEventListener('drop', function (e) {
            e.preventDefault();
            grid.querySelectorAll('.is-drag-over').forEach(function (el) {
                el.classList.remove('is-drag-over');
            });
        });
    })();
});
</script>
@endpush
@endsection
