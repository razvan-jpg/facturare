<div class="space-y-6">
    <div class="dc-card p-4 space-y-3">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="font-semibold">Decizie de inseriere</h3>
                <p class="text-sm text-slate-600 mt-0.5">Se generează PDF doar pentru seriile <strong>active</strong> din anul ales.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('companies.series.decision', $company) }}" class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end" target="_blank">
            @csrf
            <div class="sm:col-span-2">
                <label class="dc-label">Persoana responsabilă (administrator) <span class="text-amber-700">*</span></label>
                <input type="text" name="responsible_name" value="{{ old('responsible_name', $company->seriesResponsibleName() ?? auth()->user()->name) }}" class="dc-input" required placeholder="Numele administratorului">
            </div>
            <div>
                <label class="dc-label">Calitate / funcție</label>
                <input type="text" name="responsible_role" value="{{ old('responsible_role', $company->seriesResponsibleRole()) }}" class="dc-input" placeholder="ex: Administrator">
            </div>
            <div>
                <label class="dc-label">Anul</label>
                <input type="number" name="year" value="{{ old('year', date('Y')) }}" min="2000" max="2100" class="dc-input" required>
            </div>
            <div class="sm:col-span-2 lg:col-span-4">
                <button class="dc-btn-primary">Generează decizia de inseriere</button>
            </div>
        </form>
    </div>

    <div class="dc-card overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 font-semibold">Serii documente</div>
        <p class="px-4 pt-3 text-sm text-slate-600">
            <strong>Primul nr. DateConta</strong> = de unde încep golurile libere (nu se reiau numere emise în alt soft).
            <strong>Următorul nr.</strong> = următorul de emis când nu există goluri.
        </p>
        <div class="overflow-x-auto">
            <table class="w-full dc-table">
                <thead>
                    <tr>
                        <th>Tip</th>
                        <th>Prefix</th>
                        <th>An</th>
                        <th>Primul nr. DateConta</th>
                        <th>Următorul nr.</th>
                        <th>Implicită</th>
                        <th>{{ __('Activă') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($seriesList as $series)
                    <tr>
                        <td>{{ $series->typeLabel() }}</td>
                        <td class="font-medium">{{ $series->prefix }}</td>
                        <td>{{ $series->year }}</td>
                        <td>
                            <form id="series-form-{{ $series->id }}" method="POST" action="{{ route('companies.series.update', [$company, $series]) }}">
                                @csrf @method('PUT')
                                <input type="hidden" name="active" value="0">
                                <input type="hidden" name="is_default" value="0">
                                <input type="number" min="1" name="first_number" value="{{ $series->first_number ?? $series->next_number }}" class="dc-input w-24" title="Primul număr folosit în DateConta">
                            </form>
                        </td>
                        <td>
                            <input form="series-form-{{ $series->id }}" type="number" min="1" name="next_number" value="{{ $series->next_number }}" class="dc-input w-24" title="Următorul număr de emis">
                        </td>
                        <td><input form="series-form-{{ $series->id }}" type="checkbox" name="is_default" value="1" @checked($series->is_default) class="rounded border-slate-300"></td>
                        <td><input form="series-form-{{ $series->id }}" type="checkbox" name="active" value="1" @checked($series->active) class="rounded border-slate-300"></td>
                        <td class="whitespace-nowrap">
                            <button form="series-form-{{ $series->id }}" class="dc-btn-secondary text-sm">{{ __('Salvează') }}</button>
                            <form method="POST" action="{{ route('companies.series.destroy', [$company, $series]) }}" class="inline" onsubmit="return confirm('Ștergi seria {{ $series->prefix }} / {{ $series->year }}?')">
                                @csrf @method('DELETE')
                                <button class="dc-btn-secondary text-sm text-rose-700 border-rose-200">{{ __('Șterge') }}</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <form method="POST" action="{{ route('companies.series.store', $company) }}" class="dc-card p-6 grid sm:grid-cols-2 gap-4">
        @csrf
        <h3 class="sm:col-span-2 font-semibold">Adaugă serie</h3>
        <div>
            <label class="dc-label">{{ __('Tip document') }}</label>
            <select name="type" class="dc-input" required>
                @foreach(\App\Models\DocumentSeries::TYPES as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="dc-label">Prefix / denumire serie</label>
            <input name="prefix" class="dc-input" required placeholder="ex: SM, FCT">
        </div>
        <div>
            <label class="dc-label">An</label>
            <input type="number" name="year" value="{{ date('Y') }}" class="dc-input" required>
        </div>
        <div>
            <label class="dc-label">Primul număr folosit în DateConta</label>
            <input type="number" name="first_number" id="series-first-number" value="1" min="1" class="dc-input" required>
            <p class="text-xs text-slate-500 mt-1">Golurile libere se caută de aici în sus (nu se reiau numere din alt soft).</p>
        </div>
        <div>
            <label class="dc-label">Următorul număr de emis</label>
            <input type="number" name="next_number" id="series-next-number" value="1" min="1" class="dc-input" required>
            <p class="text-xs text-slate-500 mt-1">Ex.: dacă în SmartBill ai ajuns la 305, pune primul=306 și următorul=306.</p>
        </div>
        <div class="sm:col-span-2"><label class="dc-label">{{ __('Descriere') }}</label><input name="description" class="dc-input"></div>
        <label class="flex items-center gap-2"><input type="checkbox" name="is_default" value="1" class="rounded border-slate-300"><span class="text-sm">Serie implicită</span></label>
        <div class="sm:col-span-2"><button class="dc-btn-primary">Adaugă seria</button></div>
    </form>
</div>
<script>
(() => {
    const first = document.getElementById('series-first-number');
    const next = document.getElementById('series-next-number');
    if (!first || !next) return;
    let nextTouched = false;
    next.addEventListener('input', () => { nextTouched = true; });
    first.addEventListener('input', () => {
        if (!nextTouched) next.value = first.value;
    });
})();
</script>
