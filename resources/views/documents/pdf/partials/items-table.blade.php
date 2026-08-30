@php($t = $labels ?? [])
<table class="items">
<thead>
<tr>
    <th>{{ $t['name'] ?? 'Denumire' }}</th>
    <th>{{ $t['unit'] ?? 'UM' }}</th>
    <th>{{ $t['qty'] ?? 'Cant.' }}</th>
    <th>{{ $t['price'] ?? 'Preț' }}</th>
    <th>{{ $t['vat'] ?? 'TVA' }}</th>
    <th>{{ $t['total'] ?? 'Total' }}</th>
</tr>
</thead>
<tbody>
@foreach($document->items as $item)
<tr>
    <td>{{ $item->name }}@if($item->description)<br><span style="color:#627d98;font-size:10px">{{ $item->description }}</span>@endif</td>
    <td>{{ \App\Support\MeasureUnits::short($item->unit) }}</td>
    <td>{{ number_format($item->quantity, 2, ',', '.') }}</td>
    <td>{{ number_format($item->unit_price, 2, ',', '.') }}</td>
    <td>{{ number_format($item->vat_rate, 2, ',', '.') }}%</td>
    <td>{{ number_format($item->line_total, 2, ',', '.') }}</td>
</tr>
@endforeach
</tbody>
</table>
