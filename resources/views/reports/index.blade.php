@extends('layouts.app')
@section('heading', 'Rapoarte')
@section('actions')
<a href="{{ route('reports.export', ['from' => $from, 'to' => $to]) }}" class="dc-btn-secondary">{{ __('Export CSV') }}</a>
@endsection
@section('content')
<form class="dc-card p-4 mb-6 flex flex-wrap gap-3 items-end" method="GET">
    @include('partials.date-input', ['name' => 'from', 'label' => 'De la', 'value' => $from])
    @include('partials.date-input', ['name' => 'to', 'label' => 'Până la', 'value' => $to])
    <button class="dc-btn-primary">{{ __('Actualizează') }}</button>
</form>

<div class="grid sm:grid-cols-3 gap-4 mb-6">
    <div class="dc-card p-4"><div class="text-xs uppercase text-slate-500">Vânzări</div><div class="text-2xl font-semibold mt-1">{{ number_format($sales->sum('total'), 2, ',', '.') }} RON</div></div>
    <div class="dc-card p-4"><div class="text-xs uppercase text-slate-500">{{ __('Încasări') }}</div><div class="text-2xl font-semibold mt-1">{{ number_format($payments->sum('amount'), 2, ',', '.') }} RON</div></div>
    <div class="dc-card p-4"><div class="text-xs uppercase text-slate-500">Neîncasat</div><div class="text-2xl font-semibold mt-1">{{ number_format($unpaidTotal ?? $unpaid->sum(fn($d)=>$d->remainingAmount()), 2, ',', '.') }} RON</div></div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <div class="dc-card overflow-hidden">
        <div class="px-4 py-3 border-b font-semibold">Pe client</div>
        <table class="w-full dc-table">
            <thead><tr><th>{{ __('Client') }}</th><th>{{ __('Facturi') }}</th><th>{{ __('Total') }}</th><th>Încasat</th></tr></thead>
            <tbody>
            @forelse($byClient as $name => $row)
            <tr>
                <td>{{ $name ?: '—' }}</td>
                <td>{{ $row['count'] }}</td>
                <td>{{ number_format($row['total'], 2, ',', '.') }}</td>
                <td>{{ number_format($row['paid'], 2, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-slate-500">Nu există date.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="dc-card overflow-hidden">
        <div class="px-4 py-3 border-b font-semibold">{{ __('Facturi neplatite') }}</div>
        <table class="w-full dc-table">
            <thead><tr><th>{{ __('Document') }}</th><th>{{ __('Scadență') }}</th><th>{{ __('Rest') }}</th></tr></thead>
            <tbody>
            @forelse($unpaid as $doc)
            <tr>
                <td><a href="{{ route('documents.show', $doc) }}" class="text-teal-800 hover:underline">{{ $doc->number_full }}</a><div class="text-xs text-slate-500">{{ $doc->client_name }}</div></td>
                <td>{{ dc_date($doc->due_date) }}</td>
                <td>{{ number_format($doc->remainingAmount(), 2, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="3" class="text-slate-500">Totul e la zi.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
