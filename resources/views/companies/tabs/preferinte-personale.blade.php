<form method="POST" action="{{ route('companies.update', $company) }}" class="dc-card p-6 space-y-4">
    @csrf @method('PUT')
    <input type="hidden" name="tab" value="preferinte-personale">
    <h2 class="text-lg font-semibold">{{ __('Preferințe personale') }}</h2>
    <p class="text-sm text-slate-600">{{ __('Setări de lucru la nivel de utilizator.') }}</p>

    <div class="max-w-md space-y-2 rounded-xl border border-teal-200 bg-teal-50/60 p-4">
        <label class="dc-label" for="ui_locale">{{ __('Limbă interfață') }}</label>
        <p class="text-xs text-slate-600">{{ __('Alege limba în care vezi meniurile și ecranele. Nu afectează limba facturilor PDF.') }}</p>
        <select name="ui_locale" id="ui_locale" class="dc-input">
            @foreach(ui_locale_options() as $code => $label)
                <option value="{{ $code }}" @selected(old('ui_locale', auth()->user()->uiLocale()) === $code)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="dc-label">{{ __('Listă documente implicită') }}</label>
            <select name="personal_default_list" class="dc-input">
                <option value="issued" @selected($company->preference('personal_default_list', 'issued') === 'issued')>{{ __('Emise') }}</option>
                <option value="draft" @selected($company->preference('personal_default_list') === 'draft')>{{ __('Drafturi') }}</option>
                <option value="all" @selected($company->preference('personal_default_list') === 'all')>{{ __('Toate') }}</option>
            </select>
        </div>
        <label class="flex items-center gap-2 pt-6">
            <input type="checkbox" name="personal_show_drafts_first" value="1" @checked($company->preference('personal_show_drafts_first')) class="rounded border-slate-300">
            <span class="text-sm">{{ __('Afișează drafturile primele') }}</span>
        </label>
        <label class="flex items-center gap-2 sm:col-span-2">
            <input type="checkbox" name="personal_confirm_issue" value="1" @checked($company->preference('personal_confirm_issue', true)) class="rounded border-slate-300">
            <span class="text-sm">{{ __('Confirmare înainte de emiterea documentului') }}</span>
        </label>
    </div>
    <button class="dc-btn-primary">{{ __('Salvează') }}</button>
</form>
