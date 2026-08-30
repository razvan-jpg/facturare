@extends('layouts.app')

@section('heading', 'Statistici')
@section('subheading', 'Vizitatori (inclusiv admin) · refresh automat la 30s')
@section('actions')
    <a href="{{ route('admin.companies') }}" class="dc-btn-primary">Societăți &amp; promoții</a>
@endsection

@section('content')
<div class="mb-6">
    <h2 class="font-display text-xl text-slate-900 mb-3">Vizitatori</h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="dc-card p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Total de la lansare</div>
            <div class="mt-2 text-3xl font-semibold tabular-nums">
                {{ number_format($visitorStats['all']['unique'], 0, ',', '.') }}
                <span class="text-slate-400 font-medium">/</span>
                {{ number_format($visitorStats['all']['total'], 0, ',', '.') }}
            </div>
            <div class="text-xs text-slate-500 mt-1">unici / total vizualizări</div>
        </div>
        <div class="dc-card p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Ultima lună</div>
            <div class="mt-2 text-3xl font-semibold tabular-nums">
                {{ number_format($visitorStats['month']['unique'], 0, ',', '.') }}
                <span class="text-slate-400 font-medium">/</span>
                {{ number_format($visitorStats['month']['total'], 0, ',', '.') }}
            </div>
            <div class="text-xs text-slate-500 mt-1">unici / total · 30 zile</div>
        </div>
        <div class="dc-card p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Ultima săptămână</div>
            <div class="mt-2 text-3xl font-semibold tabular-nums">
                {{ number_format($visitorStats['week']['unique'], 0, ',', '.') }}
                <span class="text-slate-400 font-medium">/</span>
                {{ number_format($visitorStats['week']['total'], 0, ',', '.') }}
            </div>
            <div class="text-xs text-slate-500 mt-1">unici / total · 7 zile</div>
        </div>
        <div class="dc-card p-4 border-teal-200 bg-teal-50/40">
            <div class="text-xs uppercase tracking-wide text-teal-800">Activi acum</div>
            <div class="mt-2 text-3xl font-semibold text-teal-900 tabular-nums">
                {{ number_format($visitorStats['active']['unique'], 0, ',', '.') }}
            </div>
            <div class="text-xs text-teal-700/80 mt-1">
                vizitatori unici · {{ $usersLogged }} logați acum
            </div>
        </div>
    </div>
</div>

@if($activeVisitors->isNotEmpty())
<div class="mb-6">
    <h2 class="font-display text-xl text-slate-900 mb-3">Activi acum</h2>
    <div class="dc-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full dc-table">
                <thead>
                    <tr>
                        <th>Văzut</th>
                        <th>{{ __('Țară') }}</th>
                        <th>Browser</th>
                        <th>Sistem</th>
                        <th>Utilizator</th>
                        <th>Pagină</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($activeVisitors as $visitor)
                    <tr>
                        <td>{{ $visitor->last_seen_at?->format('H:i:s') }}</td>
                        <td>
                            @if($visitor->country || $visitor->country_code)
                                <span class="font-medium">{{ $visitor->country ?: $visitor->country_code }}</span>
                                @if($visitor->country_code)
                                    <span class="text-slate-400 text-xs ml-1">{{ $visitor->country_code }}</span>
                                @endif
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td>{{ $visitor->browser_name }}</td>
                        <td>{{ $visitor->os_name }}</td>
                        <td>
                            @if($visitor->user)
                                <span class="font-medium">{{ $visitor->user->email }}</span>
                            @else
                                <span class="text-slate-400">anonim</span>
                            @endif
                        </td>
                        <td class="max-w-[180px] truncate">{{ $visitor->last_path }}</td>
                        <td class="text-slate-500">{{ $visitor->ip }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<div class="mb-6 grid lg:grid-cols-3 gap-6">
    @if($topCountries->isNotEmpty())
    <div>
        <h2 class="font-display text-xl text-slate-900 mb-3">Țări</h2>
        <div class="grid gap-3">
            @foreach($topCountries as $row)
                <div class="dc-card p-4 flex items-center justify-between gap-3">
                    <div>
                        <div class="font-semibold text-slate-900">{{ $row->country ?: $row->country_code }}</div>
                        <div class="text-xs text-slate-500 uppercase tracking-wide">{{ $row->country_code }}</div>
                    </div>
                    <div class="text-2xl font-semibold tabular-nums">{{ number_format($row->visitors, 0, ',', '.') }}</div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($topBrowsers->isNotEmpty())
    <div>
        <h2 class="font-display text-xl text-slate-900 mb-3">Browsere</h2>
        <div class="grid gap-3">
            @foreach($topBrowsers as $row)
                <div class="dc-card p-4 flex items-center justify-between gap-3">
                    <div class="font-semibold text-slate-900">{{ $row->label }}</div>
                    <div class="text-2xl font-semibold tabular-nums">{{ number_format($row->visitors, 0, ',', '.') }}</div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($topOperatingSystems->isNotEmpty())
    <div>
        <h2 class="font-display text-xl text-slate-900 mb-3">Sisteme de operare</h2>
        <div class="grid gap-3">
            @foreach($topOperatingSystems as $row)
                <div class="dc-card p-4 flex items-center justify-between gap-3">
                    <div class="font-semibold text-slate-900">{{ $row->label }}</div>
                    <div class="text-2xl font-semibold tabular-nums">{{ number_format($row->visitors, 0, ',', '.') }}</div>
                </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

<div class="mb-6">
    <h2 class="font-display text-xl text-slate-900 mb-3">Platformă</h2>
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="dc-card p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Utilizatori creați</div>
            <div class="mt-2 text-2xl font-semibold">{{ number_format($usersTotal, 0, ',', '.') }}</div>
            <div class="text-xs text-slate-500 mt-1">+{{ $usersWeek }} în ultima săptămână · {{ $usersLogged }} logați acum</div>
        </div>
        <div class="dc-card p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">{{ __('Societăți') }}</div>
            <div class="mt-2 text-2xl font-semibold">{{ number_format($companiesTotal, 0, ',', '.') }}</div>
        </div>
        <div class="dc-card p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">CRM FLY DAVID</div>
            <div class="mt-2 text-2xl font-semibold">{{ number_format($crmClientsTotal, 0, ',', '.') }}</div>
            <div class="text-xs text-slate-500 mt-1">clienți în firma operator</div>
        </div>
        <div class="dc-card p-4">
            <div class="text-xs uppercase tracking-wide text-slate-500">Facturi emise</div>
            <div class="mt-2 text-2xl font-semibold">{{ number_format($invoicesIssued, 0, ',', '.') }}</div>
            <div class="text-xs text-slate-500 mt-1">{{ $invoicesMonth }} în ultima lună</div>
        </div>
        <div class="dc-card p-4 sm:col-span-2">
            <div class="text-xs uppercase tracking-wide text-slate-500">Valoare facturată (30 zile)</div>
            <div class="mt-2 text-2xl font-semibold">{{ number_format($invoiceTotalMonth, 2, ',', '.') }} RON</div>
        </div>
        <div class="dc-card p-4 sm:col-span-2">
            <div class="text-xs uppercase tracking-wide text-slate-500">Încasări înregistrate (30 zile)</div>
            <div class="mt-2 text-2xl font-semibold">{{ number_format($paymentsMonth, 2, ',', '.') }} RON</div>
        </div>
    </div>
</div>

<div class="mb-6">
    <div class="flex flex-wrap items-end justify-between gap-2 mb-3">
        <h2 class="font-display text-xl text-slate-900">
            <a href="{{ route('admin.companies') }}" class="text-teal-900 hover:underline">Societăți pe platformă</a>
        </h2>
        <div class="text-xs text-slate-500">
            {{ number_format($companiesTotal, 0, ',', '.') }} în total · top 5 cele mai active
            @if($operatorCompany)
                · client FLY DAVID legat după CUI (dacă există în CRM)
            @endif
        </div>
    </div>
    <div class="dc-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full dc-table">
                <thead>
                    <tr>
                        <th>{{ __('Societate') }}</th>
                        <th>{{ __('CUI') }}</th>
                        <th>{{ __('Contact') }}</th>
                        <th>Localitate</th>
                        <th title="Client din CRM-ul firmei operator, legat după CUI">Client FLY DAVID</th>
                        <th>Cod promo</th>
                        <th title="Data până la care accesul rămâne în perioada promoțională / trial; după aceasta devine plătitor">Sfârșit perioadă promoțională</th>
                        <th title="Facturi emise de FLY DAVID către acest CUI">Facturi FLY DAVID</th>
                        <th title="Facturi emise de societate pe platformă">Emise pe platformă</th>
                        <th>Adăugat</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($platformCompanies as $company)
                    <tr>
                        <td>
                            <div class="font-medium">
                                {{ $company->name }}
                                @if($company->is_operator)
                                    <span class="ml-1 inline-flex rounded-full bg-slate-900 text-white px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">operator</span>
                                @endif
                            </div>
                            @if($company->reg_com)
                                <div class="text-xs text-slate-500">{{ $company->reg_com }}</div>
                            @endif
                            @if($company->owner)
                                <div class="text-xs text-slate-500 mt-0.5">{{ $company->owner->email }}</div>
                            @endif
                        </td>
                        <td class="font-mono text-sm">{{ $company->cui ?: '—' }}</td>
                        <td class="text-sm">
                            <div>{{ $company->email ?: '—' }}</div>
                            @if($company->phone)
                                <div class="text-xs text-slate-500">{{ $company->phone }}</div>
                            @endif
                        </td>
                        <td class="text-sm">
                            {{ collect([$company->city, $company->county])->filter()->implode(', ') ?: '—' }}
                        </td>
                        <td class="text-sm">
                            @if($company->fly_david_client)
                                <div class="font-medium">{{ $company->fly_david_client->name }}</div>
                                <div class="text-xs text-slate-500">
                                    {{ $company->fly_david_client->type === 'person' ? 'PF' : 'PJ' }}
                                    @if($company->fly_david_client->email)
                                        · {{ $company->fly_david_client->email }}
                                    @endif
                                </div>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="font-mono text-sm font-semibold tracking-wide">
                            {{ $company->promo_code ?: '—' }}
                        </td>
                        <td class="text-sm tabular-nums whitespace-nowrap">
                            @if($company->promo_ends_label)
                                <span class="text-teal-800 font-medium">{{ $company->promo_ends_label }}</span>
                            @elseif($company->promo_ends_at)
                                {{ dc_date($company->promo_ends_at) }}
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="tabular-nums text-sm">{{ number_format($company->billing_invoices_count, 0, ',', '.') }}</td>
                        <td class="tabular-nums text-sm">{{ number_format($company->platform_invoices_count, 0, ',', '.') }}</td>
                        <td class="text-xs tabular-nums text-slate-500">{{ dc_datetime($company->created_at) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-slate-500 py-8">
                            Încă nu există societăți pe platformă.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mb-6">
    <div class="flex flex-wrap items-end justify-between gap-2 mb-3">
        <h2 class="font-display text-xl text-slate-900">
            <a href="{{ route('admin.users') }}" class="text-teal-900 hover:underline">{{ __('Utilizatori') }}</a>
        </h2>
        <div class="text-xs text-slate-500">
            {{ number_format($usersTotal, 0, ',', '.') }} în total · top 5 cele mai active
            · {{ $usersLogged }} online acum
        </div>
    </div>
    <div class="dc-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full dc-table">
                <thead>
                    <tr>
                        <th>Cont</th>
                        <th>Plan</th>
                        <th>{{ __('Acces până la') }}</th>
                        <th>{{ __('Societăți') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Țară') }}</th>
                        <th>Browser / OS</th>
                        <th>Creat</th>
                        <th>Ultima activitate</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($registeredUsers as $user)
                    <tr>
                        <td>
                            <div class="font-medium">
                                <a href="{{ route('admin.users.show', $user) }}" class="text-teal-900 hover:underline">
                                    {{ $user->name }}
                                </a>
                                @if($user->is_admin)
                                    <span class="ml-1 inline-flex rounded-full bg-slate-900 text-white px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide">admin</span>
                                @endif
                            </div>
                            <div class="text-xs text-slate-500">{{ $user->email }}</div>
                        </td>
                        <td class="text-sm">
                            @if($user->is_admin)
                                <span class="text-slate-500">—</span>
                            @else
                                {{ $user->plan ?: '—' }}
                            @endif
                        </td>
                        <td class="text-sm tabular-nums">
                            @if($user->is_admin)
                                <span class="text-teal-800 font-medium">nelimitat</span>
                            @elseif($user->access_effective_until)
                                {{ dc_date($user->access_effective_until) }}
                            @elseif(($user->plan ?? '') === 'paid')
                                <span class="text-teal-800 font-medium">nelimitat</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="text-sm tabular-nums">
                            {{ number_format($user->companies_count, 0, ',', '.') }}
                            @if($user->owned_companies_count)
                                <span class="text-xs text-slate-500">({{ $user->owned_companies_count }} proprii)</span>
                            @endif
                        </td>
                        <td>
                            @if($user->is_logged_now)
                                <span class="inline-flex rounded-full bg-teal-100 text-teal-800 px-2 py-0.5 text-xs font-semibold">online</span>
                            @elseif($user->has_activity)
                                <span class="inline-flex rounded-full bg-slate-100 text-slate-600 px-2 py-0.5 text-xs font-semibold">offline</span>
                            @else
                                <span class="inline-flex rounded-full bg-amber-50 text-amber-800 px-2 py-0.5 text-xs font-semibold" title="Fără societăți, documente sau sesiune înregistrată">fără activitate</span>
                            @endif
                        </td>
                        <td class="text-sm">
                            @if($user->last_session?->country || $user->last_session?->country_code)
                                {{ $user->last_session->country ?: $user->last_session->country_code }}
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="text-sm">
                            <div>{{ $user->browser_name ?: '—' }}</div>
                            <div class="text-xs text-slate-500">{{ $user->os_name ?: '' }}</div>
                        </td>
                        <td class="text-xs tabular-nums text-slate-500">{{ dc_datetime($user->created_at) }}</td>
                        <td class="text-xs tabular-nums text-slate-500">{{ dc_datetime($user->last_activity_at) }}</td>
                        <td class="text-right whitespace-nowrap">
                            @if($user->is_admin || (int) $user->id === (int) auth()->id())
                                <span class="text-xs text-slate-400">—</span>
                            @else
                                @php
                                    $confirmMsg = 'Ștergi utilizatorul '.$user->email.'?';
                                    if ($user->owned_companies_count > 0) {
                                        $confirmMsg .= ' Se șterg și '.$user->owned_companies_count.' societăți proprii cu toate datele (facturi, clienți etc.).';
                                    }
                                    $confirmMsg .= ' Acțiunea este ireversibilă.';
                                @endphp
                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline"
                                      onsubmit="return confirm(@js($confirmMsg))">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-rose-700 hover:text-rose-900 hover:underline">
                                        Șterge
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center text-slate-500 py-8">Încă nu există utilizatori creați.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="dc-card overflow-hidden">
    <div class="px-4 py-3 border-b border-slate-100 font-semibold">Ultimele vizite</div>
    <div class="overflow-x-auto">
    <table class="w-full dc-table">
        <thead>
            <tr>
                <th>Ultima activitate</th>
                <th>{{ __('Țară') }}</th>
                <th>Browser</th>
                <th>Sistem</th>
                <th>Prima vizită</th>
                <th>Pagini</th>
                <th>Ultima pagină</th>
                <th>Utilizator</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
        @forelse($recentVisitors as $visitor)
            <tr>
                <td>{{ dc_datetime($visitor->last_seen_at) }}</td>
                <td>
                    @if($visitor->country || $visitor->country_code)
                        <span class="font-medium">{{ $visitor->country ?: $visitor->country_code }}</span>
                        @if($visitor->country_code)
                            <span class="text-slate-400 text-xs ml-1">{{ $visitor->country_code }}</span>
                        @endif
                    @else
                        <span class="text-slate-400">—</span>
                    @endif
                </td>
                <td>{{ $visitor->browser_name }}</td>
                <td>{{ $visitor->os_name }}</td>
                <td>{{ dc_datetime($visitor->first_seen_at) }}</td>
                <td>{{ $visitor->page_views }}</td>
                <td class="max-w-[160px] truncate">{{ $visitor->last_path }}</td>
                <td>{{ $visitor->user?->email ?: '—' }}</td>
                <td class="text-slate-500">{{ $visitor->ip }}</td>
            </tr>
        @empty
            <tr><td colspan="9" class="text-slate-500">Încă nu există vizite înregistrate.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    setTimeout(function () {
        window.location.reload();
    }, 30000);
</script>
@endpush
