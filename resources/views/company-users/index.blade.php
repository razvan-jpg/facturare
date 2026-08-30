@extends('layouts.app')
@section('heading', 'Utilizatori')
@section('subheading', 'Subuseri creați și utilizatori invitați pe firmele tale')
@section('actions')
    @if($billingCompany && empty($seatSummary['unlimited']))
        <a href="{{ route('billing.seats', $billingCompany) }}" class="dc-btn-secondary">Abonament utilizatori</a>
    @endif
    <a href="{{ route('company-users.create') }}" class="dc-btn-primary">Adaugă utilizator</a>
@endsection

@section('content')
<div class="dc-card p-4 sm:p-5 mb-4 text-sm space-y-1">
    <div class="font-semibold text-slate-900">Locuri subuser</div>
    @if(! empty($seatSummary['unlimited']))
        <p class="text-slate-600">
            Cont administrator: subuserii și invitații sunt <strong>nelimitați</strong>
            (fără abonament de locuri și fără limită de perioadă).
        </p>
        <p class="text-slate-500">Colaboratori acum: {{ $seatSummary['used'] }}</p>
    @elseif(! $seatSummary['billable'])
        <p class="text-slate-600">
            Până la {{ $seatSummary['billable_from']->format('d.m.Y') }} locurile sunt <strong>gratuite</strong>.
            Apoi: <strong>{{ $seatSummary['price_label'] }}</strong> (+ TVA), cumpărate de tine din
            @if($billingCompany)
                <a href="{{ route('billing.seats', $billingCompany) }}" class="text-teal-800 underline">Abonament utilizatori</a>.
            @else
                Abonament utilizatori.
            @endif
        </p>
        <p class="text-slate-500">Subuseri creați acum: {{ $seatSummary['used'] }}</p>
    @elseif($seatSummary['active'])
        <p class="text-slate-600">
            {{ $seatSummary['used'] }} / {{ $seatSummary['quota'] }} locuri folosite ·
            valabile până la {{ dc_date($seatSummary['until']) }} ·
            disponibile: {{ $seatSummary['available'] }}
        </p>
    @else
        <p class="text-amber-800">
            Nu ai locuri active. Din {{ $seatSummary['billable_from']->format('d.m.Y') }} este necesar abonamentul
            ({{ $seatSummary['price_label'] }}).
            @if($billingCompany)
                <a href="{{ route('billing.seats', $billingCompany) }}" class="underline font-semibold">Cumpără locuri</a>
            @endif
        </p>
    @endif
</div>

<div class="dc-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full dc-table">
            <thead>
                <tr>
                    <th>{{ __('Nume') }}</th>
                    <th>Email</th>
                    <th>Tip</th>
                    <th>Societăți cu acces</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                <tr>
                    <td class="font-medium">
                        <a href="{{ route('company-users.edit', $user) }}" class="text-teal-900 hover:underline">{{ $user->name }}</a>
                    </td>
                    <td class="text-sm">{{ $user->email }}</td>
                    <td class="text-xs text-slate-600">
                        @if($user->is_admin)
                            Admin invitat
                        @elseif(! empty($user->is_invited_collaborator))
                            Invitat
                        @else
                            Subuser creat
                        @endif
                    </td>
                    <td class="tabular-nums text-sm">{{ $user->companies_count }}</td>
                    <td class="text-right whitespace-nowrap">
                        <div class="dc-act-wrap">
                            <a href="{{ route('company-users.edit', $user) }}" class="dc-act">Drepturi</a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-slate-500 py-10">
                        Nu ai încă subuseri sau invitați. Adaugă un utilizator (cont nou sau email existent).
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
