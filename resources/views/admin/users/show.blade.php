@extends('layouts.app')
@section('heading', $user->name)
@section('subheading', 'Detaliu utilizator · #'.$user->id)
@section('actions')
    <a href="{{ route('admin.stats') }}" class="dc-btn-secondary">Înapoi la Statistici</a>
    @if(! $user->is_admin && (int) $user->id !== (int) auth()->id())
        @php
            $confirmMsg = 'Ștergi utilizatorul '.$user->email.'?';
            if ($user->ownedCompanies->count() > 0) {
                $confirmMsg .= ' Se șterg și '.$user->ownedCompanies->count().' societăți proprii cu toate datele.';
            }
            $confirmMsg .= ' Acțiunea este ireversibilă.';
        @endphp
        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="inline"
              onsubmit="return confirm(@js($confirmMsg))">
            @csrf
            @method('DELETE')
            <button type="submit" class="dc-btn-secondary text-rose-700 border-rose-200 hover:border-rose-400">Șterge cont</button>
        </form>
    @endif
@endsection

@section('content')
<div class="grid lg:grid-cols-2 gap-4 mb-6">
    <div class="dc-card p-4 sm:p-5 space-y-3">
        <h2 class="font-display text-lg text-slate-900">Cont</h2>
        <dl class="grid grid-cols-[8rem_1fr] gap-x-3 gap-y-2 text-sm">
            <dt class="text-slate-500">ID</dt>
            <dd class="tabular-nums">{{ $user->id }}</dd>
            <dt class="text-slate-500">{{ __('Nume') }}</dt>
            <dd class="font-medium">
                {{ $user->name }}
                @if($user->is_admin)
                    <span class="ml-1 inline-flex rounded-full bg-slate-900 text-white px-2 py-0.5 text-[10px] font-semibold uppercase">admin</span>
                @endif
            </dd>
            <dt class="text-slate-500">Email</dt>
            <dd><a href="mailto:{{ $user->email }}" class="text-teal-800 hover:underline">{{ $user->email }}</a></dd>
            <dt class="text-slate-500">Plan</dt>
            <dd>{{ $user->is_admin ? '—' : ($user->plan ?: '—') }}</dd>
            <dt class="text-slate-500">{{ __('Acces până la') }}</dt>
            <dd>
                @if($user->is_admin)
                    <span class="text-teal-800 font-medium">nelimitat</span>
                @elseif($accessEffectiveUntil)
                    {{ dc_date($accessEffectiveUntil) }}
                @elseif(($user->plan ?? '') === 'paid')
                    <span class="text-teal-800 font-medium">nelimitat</span>
                @else
                    —
                @endif
                @if($accessLabel)
                    <span class="block text-xs text-slate-500 mt-0.5">{{ $accessLabel }}</span>
                @endif
            </dd>
            <dt class="text-slate-500">access_until</dt>
            <dd class="tabular-nums text-xs">{{ $user->access_until ? dc_datetime($user->access_until) : '—' }}</dd>
            <dt class="text-slate-500">trial_ends_at</dt>
            <dd class="tabular-nums text-xs">{{ $user->trial_ends_at ? dc_datetime($user->trial_ends_at) : '—' }}</dd>
            <dt class="text-slate-500">Limbă UI</dt>
            <dd>{{ $user->ui_locale ?: 'ro' }}</dd>
            <dt class="text-slate-500">Firmă curentă</dt>
            <dd>
                @if($currentCompany)
                    {{ $currentCompany->name }} <span class="text-xs text-slate-500">(#{{ $currentCompany->id }})</span>
                @else
                    —
                @endif
            </dd>
            <dt class="text-slate-500">Creat</dt>
            <dd class="tabular-nums">{{ dc_datetime($user->created_at) }}</dd>
            <dt class="text-slate-500">Actualizat</dt>
            <dd class="tabular-nums">{{ dc_datetime($user->updated_at) }}</dd>
        </dl>
    </div>

    <div class="dc-card p-4 sm:p-5 space-y-3">
        <h2 class="font-display text-lg text-slate-900">Activitate</h2>
        <dl class="grid grid-cols-[8rem_1fr] gap-x-3 gap-y-2 text-sm">
            <dt class="text-slate-500">{{ __('Status') }}</dt>
            <dd>
                @if($isOnline)
                    <span class="inline-flex rounded-full bg-teal-100 text-teal-800 px-2 py-0.5 text-xs font-semibold">online</span>
                @elseif($lastSession)
                    <span class="inline-flex rounded-full bg-slate-100 text-slate-600 px-2 py-0.5 text-xs font-semibold">offline</span>
                @else
                    <span class="inline-flex rounded-full bg-amber-50 text-amber-800 px-2 py-0.5 text-xs font-semibold">fără sesiune</span>
                @endif
            </dd>
            <dt class="text-slate-500">Ultima vizită</dt>
            <dd class="tabular-nums">{{ $lastSession?->last_seen_at ? dc_datetime($lastSession->last_seen_at) : '—' }}</dd>
            <dt class="text-slate-500">Ultima pagină</dt>
            <dd class="font-mono text-xs break-all">{{ $lastSession?->last_path ?: '—' }}</dd>
            <dt class="text-slate-500">IP</dt>
            <dd class="font-mono text-xs">{{ $lastSession?->ip ?: '—' }}</dd>
            <dt class="text-slate-500">{{ __('Țară') }}</dt>
            <dd>{{ $lastSession?->country ?: ($lastSession?->country_code ?: '—') }}</dd>
            <dt class="text-slate-500">{{ __('Societăți') }}</dt>
            <dd class="tabular-nums">{{ $companies->count() }} ({{ $user->ownedCompanies->count() }} proprii)</dd>
            <dt class="text-slate-500">Comenzi</dt>
            <dd class="tabular-nums">{{ $orders->count() }} recente afișate</dd>
        </dl>
    </div>
</div>

<div class="mb-6">
    <div class="flex flex-wrap items-end justify-between gap-2 mb-3">
        <h2 class="font-display text-xl text-slate-900">{{ __('Societăți') }}</h2>
        <div class="text-xs text-slate-500">Click „Intră” pentru a lucra în firmă ca admin</div>
    </div>
    <div class="dc-card overflow-hidden mb-4">
        <div class="overflow-x-auto">
            <table class="w-full dc-table">
                <thead>
                    <tr>
                        <th>{{ __('Societate') }}</th>
                        <th>CUI</th>
                        <th>Rol</th>
                        <th>Creată</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($companies as $company)
                    <tr>
                        <td>
                            <div class="font-medium">{{ $company->name }}</div>
                            <div class="text-xs text-slate-500">#{{ $company->id }}</div>
                        </td>
                        <td class="font-mono text-sm">{{ $company->cui ?: '—' }}</td>
                        <td class="text-sm">
                            @if($company->is_owned)
                                <span class="inline-flex rounded-full bg-teal-100 text-teal-800 px-2 py-0.5 text-xs font-semibold">proprie</span>
                            @endif
                            @if($company->membership_role)
                                <span class="inline-flex rounded-full bg-slate-100 text-slate-700 px-2 py-0.5 text-xs font-semibold">{{ $company->membership_role }}</span>
                            @elseif(! $company->is_member)
                                <span class="text-xs text-slate-400">doar owner_id</span>
                            @endif
                        </td>
                        <td class="text-xs tabular-nums text-slate-500">{{ dc_datetime($company->created_at) }}</td>
                        <td class="text-right whitespace-nowrap space-x-2">
                            <form method="POST" action="{{ route('admin.users.enter-company', [$user, $company]) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-xs font-semibold text-teal-800 hover:underline">Intră</button>
                            </form>
                            @if($company->is_member && ! $company->is_owned)
                                <form method="POST" action="{{ route('admin.users.detach-company', [$user, $company]) }}" class="inline"
                                      onsubmit="return confirm('Revoci accesul la {{ $company->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-rose-700 hover:underline">Revocă</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-slate-500 py-8">Nicio societate legată de acest utilizator.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if(! $user->is_admin)
    <div class="dc-card p-4 sm:p-5">
        <h3 class="font-semibold text-slate-900 mb-3">Asociază o societate</h3>
        <p class="text-xs text-slate-500 mb-3">Poți da acces la orice firmă din platformă, chiar dacă nu a fost creată de acest utilizator.</p>
        <form method="POST" action="{{ route('admin.users.attach-company', $user) }}" class="flex flex-wrap gap-3 items-end">
            @csrf
            <div class="flex-1 min-w-[14rem]">
                <label class="dc-label" for="company_id">{{ __('Societate') }}</label>
                <select id="company_id" name="company_id" class="dc-input" required>
                    <option value="">— alege —</option>
                    @foreach($attachableCompanies as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}@if($c->cui) ({{ $c->cui }})@endif</option>
                    @endforeach
                </select>
            </div>
            <div class="w-40">
                <label class="dc-label" for="role">Rol</label>
                <select id="role" name="role" class="dc-input" required>
                    <option value="operator" selected>operator</option>
                    <option value="owner">owner</option>
                </select>
            </div>
            <button type="submit" class="dc-btn-primary">Asociază</button>
        </form>
        @if($attachableCompanies->isEmpty())
            <p class="text-xs text-slate-500 mt-2">Toate societățile din platformă sunt deja legate de acest utilizator.</p>
        @endif
    </div>
    @endif
</div>

<div class="mb-6">
    <h2 class="font-display text-xl text-slate-900 mb-3">Comenzi abonament</h2>
    <div class="dc-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full dc-table">
                <thead>
                    <tr>
                        <th>{{ __('Număr') }}</th>
                        <th>Perioadă</th>
                        <th>{{ __('Sumă') }}</th>
                        <th>{{ __('Plată') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>Creată</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td class="font-mono text-sm">{{ $order->number }}</td>
                        <td class="text-sm">{{ $order->periodLabel() }}</td>
                        <td class="tabular-nums text-sm">{{ number_format($order->amount_total, 2, ',', '.') }} {{ $order->currency }}</td>
                        <td class="text-sm">{{ $order->payment_method }}@if($order->payment_processor) / {{ $order->payment_processor }}@endif</td>
                        <td class="text-sm">{{ $order->status }}</td>
                        <td class="text-xs tabular-nums text-slate-500">{{ dc_datetime($order->created_at) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-slate-500 py-8">Nicio comandă de abonament.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
