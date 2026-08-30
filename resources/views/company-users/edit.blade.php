@extends('layouts.app')
@section('heading', $managedUser->name)
@section('subheading', $isInvited ? 'Invitat pe societățile tale — doar drepturi' : 'Drepturi pe societățile tale')
@section('actions')
    <a href="{{ route('company-users.index') }}" class="dc-btn-secondary">Înapoi la listă</a>
@endsection

@section('content')
@if(!empty($isAdminInvite))
    <div class="dc-card p-4 sm:p-5 mb-4 text-sm text-slate-700 border-l-4 border-amber-500">
        Cont de <strong>administrator</strong>: păstrează tot timpul comportamentul și drepturile complete de admin
        pe platformă. Odată alocat pe o societate a ta, <strong>nu mai poate fi scos</strong> de pe acea firmă
        (poți doar adăuga alte societăți).
    </div>
@elseif($isInvited)
    <div class="dc-card p-4 sm:p-5 mb-4 text-sm text-slate-700 border-l-4 border-teal-600">
        Acest utilizator are deja cont în DateConta Facturare. Îl inviți pe firmele tale;
        nu îi poți schimba parola și <strong>nu îi poți șterge contul</strong> — doar revoci accesul.
    </div>
@endif
@error('delete')
    <div class="dc-card p-4 mb-4 text-sm text-rose-700 border-l-4 border-rose-500">{{ $message }}</div>
@enderror

<form method="POST" action="{{ route('company-users.update', $managedUser) }}" class="space-y-5" id="company-user-form">
    @csrf
    @method('PUT')

    <div class="dc-card p-5 sm:p-6 max-w-2xl space-y-4">
        <h2 class="font-display text-lg text-slate-900">Cont</h2>
        @if($isCreatedSubuser)
            <div class="grid sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="dc-label" for="name">{{ __('Nume') }}</label>
                    <input id="name" name="name" type="text" class="dc-input" value="{{ old('name', $managedUser->name) }}" required>
                    @error('name')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="dc-label" for="email">Email</label>
                    <input id="email" name="email" type="email" class="dc-input" value="{{ old('email', $managedUser->email) }}" required>
                    @error('email')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="dc-label" for="password">Parolă nouă (opțional)</label>
                    <input id="password" name="password" type="password" class="dc-input" autocomplete="new-password">
                    @error('password')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="dc-label" for="password_confirmation">Confirmă parola</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="dc-input" autocomplete="new-password">
                </div>
            </div>
        @else
            <dl class="grid sm:grid-cols-2 gap-3 text-sm">
                <div>
                    <dt class="text-slate-500">{{ __('Nume') }}</dt>
                    <dd class="font-medium text-slate-900">{{ $managedUser->name }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Email</dt>
                    <dd class="font-medium text-slate-900">{{ $managedUser->email }}</dd>
                </div>
            </dl>
        @endif
    </div>

    <div class="space-y-4">
        <div>
            <h2 class="font-display text-xl text-slate-900">Societăți și drepturi</h2>
            <p class="text-sm text-slate-500 mt-1">
                @if(!empty($isAdminInvite))
                    Bifează societățile pe care inviți administratorul. Drepturile pe categorii nu se aplică —
                    adminul are acces complet. Societățile deja alocate nu mai pot fi debifate.
                @else
                    Bifează accesul pe firmă, apoi pe fiecare categorie: <strong>Vizualizare</strong> și
                    <strong>Creare / editare</strong>. Dacă niciuna nu e bifată pe o categorie, utilizatorul
                    <strong>nu are acces</strong> acolo. La salvare se trimite emailul de notificare (dacă e în așteptare).
                @endif
            </p>
        </div>

        @forelse($matrix as $row)
            @php
                $company = $row['company'];
                $cid = $company->id;
                $accessChecked = (bool) old('companies.'.$cid.'.access', $row['access']);
                $lockedAccess = !empty($isAdminInvite) && $row['access'];
                if ($lockedAccess) {
                    $accessChecked = true;
                }
                $oldPerms = old('companies.'.$cid.'.permissions');
            @endphp
            <div class="dc-card p-4 sm:p-5 company-perm-card" data-company="{{ $cid }}">
                <label class="flex items-center gap-2 mb-3">
                    <input type="checkbox"
                           name="companies[{{ $cid }}][access]"
                           value="1"
                           class="rounded border-slate-300 company-access-toggle"
                           @checked($accessChecked)
                           @if($lockedAccess) disabled @endif>
                    @if($lockedAccess)
                        <input type="hidden" name="companies[{{ $cid }}][access]" value="1">
                    @endif
                    <span class="font-semibold text-slate-900">{{ $company->name }}</span>
                    @if($company->cui)
                        <span class="text-xs text-slate-500 font-mono">{{ $company->cui }}</span>
                    @endif
                    @if($lockedAccess)
                        <span class="text-xs text-amber-700 font-semibold">blocat (admin)</span>
                    @endif
                </label>
                @if(!empty($isAdminInvite))
                    <p class="text-xs text-slate-500 pl-1">Acces complet de administrator pe această societate.</p>
                @else
                <div class="company-perm-grid overflow-x-auto pl-1 {{ $accessChecked ? '' : 'opacity-40 pointer-events-none' }}">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="py-2 pr-3 font-semibold">Categorie</th>
                                <th class="py-2 px-2 font-semibold text-center w-28">Vizualizare</th>
                                <th class="py-2 px-2 font-semibold text-center w-36">Creare / editare</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($categories as $catKey => $catLabel)
                            @php
                                $viewKey = $catKey.'_view';
                                $manageKey = $catKey.'_manage';
                                $viewChecked = is_array($oldPerms)
                                    ? in_array($viewKey, $oldPerms, true)
                                    : (bool) ($row['permissions'][$viewKey] ?? false);
                                $manageChecked = is_array($oldPerms)
                                    ? in_array($manageKey, $oldPerms, true)
                                    : (bool) ($row['permissions'][$manageKey] ?? false);
                            @endphp
                            <tr class="border-t border-slate-100">
                                <td class="py-2.5 pr-3 text-slate-800">{{ $catLabel }}</td>
                                <td class="py-2.5 px-2 text-center">
                                    <input type="checkbox"
                                           name="companies[{{ $cid }}][permissions][]"
                                           value="{{ $viewKey }}"
                                           class="rounded border-slate-300"
                                           @checked($viewChecked)
                                           aria-label="{{ $catLabel }} — vizualizare">
                                </td>
                                <td class="py-2.5 px-2 text-center">
                                    <input type="checkbox"
                                           name="companies[{{ $cid }}][permissions][]"
                                           value="{{ $manageKey }}"
                                           class="rounded border-slate-300"
                                           @checked($manageChecked)
                                           aria-label="{{ $catLabel }} — creare/editare">
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        @empty
            <div class="dc-card p-6 text-slate-500 text-sm">
                Nu ai societăți proprii. Adaugă o firmă înainte de a aloca drepturi.
            </div>
        @endforelse
    </div>

    <div class="flex flex-wrap gap-3 items-center">
        <button type="submit" class="dc-btn-primary">{{ __('Salvează') }}</button>
        @unless(!empty($isAdminInvite))
            <button type="submit" form="delete-user-form" class="dc-btn-secondary text-rose-700 border-rose-200 hover:border-rose-400"
                    onclick="return confirm(@json($isInvited ? 'Revoci accesul lui '.$managedUser->email.' la societățile tale? Contul lui rămâne activ.' : 'Ștergi utilizatorul '.$managedUser->email.'?'))">
                {{ $isInvited ? 'Revocă accesul' : 'Șterge utilizator' }}
            </button>
        @endunless
    </div>
</form>

@unless(!empty($isAdminInvite))
<form id="delete-user-form" method="POST" action="{{ route('company-users.destroy', $managedUser) }}" class="hidden">
    @csrf
    @method('DELETE')
</form>
@endunless

<script>
document.querySelectorAll('.company-access-toggle').forEach(function (toggle) {
    toggle.addEventListener('change', function () {
        var card = toggle.closest('.company-perm-card');
        var grid = card && card.querySelector('.company-perm-grid');
        if (!grid) return;
        if (toggle.checked) {
            grid.classList.remove('opacity-40', 'pointer-events-none');
        } else {
            grid.classList.add('opacity-40', 'pointer-events-none');
            grid.querySelectorAll('input[type=checkbox]').forEach(function (cb) { cb.checked = false; });
        }
    });
});
document.querySelectorAll('.company-perm-grid tbody tr').forEach(function (row) {
    var boxes = row.querySelectorAll('input[type=checkbox]');
    if (boxes.length !== 2) return;
    var viewCb = boxes[0];
    var manageCb = boxes[1];
    manageCb.addEventListener('change', function () {
        if (manageCb.checked) viewCb.checked = true;
    });
    viewCb.addEventListener('change', function () {
        if (!viewCb.checked) manageCb.checked = false;
    });
});
</script>
@endsection
