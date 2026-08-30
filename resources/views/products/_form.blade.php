@php $p = $product ?? null; @endphp
<div class="sm:col-span-2"><label class="dc-label">Denumire</label><input name="name" value="{{ old('name', $p->name ?? '') }}" class="dc-input" required></div>
<div><label class="dc-label">Cod</label><input name="sku" value="{{ old('sku', $p->sku ?? '') }}" class="dc-input"></div>
<div>
    <label class="dc-label">UM</label>
    @include('partials.unit-options', [
        'selected' => old('unit', $p->unit ?? 'buc'),
        'name' => 'unit',
        'company' => $company ?? null,
        'class' => '',
        'listId' => 'dc-units-product',
    ])
    <p class="text-xs text-slate-500 mt-1">Alege din listă sau scrie o U/M nouă — se salvează în catalogul firmei.</p>
</div>
<div><label class="dc-label">Tip</label>
<select name="type" class="dc-input">
<option value="service" @selected(old('type', $p->type ?? 'service')==='service')>{{ __('Serviciu') }}</option>
<option value="product" @selected(old('type', $p->type ?? '')==='product')>{{ __('Produs') }}</option>
</select></div>
<div><label class="dc-label">Preț (fără TVA)</label><input name="price" type="number" step="0.01" value="{{ number_format((float) old('price', $p->price ?? 0), 2, '.', '') }}" class="dc-input" required></div>
<div><label class="dc-label">TVA %</label><input name="vat_rate" type="number" step="0.01" value="{{ number_format((float) old('vat_rate', $p->vat_rate ?? ($company->default_vat_rate ?? 21)), 2, '.', '') }}" class="dc-input" required></div>
<div class="sm:col-span-2"><label class="dc-label">{{ __('Descriere') }}</label><textarea name="description" rows="2" class="dc-input">{{ old('description', $p->description ?? '') }}</textarea></div>
<div class="flex items-center gap-2"><input type="checkbox" name="active" value="1" @checked(old('active', $p->active ?? true)) class="rounded border-slate-300"><span class="text-sm">{{ __('Activ') }}</span></div>
