@extends('layouts.app')
@section('heading', 'Utilizator nou')
@section('subheading', 'Creezi un cont nou sau inviți un utilizator existent')
@section('actions')
    <a href="{{ route('company-users.index') }}" class="dc-btn-secondary">{{ __('Înapoi') }}</a>
@endsection

@section('content')
<form method="POST" action="{{ route('company-users.store') }}" class="dc-card p-5 sm:p-6 max-w-xl space-y-4" id="company-user-create-form">
    @csrf
    <div>
        <label class="dc-label" for="email">Email</label>
        <input id="email" name="email" type="email" class="dc-input" value="{{ old('email') }}" required autofocus autocomplete="off">
        @error('email')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
        <p class="text-xs text-slate-500 mt-1" id="email-hint">
            După ce completezi emailul, verificăm dacă există deja în DateConta Facturare.
        </p>
    </div>

    <div id="existing-user-banner" class="hidden rounded-lg border border-teal-200 bg-teal-50 px-4 py-3 text-sm text-teal-950">
        <p class="font-semibold" id="existing-user-title">Utilizator existent</p>
        <p class="mt-1 text-teal-900/90" id="existing-user-detail"></p>
    </div>

    <div>
        <label class="dc-label" for="name">Nume <span class="font-normal text-slate-400" id="name-hint">(pentru cont nou)</span></label>
        <input id="name" name="name" type="text" class="dc-input" value="{{ old('name') }}">
        @error('name')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="dc-label" for="password">Parolă <span class="font-normal text-slate-400" id="password-hint">(pentru cont nou)</span></label>
        <input id="password" name="password" type="password" class="dc-input" autocomplete="new-password">
        @error('password')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="dc-label" for="password_confirmation">Confirmă parola</label>
        <input id="password_confirmation" name="password_confirmation" type="password" class="dc-input" autocomplete="new-password">
    </div>
    <p class="text-xs text-slate-500">
        La salvarea societăților și drepturilor, utilizatorul primește un email pe formatul aplicației
        (cont nou cu date de acces, sau invitație pe firmele tale).
    </p>
    <button type="submit" class="dc-btn-primary" id="submit-btn">{{ __('Continuă') }}</button>
</form>

<script>
(function () {
    var emailInput = document.getElementById('email');
    var nameInput = document.getElementById('name');
    var passwordInput = document.getElementById('password');
    var passwordConfirm = document.getElementById('password_confirmation');
    var banner = document.getElementById('existing-user-banner');
    var bannerTitle = document.getElementById('existing-user-title');
    var bannerDetail = document.getElementById('existing-user-detail');
    var emailHint = document.getElementById('email-hint');
    var nameHint = document.getElementById('name-hint');
    var passwordHint = document.getElementById('password-hint');
    var lookupUrl = @json(route('company-users.lookup'));
    var timer = null;
    var lastQueried = '';
    var nameBeforeLookup = nameInput.value || '';

    function setInviteMode(on, data) {
        passwordInput.disabled = on;
        passwordConfirm.disabled = on;
        if (on) {
            passwordInput.value = '';
            passwordConfirm.value = '';
            passwordInput.classList.add('bg-slate-100', 'cursor-not-allowed');
            passwordConfirm.classList.add('bg-slate-100', 'cursor-not-allowed');
            passwordInput.removeAttribute('required');
            passwordConfirm.removeAttribute('required');
            nameHint.textContent = '(din contul existent)';
            passwordHint.textContent = '(dezactivat — invitație)';
            nameInput.readOnly = true;
            nameInput.classList.add('bg-slate-100');
            if (data && data.name) {
                nameInput.value = data.name;
            }
            banner.classList.remove('hidden');
            if (data && data.is_self) {
                bannerTitle.textContent = 'Acesta este contul tău';
                bannerDetail.textContent = 'Nu te poți invita pe tine însuți. Folosește alt email.';
                banner.classList.remove('border-teal-200', 'bg-teal-50', 'text-teal-950');
                banner.classList.add('border-rose-200', 'bg-rose-50', 'text-rose-900');
            } else if (data && data.blocked_subuser) {
                bannerTitle.textContent = 'Subuser al altui cont';
                bannerDetail.textContent = 'Acest email aparține unui subuser creat de altcineva și nu poate fi invitat.';
                banner.classList.remove('border-teal-200', 'bg-teal-50', 'text-teal-950');
                banner.classList.add('border-rose-200', 'bg-rose-50', 'text-rose-900');
            } else if (data && data.is_admin) {
                bannerTitle.textContent = 'Administrator existent';
                bannerDetail.textContent = (data.name || data.email) + ' — va fi invitat cu drepturi complete de admin. Odată alocat pe o firmă, nu mai poate fi scos.';
                banner.classList.remove('border-rose-200', 'bg-rose-50', 'text-rose-900');
                banner.classList.add('border-amber-200', 'bg-amber-50', 'text-amber-950');
            } else if (data && data.already_yours) {
                bannerTitle.textContent = 'Deja în lista ta';
                bannerDetail.textContent = (data.name || data.email) + ' — poți continua pentru a actualiza societățile și drepturile.';
                banner.classList.remove('border-rose-200', 'bg-rose-50', 'text-rose-900', 'border-amber-200', 'bg-amber-50', 'text-amber-950');
                banner.classList.add('border-teal-200', 'bg-teal-50', 'text-teal-950');
            } else {
                bannerTitle.textContent = 'Utilizator existent';
                bannerDetail.textContent = (data.name || '') + (data.email ? ' · ' + data.email : '') + ' — va fi invitat pe societățile tale (fără parolă nouă).';
                banner.classList.remove('border-rose-200', 'bg-rose-50', 'text-rose-900', 'border-amber-200', 'bg-amber-50', 'text-amber-950');
                banner.classList.add('border-teal-200', 'bg-teal-50', 'text-teal-950');
            }
            emailHint.textContent = 'Email găsit în baza de date — invitație.';
        } else {
            passwordInput.classList.remove('bg-slate-100', 'cursor-not-allowed');
            passwordConfirm.classList.remove('bg-slate-100', 'cursor-not-allowed');
            nameInput.readOnly = false;
            nameInput.classList.remove('bg-slate-100');
            nameHint.textContent = '(pentru cont nou)';
            passwordHint.textContent = '(pentru cont nou)';
            banner.classList.add('hidden');
            banner.classList.remove('border-rose-200', 'bg-rose-50', 'text-rose-900', 'border-amber-200', 'bg-amber-50', 'text-amber-950');
            banner.classList.add('border-teal-200', 'bg-teal-50', 'text-teal-950');
            emailHint.textContent = 'Email liber — vei crea un cont nou cu parolă.';
            if (nameInput.value === '' || nameInput.dataset.fromLookup === '1') {
                nameInput.value = nameBeforeLookup;
            }
            nameInput.dataset.fromLookup = '';
        }
        if (on && data && data.name) {
            nameInput.dataset.fromLookup = '1';
        }
    }

    function lookup() {
        var email = (emailInput.value || '').trim().toLowerCase();
        if (!email || email.indexOf('@') < 1) {
            setInviteMode(false);
            emailHint.textContent = 'După ce completezi emailul, verificăm dacă există deja în DateConta Facturare.';
            return;
        }
        if (email === lastQueried) return;
        lastQueried = email;

        fetch(lookupUrl + '?email=' + encodeURIComponent(email), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if ((emailInput.value || '').trim().toLowerCase() !== email) return;
                if (data && data.exists) {
                    setInviteMode(true, data);
                } else {
                    setInviteMode(false);
                }
            })
            .catch(function () {
                lastQueried = '';
            });
    }

    emailInput.addEventListener('blur', lookup);
    emailInput.addEventListener('input', function () {
        lastQueried = '';
        clearTimeout(timer);
        timer = setTimeout(lookup, 450);
    });
    nameInput.addEventListener('focus', function () {
        if (!nameInput.readOnly) {
            nameBeforeLookup = nameInput.value;
        }
    });

    if (emailInput.value) {
        lookup();
    }
})();
</script>
@endsection
