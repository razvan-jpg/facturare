@php
    $langs = old('document_languages', $company->document_languages ?: ['ro']);
    if (! is_array($langs)) {
        $langs = ['ro'];
    }
    $options = config('document_languages', ['ro' => 'Română']);
@endphp
<form method="POST" action="{{ route('companies.update', $company) }}" class="dc-card p-6 space-y-4">
    @csrf @method('PUT')
    <input type="hidden" name="tab" value="limbi">
    <h2 class="text-lg font-semibold">{{ __('Limbi documente') }}</h2>
    <p class="text-sm text-slate-600">{{ __('Alege limbile în care poți emite documente PDF. Limba aleasă pe factură afectează doar acel document.') }}</p>
    <p class="text-xs text-slate-500">{{ __('Româna rămâne disponibilă mereu.') }} {{ __('Limbă interfață') }}: {{ __('Preferințe personale') }}.</p>
    <div class="grid sm:grid-cols-2 gap-3">
        @foreach($options as $code => $label)
            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2">
                <input type="checkbox" name="document_languages[]" value="{{ $code }}"
                       @checked(in_array($code, $langs, true) || $code === 'ro')
                       @if($code === 'ro') onclick="return false;" @endif
                       class="rounded border-slate-300">
                <span class="text-sm">{{ $label }}</span>
            </label>
        @endforeach
    </div>
    <button class="dc-btn-primary">{{ __('Salvează') }}</button>
</form>
