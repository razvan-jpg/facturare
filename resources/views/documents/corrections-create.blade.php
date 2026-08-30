@extends('layouts.app')
@section('heading', $heading)
@section('subheading', $subheading)
@section('shell_pad', 'px-2 sm:px-3 lg:px-4')

@section('content')
@php
    $actionLabel = $kind === 'storno' ? 'Emite storno' : 'Emite notă de creditare';
    $emptyLabel = $kind === 'storno'
        ? 'Nu există facturi emise care pot fi stornate (sau au deja storno / notă de creditare).'
        : 'Nu există facturi emise eligibile pentru notă de creditare (sau au deja storno / NC).';
@endphp

<div class="dc-card overflow-hidden w-full">
    <div class="px-4 py-3 border-b border-slate-100 text-sm text-slate-600">
        Se creează un document cu linii negative pe baza facturii selectate, pe serie
        {{ $kind === 'storno' ? 'de factură (status storno)' : 'de notă de creditare (NC)' }}.
        Poate fi trimis ulterior în e-Factura.
    </div>

    @if($invoices->isEmpty())
        <div class="p-8 text-center text-slate-500">{{ $emptyLabel }}</div>
    @else
        <table class="w-full dc-table">
            <thead>
                <tr>
                    <th>{{ __('Număr') }}</th>
                    <th>Data</th>
                    <th>{{ __('Client') }}</th>
                    <th>{{ __('Total') }}</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($invoices as $doc)
                <tr>
                    <td class="font-medium">
                        <a href="{{ route('documents.show', $doc) }}" class="text-teal-800 hover:underline">
                            {{ $doc->number_full ?: '#'.$doc->id }}
                        </a>
                    </td>
                    <td>{{ dc_date($doc->issue_date) }}</td>
                    <td>{{ $doc->client_name ?: '—' }}</td>
                    <td class="tabular-nums">{{ number_format($doc->total, 2, ',', '.') }} {{ $doc->currency }}</td>
                    <td class="text-right">
                        <form method="POST" action="{{ route('documents.corrections.store', ['kind' => $kind]) }}"
                              onsubmit="return confirm(@js($kind === 'storno'
                                  ? 'Emți factură storno pentru '.$doc->number_full.'?'
                                  : 'Emți notă de creditare pentru '.$doc->number_full.'?'))">
                            @csrf
                            <input type="hidden" name="document_id" value="{{ $doc->id }}">
                            <button class="dc-btn-primary text-sm">{{ $actionLabel }}</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
