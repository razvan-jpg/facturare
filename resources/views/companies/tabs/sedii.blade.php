<div class="space-y-6">
    <div class="dc-card p-6">
        <h2 class="text-lg font-semibold mb-1">Sedii / puncte de lucru</h2>
        <p class="text-sm text-slate-600 mb-4">Adaugă sedii secundare, filiale sau puncte de lucru.</p>
        @if($branches->isEmpty())
            <p class="text-sm text-slate-500 mb-4">Nu există sedii secundare încă. Sediul principal e cel din tab-ul Generale.</p>
        @else
            <div class="overflow-x-auto mb-4">
                <table class="w-full dc-table">
                    <thead><tr><th>Denumire</th><th>{{ __('Adresă') }}</th><th>{{ __('Oraș') }}</th><th></th></tr></thead>
                    <tbody>
                    @foreach($branches as $branch)
                        <tr>
                            <td class="font-medium">
                                {{ $branch->name }}
                                @if($branch->is_main)<span class="text-xs text-teal-700 ml-1">principal</span>@endif
                            </td>
                            <td>{{ $branch->address }}</td>
                            <td>{{ $branch->city }}{{ $branch->county ? ', '.$branch->county : '' }}</td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('companies.branches.destroy', [$company, $branch]) }}" onsubmit="return confirm('Ștergi sediul?')">
                                    @csrf @method('DELETE')
                                    <button class="text-sm text-rose-700 hover:underline">{{ __('Șterge') }}</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <form method="POST" action="{{ route('companies.branches.store', $company) }}" class="dc-card p-6 grid sm:grid-cols-2 gap-4">
        @csrf
        <h3 class="sm:col-span-2 font-semibold">Adaugă sediu</h3>
        <div class="sm:col-span-2"><label class="dc-label">Denumire</label><input name="name" class="dc-input" required placeholder="ex: Punct de lucru Cluj"></div>
        <div class="sm:col-span-2"><label class="dc-label">{{ __('Adresă') }}</label><input name="address" class="dc-input"></div>
        <div><label class="dc-label">Localitate</label><input name="city" class="dc-input"></div>
        @include('partials.county-select')
        <div><label class="dc-label">{{ __('Telefon') }}</label><input name="phone" class="dc-input"></div>
        <label class="flex items-center gap-2 pt-6"><input type="checkbox" name="is_main" value="1" class="rounded border-slate-300"><span class="text-sm">Sediu principal</span></label>
        <div class="sm:col-span-2"><button class="dc-btn-primary">Adaugă sediul</button></div>
    </form>
</div>
