@extends('layouts.app')
@section('heading', __('Utilizatori'))
@section('subheading', number_format($usersTotal, 0, ',', '.').' în total · sortați după ultima activitate · '.$usersLogged.' online acum')
@section('actions')
    <a href="{{ route('admin.stats') }}" class="dc-btn-secondary">Înapoi la Statistici</a>
@endsection

@section('content')
<form method="GET" action="{{ route('admin.users') }}" class="dc-card p-4 mb-4 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-[14rem]">
        <label class="dc-label">{{ __('Caută') }}</label>
        <input type="search" name="q" value="{{ $q }}" class="dc-input" placeholder="nume sau email">
    </div>
    <button class="dc-btn-primary">{{ __('Filtrează') }}</button>
    @if($q !== '')
        <a href="{{ route('admin.users') }}" class="dc-btn-secondary">{{ __('Resetează') }}</a>
    @endif
</form>

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
            @forelse($users as $user)
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
                    <td colspan="10" class="text-center text-slate-500 py-8">
                        @if($q !== '')
                            Niciun utilizator nu corespunde căutării.
                        @else
                            Încă nu există utilizatori creați.
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($users->hasPages())
    <div class="mt-4">{{ $users->links() }}</div>
@endif
@endsection
