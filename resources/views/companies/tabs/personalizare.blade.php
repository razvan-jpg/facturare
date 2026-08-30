@php
    $templates = $company->availableInvoiceTemplates();
    $selected = old('invoice_template', $company->invoice_template ?: 'classic');
    $color = old('invoice_color', $company->invoice_color ?: '#0f4c5c');
@endphp
<form method="POST" action="{{ route('companies.update', $company) }}" enctype="multipart/form-data" class="space-y-6">
    @csrf @method('PUT')
    <input type="hidden" name="tab" value="personalizare">

    <div class="dc-card p-6 space-y-4">
        <h2 class="text-lg font-semibold">Personalizare documente</h2>
        <p class="text-sm text-slate-600">Logo, semnătură, ștampilă, culoare și macheta implicită a facturilor.</p>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="dc-label">Culoare factură</label>
                <input name="invoice_color" type="color" value="{{ $color }}" class="dc-input h-10 p-1">
            </div>
            <div class="sm:col-span-2">
                <label class="dc-label">Notă pe factură</label>
                <textarea name="invoice_notes" rows="3" class="dc-input" placeholder="Text afișat în subsolul facturii">{{ old('invoice_notes', $company->invoice_notes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="dc-card p-6 space-y-5">
        <div>
            <h3 class="font-semibold">Logo, semnătură și ștampilă</h3>
            <p class="text-sm text-slate-600 mt-1">Imagini JPEG/PNG (max. 2 MB). Apar pe factura PDF.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-5">
            @foreach([
                'logo' => [
                    'label' => 'Logo firmă',
                    'path' => $company->logo_path,
                    'hint' => 'Antet factură',
                    'scale' => 'logo_scale',
                    'kind' => 'logo',
                ],
                'signature' => [
                    'label' => 'Semnătură (imagine)',
                    'path' => $company->signature_path,
                    'hint' => 'Opțional — sau folosește textul de mai jos',
                    'scale' => 'signature_scale',
                    'kind' => 'signature',
                ],
                'stamp' => [
                    'label' => 'Ștampilă',
                    'path' => $company->stamp_path,
                    'hint' => 'Colțul din dreapta jos',
                    'scale' => 'stamp_scale',
                    'kind' => 'stamp',
                ],
            ] as $field => $meta)
                @php
                    $scaleField = $meta['scale'];
                    $scaleValue = old($scaleField, $company->brandingScaleKey($meta['kind']));
                    $scaleSteps = array_keys(\App\Models\Company::BRANDING_SCALES);
                    $scaleLabels = \App\Models\Company::BRANDING_SCALES;
                @endphp
                <div class="rounded-xl border border-slate-200 p-4 space-y-3"
                     x-data="{
                        steps: @js($scaleSteps),
                        labels: @js($scaleLabels),
                        scale: @js($scaleValue),
                        get idx() { const i = this.steps.indexOf(this.scale); return i < 0 ? this.steps.length - 1 : i; },
                        get label() { return this.labels[this.scale] || this.scale; },
                        dec() { if (this.idx > 0) this.scale = this.steps[this.idx - 1]; },
                        inc() { if (this.idx < this.steps.length - 1) this.scale = this.steps[this.idx + 1]; },
                     }">
                    <div class="text-sm font-semibold">{{ $meta['label'] }}</div>
                    <div class="h-24 rounded-lg bg-slate-50 border border-dashed border-slate-300 flex items-center justify-center overflow-hidden">
                        @if($meta['path'] && $company->brandingUrl($meta['path']))
                            <img src="{{ $company->brandingUrl($meta['path']) }}" alt="{{ $meta['label'] }}"
                                 class="max-h-16 max-w-full object-contain">
                        @else
                            <span class="text-xs text-slate-400">Nicio imagine</span>
                        @endif
                    </div>
                    <input type="file" name="{{ $field }}" accept="image/jpeg,image/png,image/webp,.jpg,.jpeg,.png" class="block w-full text-xs text-slate-600">
                    <div>
                        <div class="dc-label mb-1.5">Dimensiune pe factură</div>
                        <input type="hidden" name="{{ $scaleField }}" :value="scale">
                        <div class="flex items-center gap-2">
                            <button type="button" class="dc-btn-secondary px-3 py-1.5 text-lg font-bold leading-none min-w-[2.5rem]"
                                    @click="dec()" :disabled="idx <= 0" title="Micșorează">−</button>
                            <div class="flex-1 text-center rounded-md border border-slate-200 bg-white px-2 py-1.5 text-sm font-semibold text-slate-800"
                                 x-text="label"></div>
                            <button type="button" class="dc-btn-secondary px-3 py-1.5 text-lg font-bold leading-none min-w-[2.5rem]"
                                    @click="inc()" :disabled="idx >= steps.length - 1" title="Mărește">+</button>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">Doar pe imaginea din PDF (nu pe machetă). − / + : 25% … 200%, pas 25%.</p>
                    </div>
                    <p class="text-xs text-slate-500">{{ $meta['hint'] }}</p>
                    @if($meta['path'])
                        <label class="flex items-center gap-2 text-xs text-slate-600">
                            <input type="checkbox" name="remove_{{ $field }}" value="1" class="rounded border-slate-300">
                            Șterge imaginea actuală
                        </label>
                    @endif
                </div>
            @endforeach
        </div>

        @php
            $defaultSigText = \App\Models\Company::DEFAULT_SIGNATURE_TEXT;
            $sigTextValue = old('signature_text', $company->signature_text ?: $defaultSigText);
            $showSigText = old('show_signature_text', $company->show_signature_text ?? true);
        @endphp
        <div class="space-y-3 rounded-xl border border-slate-200 p-4">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="show_signature_text" value="1" class="mt-1 rounded border-slate-300" @checked($showSigText)>
                <span>
                    <span class="block text-sm font-semibold text-slate-900">Afișează textul pe factură (în loc de semnătură)</span>
                    <span class="block text-xs text-slate-500 mt-0.5">Apare jos pe factură, în stânga, pe 3 rânduri (dacă nu ai imagine de semnătură).</span>
                </span>
            </label>
            <div>
                <label class="dc-label">Text în loc de semnătură (câte un rând pe linie)</label>
                <textarea name="signature_text" rows="3" class="dc-input" maxlength="500" placeholder="Rând 1&#10;Rând 2&#10;Rând 3">{{ $sigTextValue }}</textarea>
            </div>
        </div>
    </div>

    @php($templateLocked = $company->invoiceTemplateLocked())
    <div class="dc-card p-6 space-y-4" @unless($templateLocked) x-data="{ tpl: @js(old('invoice_template', $selected)) }" @endunless>
        <div>
            <h3 class="font-semibold">{{ __('Machetă factură implicită') }}</h3>
            @if($templateLocked)
                <p class="text-sm text-slate-600 mt-1">{{ __('Pentru această societate macheta este fixă (DateConta) și nu poate fi schimbată.') }}</p>
            @else
                <p class="text-sm text-slate-600 mt-1">{{ __('Click pe o variantă, apoi apasă „Salvează personalizarea”. Culoarea selectată se aplică pe machetă.') }}</p>
            @endif
        </div>

        <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($templates as $key => $tpl)
                @php($isSelected = old('invoice_template', $selected) === $key || $templateLocked)
                @if($templateLocked)
                    <div class="relative block w-full rounded-xl border-2 border-amber-500 bg-amber-50/40 ring-1 ring-amber-400 p-3">
                        <input type="hidden" name="invoice_template" value="{{ $key }}">
                        <div class="aspect-[4/3] rounded-lg overflow-hidden border border-slate-200 bg-white mb-3 pointer-events-none">
                            @include('companies.partials.template-preview', ['key' => $key, 'color' => $color])
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="text-sm font-semibold text-slate-900">{{ $tpl['name'] }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ $tpl['description'] }}</div>
                            </div>
                            <span class="shrink-0 text-[10px] font-bold uppercase tracking-wide bg-amber-500 text-[#1a1205] px-2 py-0.5 rounded">{{ __('Activă') }}</span>
                        </div>
                    </div>
                @else
                    <label class="relative block w-full cursor-pointer rounded-xl border-2 p-3 transition {{ $isSelected ? 'border-amber-500 bg-amber-50/40 ring-1 ring-amber-400' : 'border-slate-200 hover:border-amber-300' }}"
                           :class="tpl === @js($key)
                               ? 'border-amber-500 bg-amber-50/40 ring-1 ring-amber-400'
                               : 'border-slate-200 hover:border-amber-300'">
                        <input type="radio"
                               name="invoice_template"
                               value="{{ $key }}"
                               class="absolute opacity-0 w-px h-px"
                               x-model="tpl"
                               @checked($isSelected)>
                        <div class="aspect-[4/3] rounded-lg overflow-hidden border border-slate-200 bg-white mb-3 pointer-events-none">
                            @include('companies.partials.template-preview', ['key' => $key, 'color' => $color])
                        </div>
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="text-sm font-semibold text-slate-900">{{ $tpl['name'] }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">{{ $tpl['description'] }}</div>
                            </div>
                            <span class="shrink-0 text-[10px] font-bold uppercase tracking-wide bg-amber-500 text-[#1a1205] px-2 py-0.5 rounded"
                                  @if(! $isSelected) style="display:none" @endif
                                  x-show="tpl === @js($key)">{{ __('Activă') }}</span>
                        </div>
                    </label>
                @endif
            @endforeach
        </div>
    </div>

    <button class="dc-btn-primary">Salvează personalizarea</button>
</form>
