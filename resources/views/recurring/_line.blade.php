<tr class="inv-line-main" data-line-row>
    <td class="col-product">
        <div class="inv-product-wrap">
            <input type="text"
                   name="items[{{ $idx }}][name]"
                   value="{{ $item['name'] ?? '' }}"
                   class="dc-input item-name item-product-input dc-tpl-field"
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
                   class="dc-input item-description dc-tpl-field"
                   placeholder="Descriere (opțional) — poți folosi #luna# #an#"
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
            'listId' => 'dc-units-rec-'.$idx,
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
        <button type="button" class="remove-line" title="{{ __('Șterge') }}">×</button>
    </td>
</tr>
