@extends('layouts.app')
@php
    $canManage = app(\App\Services\CompanyPermission::class)->can(auth()->user(), $company, 'clients_manage');
    $canCollect = app(\App\Services\CompanyPermission::class)->can(auth()->user(), $company, 'payments_manage');
@endphp
@section('heading', 'Clienți')
@section('actions')
@php
    $zeroToggleParams = array_filter([
        'hide_zero' => $hideZero ? null : 1,
        'per_page' => request()->query('per_page'),
    ], fn ($v) => $v !== null && $v !== '');
@endphp
<a href="{{ route('clients.index', $zeroToggleParams) }}"
   class="dc-btn-secondary"
   title="{{ $hideZero ? __('Afișează și clienții cu sold 0') : __('Ascunde clienții cu sold 0') }}">
    {{ $hideZero ? __('Afișează sold 0') : __('Ascunde sold 0') }}
</a>
@if($canManage)
<a href="{{ route('clients.opening-balances.edit') }}" class="dc-btn-secondary">Solduri inițiale</a>
@if(($anafSyncableCount ?? 0) > 0)
<button type="button" id="clients-anaf-sync" class="dc-btn-secondary"
    data-url="{{ route('clients.anaf-sync') }}"
    data-count="{{ $anafSyncableCount }}">
    Actualizare ANAF ({{ $anafSyncableCount }})
</button>
@endif
<a href="{{ route('clients.create') }}" class="dc-btn-primary">Client nou</a>
@endif
@endsection
@section('content')
<div id="clients-anaf-sync-status" class="hidden mb-4 rounded-lg border px-4 py-3 text-sm"></div>

<div id="clients-anaf-sync-popup" class="hidden fixed inset-0 z-[80] flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="anaf-sync-popup-title">
    <div class="absolute inset-0 bg-slate-900/45" data-anaf-sync-dismiss></div>
    <div class="relative w-full max-w-md rounded-2xl bg-white shadow-2xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 bg-gradient-to-r from-teal-50 to-slate-50">
            <h2 id="anaf-sync-popup-title" class="font-display text-xl text-slate-900">Actualizare ANAF finalizată</h2>
            <p class="text-sm text-slate-600 mt-1">Statistica pentru societatea curentă. Fereastra se închide automat în <span id="anaf-sync-popup-countdown">30</span>s.</p>
        </div>
        <div class="p-5 space-y-3 text-sm">
            <div class="flex items-center justify-between gap-3 rounded-lg border border-teal-100 bg-teal-50/70 px-3 py-2.5">
                <span class="text-slate-700">Actualizați cu succes</span>
                <strong id="anaf-sync-stat-success" class="text-teal-900 text-lg tabular-nums">0</strong>
            </div>
            <div class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5">
                <span class="text-slate-700">Fișe modificate</span>
                <strong id="anaf-sync-stat-modified" class="text-slate-900 text-lg tabular-nums">0</strong>
            </div>
            <div class="flex items-center justify-between gap-3 rounded-lg border border-amber-100 bg-amber-50/70 px-3 py-2.5">
                <span class="text-slate-700">Ignorați (diverse motive)</span>
                <strong id="anaf-sync-stat-ignored" class="text-amber-950 text-lg tabular-nums">0</strong>
            </div>
            <ul id="anaf-sync-stat-reasons" class="text-xs text-slate-600 space-y-1 pl-1"></ul>
        </div>
        <div class="px-5 py-3 border-t border-slate-100 flex justify-end">
            <button type="button" class="dc-btn-primary" data-anaf-sync-dismiss>{{ __('Închide') }}</button>
        </div>
    </div>
</div>

@include('partials.pagination', ['paginator' => $clients, 'perPage' => $perPage ?? null, 'class' => 'mb-4'])
<div class="dc-card overflow-hidden">
<table class="w-full dc-table">
<thead><tr>
    <th>{{ __('Nume') }}</th>
    <th>{{ __('CUI / CNP') }}</th>
    <th>Email</th>
    <th>{{ __('Oraș') }}</th>
    <th class="text-right">Sold</th>
    <th class="text-right" title="Penalități calculate până azi, încă nefacturate (indiferent de toggle ON/OFF)">Penalități</th>
    <th></th>
</tr></thead>
<tbody>
@forelse($clients as $client)
@php
    $sold = (float) ($clientBalances[$client->id] ?? 0);
    $pen = (float) (($penaltyUnbilled ?? [])[$client->id] ?? 0);
@endphp
<tr>
    <td class="font-medium">{{ $client->name }}</td>
    <td>{{ $client->cui ?: $client->cnp }}</td>
    <td>{{ $client->email }}</td>
    <td>{{ $client->city }}</td>
    <td class="text-right tabular-nums {{ $sold > 0.009 ? 'text-amber-900 font-medium' : 'text-slate-500' }}">
        {{ number_format($sold, 2, ',', '.') }}
    </td>
    <td class="text-right tabular-nums {{ $pen > 0.009 ? 'text-rose-800 font-medium' : 'text-slate-400' }}">
        @if($pen > 0.009)
            {{ number_format($pen, 2, ',', '.') }}
        @else
            —
        @endif
    </td>
    <td class="text-right whitespace-nowrap">
        <div class="dc-act-wrap">
            <a href="{{ route('clients.show', $client) }}" class="dc-act">{{ __('Fișă') }}</a>
                        @if($canCollect && $sold > 0.009)
                            <a href="{{ route('payments.create', ['client_id' => $client->id, 'select_all' => 1]) }}"
                               class="dc-act">{{ __('Încasează') }}</a>
                        @endif
            @if($canManage)
            <a href="{{ route('clients.edit', $client) }}" class="dc-act">{{ __('Editează') }}</a>
            <form class="inline" method="POST" action="{{ route('clients.destroy', $client) }}" onsubmit="return confirm('Ștergi clientul?')">@csrf @method('DELETE')
                <button type="submit" class="dc-act dc-act-danger">{{ __('Șterge') }}</button>
            </form>
            @endif
        </div>
    </td>
</tr>
@empty
    <tr><td colspan="7" class="text-slate-500">Niciun client încă.</td></tr>
@endforelse
</tbody>
</table>
</div>
@include('partials.pagination', ['paginator' => $clients, 'perPage' => $perPage ?? null, 'class' => 'mt-4'])
@if($canManage)
<script>
(() => {
    const STORAGE_KEY = 'dc_anaf_sync_stats';
    const popup = document.getElementById('clients-anaf-sync-popup');
    const countdownEl = document.getElementById('anaf-sync-popup-countdown');
    let closeTimer = null;
    let tickTimer = null;

    function hidePopup() {
        if (!popup) return;
        popup.classList.add('hidden');
        if (closeTimer) clearTimeout(closeTimer);
        if (tickTimer) clearInterval(tickTimer);
        closeTimer = null;
        tickTimer = null;
    }

    function showPopup(stats) {
        if (!popup) return;
        const success = Number(stats.success ?? stats.updated ?? 0);
        const modified = Number(stats.modified ?? 0);
        const ignored = Number(stats.ignored ?? stats.skipped ?? 0);
        const reasons = stats.ignored_reasons || {};

        document.getElementById('anaf-sync-stat-success').textContent = String(success);
        document.getElementById('anaf-sync-stat-modified').textContent = String(modified);
        document.getElementById('anaf-sync-stat-ignored').textContent = String(ignored);

        const reasonsEl = document.getElementById('anaf-sync-stat-reasons');
        const parts = [];
        if (reasons.person > 0) parts.push(`Persoane fizice (CNP): ${reasons.person}`);
        if (reasons.no_cui > 0) parts.push(`Fără CUI valid: ${reasons.no_cui}`);
        if (reasons.not_found > 0) parts.push(`Negăsite în ANAF: ${reasons.not_found}`);
        reasonsEl.innerHTML = parts.map((p) => `<li>${p}</li>`).join('');

        let left = 30;
        if (countdownEl) countdownEl.textContent = String(left);
        popup.classList.remove('hidden');

        if (tickTimer) clearInterval(tickTimer);
        tickTimer = setInterval(() => {
            left -= 1;
            if (countdownEl) countdownEl.textContent = String(Math.max(0, left));
            if (left <= 0 && tickTimer) {
                clearInterval(tickTimer);
                tickTimer = null;
            }
        }, 1000);

        if (closeTimer) clearTimeout(closeTimer);
        closeTimer = setTimeout(hidePopup, 30000);
    }

    popup?.querySelectorAll('[data-anaf-sync-dismiss]').forEach((el) => {
        el.addEventListener('click', hidePopup);
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && popup && !popup.classList.contains('hidden')) hidePopup();
    });

    try {
        const raw = sessionStorage.getItem(STORAGE_KEY);
        if (raw) {
            sessionStorage.removeItem(STORAGE_KEY);
            showPopup(JSON.parse(raw));
        }
    } catch (_) {}

    const btn = document.getElementById('clients-anaf-sync');
    const status = document.getElementById('clients-anaf-sync-status');
    if (!btn) return;

    btn.addEventListener('click', async () => {
        const count = btn.dataset.count || '?';
        if (!confirm(`Actualizezi din ANAF cele ${count} firme (cu CUI) din societatea curentă?\n\nPersoanele fizice sunt omise. Se actualizează denumirea, CUI, Reg. Com. și adresa. Email, IBAN și notele rămân neschimbate.`)) {
            return;
        }

        const label = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Se actualizează…';
        if (status) {
            status.className = 'mb-4 rounded-lg border border-slate-200 bg-slate-50 text-slate-800 px-4 py-3 text-sm';
            status.textContent = 'Se preiau datele din ANAF. Așteaptă câteva secunde…';
            status.classList.remove('hidden');
        }

        try {
            const res = await fetch(btn.dataset.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: '{}',
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                throw new Error(data.message || 'Actualizarea ANAF a eșuat.');
            }

            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(data));
            location.reload();
        } catch (e) {
            if (status) {
                status.className = 'mb-4 rounded-lg border border-red-200 bg-red-50 text-red-900 px-4 py-3 text-sm';
                status.textContent = e.message || 'Actualizarea ANAF a eșuat.';
            } else {
                alert(e.message || 'Actualizarea ANAF a eșuat.');
            }
            btn.disabled = false;
            btn.textContent = label;
        }
    });
})();
</script>
@endif
@endsection
