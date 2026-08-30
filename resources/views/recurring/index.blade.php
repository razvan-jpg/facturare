@extends('layouts.app')
@php
    $canManage = app(\App\Services\CompanyPermission::class)->can(auth()->user(), $company, 'recurring_manage');
    $seriesNextByRecurringId = $seriesNextByRecurringId ?? [];
    $listPage = max(1, (int) $recurring->currentPage());
    $pageQuery = $listPage > 1 ? ['page' => $listPage] : [];
@endphp
@section('heading', 'Facturi recurente')
@section('subheading', 'Abonamente cu emitere automată săptămânală, lunară, trimestrială, semestrială sau anuală')
@section('actions')
@if($canManage)
    <a href="{{ route('recurring.create') }}" class="dc-btn-primary">+ Abonament nou</a>
@endif
@endsection

@section('content')
@include('partials.pagination', ['paginator' => $recurring, 'perPage' => $perPage ?? null, 'class' => 'mb-4'])
<div class="dc-card overflow-hidden">
    <table class="w-full dc-table">
        <thead>
            <tr>
                <th>{{ __('Abonament') }}</th>
                <th>{{ __('Client') }}</th>
                <th>Tip</th>
                <th>{{ __('Serie') }}</th>
                <th>{{ __('Frecvență') }}</th>
                <th>Următoarea</th>
                <th>{{ __('Status') }}</th>
                <th>Generate</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($recurring as $row)
            @php
                $serie = $seriesNextByRecurringId[$row->id] ?? ['prefix' => ($row->series ?: 'implicită'), 'next_full' => null];
                $currency = strtoupper((string) ($row->currency ?: 'RON'));
                $total = $row->estimatedTotal();
                $bnr = ($currency !== 'RON') ? ($fxRates[$currency] ?? null) : null;
                $markup = max(0, (float) ($row->fx_markup_percent ?? 0));
                $fx = $bnr ? round($bnr * (1 + ($markup / 100)), 4) : null;
                $ron = $fx ? round($total * $fx, 2) : null;
            @endphp
            <tr>
                <td>
                    <a href="{{ route('recurring.show', $row) }}{{ $pageQuery ? '?'.http_build_query($pageQuery) : '' }}" class="font-medium text-teal-900 hover:underline">{{ $row->displayTitle() }}</a>
                    <div class="mt-0.5 text-sm font-semibold text-slate-800 tabular-nums">
                        {{ number_format($total, 2, ',', '.') }} {{ $currency }}
                    </div>
                    @if($ron !== null)
                        <div class="text-sm font-semibold text-teal-800 tabular-nums" title="Estimare la cursul BNR{{ $markup > 0 ? ' + '.$markup.'%' : '' }} curent">
                            ≈ {{ number_format($ron, 2, ',', '.') }} RON
                            <span class="text-xs font-normal text-slate-500">(1 {{ $currency }} = {{ number_format($fx, 4, ',', '.') }}@if($markup > 0) · BNR+{{ rtrim(rtrim(number_format($markup, 2, ',', '.'), '0'), ',') }}%@endif)</span>
                        </div>
                    @elseif($currency !== 'RON')
                        <div class="text-xs text-amber-700">Echivalent RON indisponibil (curs BNR)</div>
                    @endif
                </td>
                <td>{{ $row->client?->name }}</td>
                <td>
                    <span class="font-medium text-slate-800">{{ $row->documentTypeLabel() }}</span>
                </td>
                <td>
                    <div class="font-medium text-slate-800">{{ $serie['prefix'] }}</div>
                    @if(!empty($serie['next_full']))
                        <div class="text-xs text-slate-500" title="Numărul se rezervă abia la emitere">urm. {{ $serie['next_full'] }}</div>
                    @endif
                </td>
                <td>{{ $row->frequencyLabel() }}</td>
                <td>{{ $row->active ? dc_date($row->next_run_date) : '—' }}</td>
                <td>
                    @if($row->active)
                        <span class="text-blue-700 text-sm font-bold">{{ __('Activ') }}</span>
                    @else
                        <span class="text-red-700 text-sm font-bold">{{ __('Inactiv') }}</span>
                    @endif
                </td>
                <td>{{ $row->generated_count }}</td>
                <td class="text-right whitespace-nowrap">
                    <div class="dc-act-wrap">
                        <a href="{{ route('recurring.preview', $row) }}{{ $pageQuery ? '?'.http_build_query($pageQuery) : '' }}"
                           class="dc-act"
                           target="_blank"
                           rel="noopener"
                           title="Previzualizare PDF pentru următoarea emitere (data: {{ dc_date($row->next_run_date ?: $row->start_date) }})">
                            {{ __('Preview') }}
                        </a>
                    @if($canManage)
                        <a href="{{ route('recurring.edit', $row) }}{{ $pageQuery ? '?'.http_build_query($pageQuery) : '' }}" class="dc-act">{{ __('Editează') }}</a>
                        <form class="inline" method="POST" action="{{ route('recurring.destroy', $row) }}"
                              onsubmit="return confirm(@js('Ștergi abonamentul „'.$row->displayTitle().'”? Această acțiune este definitivă. Documentele deja emise rămân în liste.'))">
                            @csrf @method('DELETE')
                            @if($listPage > 1)
                                <input type="hidden" name="return_page" value="{{ $listPage }}">
                            @endif
                            <button type="submit" class="dc-act dc-act-danger">{{ __('Șterge') }}</button>
                        </form>
                    @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9" class="text-slate-500 py-8 text-center">
                    Nicio factură recurentă încă.
                    @if($canManage)
                    <a href="{{ route('recurring.create') }}" class="underline text-teal-800">Creează primul abonament</a>
                    @endif
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>
@include('partials.pagination', ['paginator' => $recurring, 'perPage' => $perPage ?? null, 'class' => 'mt-4'])
@endsection
