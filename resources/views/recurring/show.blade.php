@extends('layouts.app')
@php
    $returnPage = max(1, (int) ($returnPage ?? request('page', 1)));
    $listUrl = route('recurring.index', $returnPage > 1 ? ['page' => $returnPage] : []);
    $editUrl = route('recurring.edit', $recurring).($returnPage > 1 ? '?page='.$returnPage : '');
@endphp
@section('heading', $recurring->displayTitle())
@section('subheading')
{{ $recurring->frequencyLabel() }} · client {{ $recurring->client?->name }}
@endsection
@section('actions')
<a href="{{ $listUrl }}" class="dc-btn-secondary">Înapoi la lista de abonamente</a>
@if(app(\App\Services\CompanyPermission::class)->can(auth()->user(), $company, 'recurring_manage'))
<a href="{{ $editUrl }}" class="dc-btn-secondary">{{ __('Editează') }}</a>
<a href="{{ route('recurring.preview', $recurring) }}" target="_blank" class="dc-btn-secondary">Preview factură</a>
<form method="POST" action="{{ route('recurring.toggle', $recurring) }}">@csrf
    <button class="dc-btn-secondary">{{ $recurring->active ? 'Dezactivează' : 'Activează' }}</button>
</form>
<form method="POST" action="{{ route('recurring.generate', $recurring) }}" onsubmit="return confirm('Generezi {{ $recurring->documentType() === 'proforma' ? 'proforma' : 'factura' }} acum? Data următoare se va avansa.')">@csrf
    <button class="dc-btn-primary">Generează acum</button>
</form>
@else
<a href="{{ route('recurring.preview', $recurring) }}" target="_blank" class="dc-btn-secondary">Preview factură</a>
@endif
@endsection

@section('content')
<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="dc-card p-6">
            <h3 class="font-semibold mb-3">{{ __('Detalii') }}</h3>
            <dl class="grid sm:grid-cols-2 gap-3 text-sm">
                <div><div class="text-slate-500">{{ __('Status') }}</div><div class="font-medium {{ $recurring->active ? 'text-blue-700 font-bold' : 'text-red-700 font-bold' }}">{{ $recurring->active ? 'Activ' : 'Inactiv' }}</div></div>
                <div><div class="text-slate-500">{{ __('Tip document') }}</div><div class="font-medium">{{ $recurring->documentTypeLabel() }}</div></div>
                <div><div class="text-slate-500">{{ __('Frecvență') }}</div><div class="font-medium">{{ $recurring->frequencyLabel() }}</div></div>
                <div><div class="text-slate-500">{{ __('Următoarea emitere') }}</div><div class="font-medium">{{ $recurring->active ? dc_date($recurring->next_run_date) : '—' }}</div></div>
                <div><div class="text-slate-500">Start</div><div class="font-medium">{{ dc_date($recurring->start_date) }}</div></div>
                <div><div class="text-slate-500">Final</div><div class="font-medium">{{ $recurring->end_date ? dc_date($recurring->end_date) : 'nedefinit' }}</div></div>
                <div><div class="text-slate-500">{{ __('Scadență') }}</div><div class="font-medium">{{ $recurring->due_days }} zile de la emitere</div></div>
                <div><div class="text-slate-500">{{ __('Serie') }}</div><div class="font-medium">{{ $recurring->series ?: 'implicită' }}</div></div>
                <div><div class="text-slate-500">Limbă</div><div class="font-medium">{{ strtoupper($recurring->document_language ?: 'ro') }}</div></div>
                <div><div class="text-slate-500">Nr. abonament</div><div class="font-medium">{{ $recurring->subscription_number ?: '—' }}</div></div>
                <div><div class="text-slate-500">Nr. documente</div><div class="font-medium">{{ $recurring->hasDocumentLimit() ? ($recurring->generated_count.'/'.$recurring->max_documents) : 'nelimitat' }}</div></div>
                <div><div class="text-slate-500">Emitere automată</div><div class="font-medium">{{ $recurring->auto_issue ? 'Da (emisă)' : 'Nu (doar draft)' }}</div></div>
                <div><div class="text-slate-500">Total estimat</div><div class="font-medium">{{ number_format($recurring->estimatedTotal(), 2, ',', '.') }} {{ $recurring->currency }}</div></div>
                @if(($pendingPenalties ?? 0) > 0.009)
                <div class="sm:col-span-2">
                    <div class="text-slate-500">Penalități nefacturate (vor apărea pe următoarea factură)</div>
                    <div class="font-medium text-amber-900 tabular-nums">{{ number_format($pendingPenalties, 2, ',', '.') }} {{ $recurring->currency }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">Vezi și butonul <strong>Preview factură</strong>.</div>
                </div>
                @endif
            </dl>
            @if($recurring->notes)
                <p class="mt-4 text-sm text-slate-600">{{ $recurring->notes }}</p>
            @endif
        </div>

        <div class="dc-card overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-100 font-semibold">Linii template</div>
            <table class="w-full dc-table">
                <thead><tr><th>Denumire</th><th>UM</th><th>Cant.</th><th>{{ __('Preț') }}</th><th>{{ __('TVA') }}</th></tr></thead>
                <tbody>
                @foreach($recurring->items as $item)
                    <tr>
                        <td>{{ $item->name }}</td>
                        <td>{{ \App\Support\MeasureUnits::short($item->unit) }}</td>
                        <td>{{ number_format($item->quantity, 2, ',', '.') }}</td>
                        <td>{{ number_format($item->unit_price, 2, ',', '.') }}</td>
                        <td>{{ number_format($item->vat_rate, 2, ',', '.') }}%</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="space-y-4">
        <div class="dc-card p-5">
            <h3 class="font-semibold mb-2">Istoric</h3>
            <div class="text-sm text-slate-600 mb-3">{{ $recurring->generated_count }} documente generate</div>
            <ul class="space-y-2 text-sm">
                @forelse($recurring->documents as $doc)
                    <li class="flex justify-between gap-2 border-b border-slate-100 pb-2">
                        <a href="{{ route('documents.show', $doc) }}" class="text-teal-800 underline">{{ $doc->number_full ?: '#'.$doc->id }}</a>
                        <span class="text-slate-500">{{ dc_date($doc->issue_date) }}</span>
                    </li>
                @empty
                    <li class="text-slate-500">Încă nu s-a generat niciun document.</li>
                @endforelse
            </ul>
        </div>

        <form method="POST" action="{{ route('recurring.destroy', $recurring) }}" onsubmit="return confirm('Ștergi abonamentul? Documentele deja emise rămân.')">
            @csrf @method('DELETE')
            <input type="hidden" name="return_page" value="{{ $returnPage }}">
            <button class="dc-btn-secondary w-full text-rose-700">Șterge abonamentul</button>
        </form>
    </div>
</div>
@endsection
