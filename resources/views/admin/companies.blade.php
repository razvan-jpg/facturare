@extends('layouts.app')
@section('heading', 'Societăți & promoții')
@section('subheading', 'Perioade de acces pe proprietar · adaugă / scade (minim 1 săptămână) sau preset-uri mari')
@section('actions')
    <a href="{{ route('admin.orders') }}" class="dc-btn-secondary">Comenzi OP</a>
    <a href="{{ route('admin.stats') }}" class="dc-btn-secondary">Înapoi la Statistici</a>
@endsection

@section('content')
<form method="GET" action="{{ route('admin.companies') }}" class="dc-card p-4 mb-4 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[14rem]">
        <label class="dc-label">{{ __('Caută') }}</label>
        <input type="search" name="q" value="{{ $q }}" class="dc-input" placeholder="firmă, CUI, cod promo, email proprietar">
    </div>
    <button class="dc-btn-primary">{{ __('Filtrează') }}</button>
    @if($q !== '')
        <a href="{{ route('admin.companies') }}" class="dc-btn-secondary">{{ __('Resetează') }}</a>
    @endif
</form>

<div class="dc-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full dc-table">
            <thead>
                <tr>
                    <th>{{ __('Societate') }}</th>
                    <th>Cod promo</th>
                    <th>Proprietar</th>
                    <th>Plan</th>
                    <th>{{ __('Acces până la') }}</th>
                    <th>Zile rămase</th>
                    <th>Recomandări</th>
                    <th>Ajustează perioada</th>
                </tr>
            </thead>
            <tbody>
            @forelse($companies as $company)
                @php
                    $owner = $company->owner;
                    $until = $company->access_until_effective;
                    $days = $company->access_days_remaining;
                @endphp
                <tr>
                    <td>
                        <div class="font-medium">{{ $company->name }}</div>
                        <div class="text-xs text-slate-500">
                            @if($company->cui) CUI {{ $company->cui }} · @endif
                            #{{ $company->id }}
                        </div>
                        @if($company->referredByCompany)
                            <div class="text-xs text-teal-700 mt-0.5">
                                via
                                @if(filled($company->referredByCompany->promo_code))
                                    <button type="button"
                                            class="font-mono tracking-wider text-teal-800 hover:underline"
                                            title="{{ __('Click pentru a copia codul') }}"
                                            x-data="{ copied: false }"
                                            @click="
                                                navigator.clipboard.writeText(@js($company->referredByCompany->promo_code)).then(() => {
                                                    copied = true;
                                                    setTimeout(() => copied = false, 1600);
                                                }).catch(() => {
                                                    window.prompt(@js(__('Copiază codul promoțional:')), @js($company->referredByCompany->promo_code));
                                                })
                                            ">
                                        <span x-show="!copied">{{ $company->referredByCompany->promo_code }}</span>
                                        <span x-cloak x-show="copied">{{ __('Copiat!') }}</span>
                                    </button>
                                @else
                                    —
                                @endif
                                ({{ $company->referredByCompany->name }})
                            </div>
                        @endif
                    </td>
                    <td class="font-mono text-sm tracking-wider">
                        @if(filled($company->promo_code))
                            <button type="button"
                                    class="font-mono tracking-wider text-sm text-teal-800 hover:underline"
                                    title="{{ __('Click pentru a copia codul') }}"
                                    x-data="{ copied: false }"
                                    @click="
                                        navigator.clipboard.writeText(@js($company->promo_code)).then(() => {
                                            copied = true;
                                            setTimeout(() => copied = false, 1600);
                                        }).catch(() => {
                                            window.prompt(@js(__('Copiază codul promoțional:')), @js($company->promo_code));
                                        })
                                    ">
                                <span x-show="!copied">{{ $company->promo_code }}</span>
                                <span x-cloak x-show="copied">{{ __('Copiat!') }}</span>
                            </button>
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if($owner)
                            <div class="font-medium">{{ $owner->name }}</div>
                            <div class="text-xs text-slate-500">{{ $owner->email }}</div>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td>
                        @if($owner?->is_admin)
                            <span class="text-xs font-semibold text-amber-800">admin</span>
                        @else
                            <span class="text-xs font-medium">{{ $owner?->plan ?: '—' }}</span>
                        @endif
                    </td>
                    <td>
                        @if($owner?->is_admin)
                            <span class="text-teal-800 font-medium">Nelimitat</span>
                        @elseif($until)
                            <span class="tabular-nums">{{ dc_date($until) }}</span>
                            @if($owner?->access_until && $until && ! $owner->access_until->equalTo($until))
                                <div class="text-[11px] text-slate-400">access_until {{ dc_date($owner->access_until) }}</div>
                            @endif
                        @else
                            <span class="text-rose-600 font-medium">Expirat / nedefinit</span>
                        @endif
                    </td>
                    <td class="tabular-nums">
                        @if($owner?->is_admin)
                            —
                        @elseif($days === null)
                            —
                        @elseif($days <= 0)
                            <span class="text-rose-600 font-semibold">0</span>
                        @else
                            {{ $days }}
                        @endif
                    </td>
                    <td class="tabular-nums text-sm">
                        {{ (int) $company->referred_companies_count }}
                        @if((int) $company->referral_rewards_granted > 0)
                            <div class="text-[11px] text-slate-400">{{ $company->referral_rewards_granted }}× bonus</div>
                        @endif
                    </td>
                    <td>
                        @if($owner && ! $owner->is_admin)
                            <div class="space-y-2 min-w-[16rem]">
                                <form method="POST" action="{{ route('admin.companies.grant', $company) }}"
                                      class="flex flex-wrap items-center gap-1.5"
                                      x-data="{ weeks: 1 }"
                                      @submit="
                                          const btn = $event.submitter;
                                          if (!btn) return;
                                          const dir = btn.value;
                                          const w = Number(weeks) || 1;
                                          const label = w === 1 ? '1 săptămână' : (w + ' săptămâni');
                                          if (!confirm((dir === 'sub' ? 'Scade ' : 'Adaugă ') + label + ' pentru ' + @js($company->name) + '?')) {
                                              $event.preventDefault();
                                          }
                                      ">
                                    @csrf
                                    <input id="weeks-{{ $company->id }}"
                                           type="number"
                                           name="weeks"
                                           x-model.number="weeks"
                                           min="1"
                                           max="104"
                                           step="1"
                                           required
                                           aria-label="Săptămâni"
                                           class="dc-input w-16 text-sm py-1 px-2 tabular-nums"
                                           title="Număr de săptămâni (minim 1)">
                                    <span class="text-xs text-slate-500">săpt.</span>
                                    <button type="submit" name="direction" value="add"
                                            class="dc-btn-primary text-xs px-2 py-1">+</button>
                                    <button type="submit" name="direction" value="sub"
                                            class="dc-btn-secondary text-xs px-2 py-1 text-rose-700 border-rose-200">−</button>
                                </form>
                                <div class="flex flex-wrap gap-1">
                                    @foreach([
                                        '1w' => '+1s',
                                        '2w' => '+2s',
                                        '4w' => '+4s',
                                        '1m' => '+1l',
                                        '3m' => '+3l',
                                        '6m' => '+6l',
                                        '1y' => '+1a',
                                    ] as $preset => $btnLabel)
                                        <form method="POST" action="{{ route('admin.companies.grant', $company) }}" class="inline"
                                              onsubmit="return confirm(@js('Acordă '.$btnLabel.' pentru '.$company->name.'?'))">
                                            @csrf
                                            <input type="hidden" name="preset" value="{{ $preset }}">
                                            <button class="dc-btn-secondary text-[11px] px-1.5 py-0.5">{{ $btnLabel }}</button>
                                        </form>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-slate-500 py-8">Nicio societate găsită.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $companies->links() }}
</div>

<p class="mt-4 text-xs text-slate-500">
    Ajustările scriu <code>access_until</code> pe contul proprietarului (baza = data efectivă curentă).
    Poți adăuga sau scădea în multipli de săptămâni (1–104). Preset-uri: săptămâni / luni / 1 an.
    Scăderea sub promoția platformă (31.03.2027) este respectată ca plafon admin.
</p>
@endsection
