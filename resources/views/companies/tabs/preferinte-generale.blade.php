<form method="POST" action="{{ route('companies.update', $company) }}" class="dc-card p-6 space-y-4">
    @csrf @method('PUT')
    <input type="hidden" name="tab" value="preferinte-generale">
    <h2 class="text-lg font-semibold">{{ __('Preferințe generale') }}</h2>
    <p class="text-sm text-slate-600">Setări comune tuturor utilizatorilor societății.</p>
    <div class="space-y-3">
        <label class="flex items-center gap-2"><input type="checkbox" name="show_cui_on_docs" value="1" @checked($company->preference('show_cui_on_docs', true)) class="rounded border-slate-300"><span class="text-sm">Afișează CUI pe documente</span></label>
        <label class="flex items-center gap-2"><input type="checkbox" name="show_reg_com_on_docs" value="1" @checked($company->preference('show_reg_com_on_docs', true)) class="rounded border-slate-300"><span class="text-sm">Afișează Reg. Com. pe documente</span></label>
        <label class="flex items-center gap-2"><input type="checkbox" name="show_bank_on_docs" value="1" @checked($company->preference('show_bank_on_docs', true)) class="rounded border-slate-300"><span class="text-sm">Afișează conturile bancare pe documente</span></label>
        <label class="flex items-center gap-2"><input type="checkbox" name="show_product_code" value="1" @checked($company->preference('show_product_code')) class="rounded border-slate-300"><span class="text-sm">Folosește / afișează cod produs</span></label>
        <div class="max-w-xs pt-2">
            <label class="dc-label">Zile scadență implicite</label>
            <input type="number" min="0" max="365" name="default_due_days" value="{{ old('default_due_days', $company->preference('default_due_days', 15)) }}" class="dc-input">
        </div>
        <div class="max-w-xs pt-2">
            <label class="dc-label" for="documents_per_page">Documente pe pagină (liste)</label>
            <select id="documents_per_page" name="documents_per_page" class="dc-input">
                @foreach([10, 25, 50, 100] as $n)
                    <option value="{{ $n }}" @selected((int) old('documents_per_page', $company->documentsPerPage()) === $n)>{{ $n }}</option>
                @endforeach
            </select>
            <p class="text-xs text-slate-500 mt-1">Se aplică la facturi, proforme, avize, chitanțe, storno și note de creditare.</p>
        </div>
    </div>
    <button class="dc-btn-primary">{{ __('Salvează') }}</button>
</form>
