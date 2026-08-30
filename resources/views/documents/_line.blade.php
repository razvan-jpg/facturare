@php
    $d = $item['details'] ?? [];
    if (! is_array($d)) {
        $d = [];
    }
    // Doar câmpurile opționale e-Factura deschid panoul — nu și descrierea BT-154.
    $hasExtra = collect($d)->filter(fn ($v) => filled($v))->isNotEmpty();
@endphp
<tr class="inv-line-main" data-line-row>
    <td class="col-product">
        <div class="inv-product-wrap">
            <input type="text"
                   name="items[{{ $idx }}][name]"
                   value="{{ $item['name'] ?? '' }}"
                   class="dc-input item-name item-product-input"
                   placeholder="Produs / serviciu (obligatoriu)"
                   autocomplete="off"
                   data-autocomplete
                   aria-required="true">
            <input type="hidden" name="items[{{ $idx }}][product_id]" value="{{ $item['product_id'] ?? '' }}" class="product-id">
            <div class="inv-ac-list hidden" data-ac-list></div>
        </div>
    </td>
    <td class="col-description">
        <div class="inv-desc-wrap">
            <input type="text"
                   name="items[{{ $idx }}][description]"
                   value="{{ $item['description'] ?? '' }}"
                   class="dc-input item-description"
                   placeholder="Descriere (opțional)"
                   autocomplete="off"
                   data-autocomplete>
            <div class="inv-ac-list hidden" data-ac-list></div>
        </div>
    </td>
    <td class="col-unit">
        @include('partials.unit-options', [
            'selected' => $item['unit'] ?? 'buc',
            'name' => 'items['.$idx.'][unit]',
            'company' => $company ?? null,
            'listId' => 'dc-units-line-'.$idx,
        ])
    </td>
    <td class="col-qty"><input name="items[{{ $idx }}][quantity]" type="number" step="any" inputmode="decimal" value="{{ number_format((float) ($item['quantity'] ?? 1), 2, '.', '') }}" class="dc-input item-qty"></td>
    <td class="col-price"><input name="items[{{ $idx }}][unit_price]" type="number" step="any" inputmode="decimal" value="{{ number_format((float) ($item['unit_price'] ?? 0), 2, '.', '') }}" class="dc-input item-price"></td>
    <td class="col-vat">
        @include('partials.vat-rate-select', [
            'name' => 'items['.$idx.'][vat_rate]',
            'selected' => $item['vat_rate'] ?? $defaultVat,
            'default' => $defaultVat,
        ])
    </td>
    <td class="col-total"><span class="line-total">0,00</span></td>
    <td class="col-actions">
        <button type="button" class="toggle-details" title="Detalii opționale" aria-expanded="{{ $hasExtra ? 'true' : 'false' }}">{{ $hasExtra ? '▴' : '▾' }}</button>
        <button type="button" class="remove-line" title="{{ __('Șterge') }}">×</button>
    </td>
</tr>
<tr class="inv-line-details {{ $hasExtra ? '' : 'hidden' }}" data-line-details>
    <td colspan="8">
        <div class="inv-details-grid">
            <div class="inv-details-block">
                <div class="inv-details-title">Detalii produs</div>
                <div class="inv-details-fields">
                    <label>Identif. cumpărătorului art. (BT-156)
                        <input name="items[{{ $idx }}][details][buyer_item_id]" value="{{ $d['buyer_item_id'] ?? '' }}" class="dc-input">
                    </label>
                    <label>Identif. std. art. (BT-157)
                        <input name="items[{{ $idx }}][details][standard_item_id]" value="{{ $d['standard_item_id'] ?? '' }}" class="dc-input">
                    </label>
                    <label>Tip
                        <input name="items[{{ $idx }}][details][standard_item_scheme]" value="{{ $d['standard_item_scheme'] ?? '' }}" class="dc-input" placeholder="ex: SA">
                    </label>
                    <label>Cod NC (BT-158)
                        <input name="items[{{ $idx }}][details][nc_code]" value="{{ $d['nc_code'] ?? '' }}" class="dc-input">
                    </label>
                    <label>Cod CPV (BT-158)
                        <input name="items[{{ $idx }}][details][cpv_code]" value="{{ $d['cpv_code'] ?? '' }}" class="dc-input">
                    </label>
                    <label>Țara de origine (BT-159)
                        <input name="items[{{ $idx }}][details][origin_country]" value="{{ $d['origin_country'] ?? '' }}" class="dc-input" placeholder="RO" maxlength="2">
                    </label>
                </div>
            </div>
            <div class="inv-details-block">
                <div class="inv-details-title">Detalii linie</div>
                <div class="inv-details-fields">
                    <label class="span-2">Comentariu linie (BT-127)
                        <textarea name="items[{{ $idx }}][details][note]" rows="2" class="dc-input">{{ $d['note'] ?? '' }}</textarea>
                    </label>
                    <label>Identif. liniei (BT-128)
                        <input name="items[{{ $idx }}][details][sellers_item_id]" value="{{ $d['sellers_item_id'] ?? '' }}" class="dc-input">
                    </label>
                    <label>Tip
                        <input name="items[{{ $idx }}][details][sellers_item_scheme]" value="{{ $d['sellers_item_scheme'] ?? '' }}" class="dc-input">
                    </label>
                    <label>Referință comenzii (BT-132)
                        <input name="items[{{ $idx }}][details][order_reference]" value="{{ $d['order_reference'] ?? '' }}" class="dc-input">
                    </label>
                    <label>Referință contabilă cumpărător (BT-133)
                        <input name="items[{{ $idx }}][details][buyer_accounting_ref]" value="{{ $d['buyer_accounting_ref'] ?? '' }}" class="dc-input">
                    </label>
                    <label>Perioadă start (BT-134)
                        <input type="date" name="items[{{ $idx }}][details][period_start]" value="{{ $d['period_start'] ?? '' }}" class="dc-input">
                    </label>
                    <label>Perioadă end (BT-135)
                        <input type="date" name="items[{{ $idx }}][details][period_end]" value="{{ $d['period_end'] ?? '' }}" class="dc-input">
                    </label>
                </div>
            </div>
        </div>
    </td>
</tr>
