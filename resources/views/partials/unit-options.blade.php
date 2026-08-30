@php
    /** @var \App\Models\Company|null $company */
    $company = $company ?? null;
    $selectedUnit = \App\Support\MeasureUnits::canonicalName($selected ?? null);
    $units = collect();
    if ($company) {
        $units = app(\App\Services\MeasureUnitService::class)->activeForCompany($company);
    }
    if ($units->isEmpty()) {
        foreach (\App\Support\MeasureUnits::definitions() as $code => $row) {
            $units->push((object) ['name' => $row['short'], 'unece_code' => $code]);
        }
    }
    $listId = $listId ?? ('dc-units-'.uniqid());
@endphp
<input type="text"
       name="{{ $name ?? 'unit' }}"
       value="{{ $selectedUnit }}"
       list="{{ $listId }}"
       class="dc-input {{ $class ?? 'item-unit' }}"
       autocomplete="off"
       maxlength="32"
       placeholder="ex: buc, kg, palet"
       title="Unitate de măsură — poți alege din listă sau scrie una nouă">
<datalist id="{{ $listId }}">
    @foreach($units as $u)
        <option value="{{ $u->name }}">@if($u->unece_code){{ $u->name }} ({{ $u->unece_code }})@else{{ $u->name }}@endif</option>
    @endforeach
</datalist>
