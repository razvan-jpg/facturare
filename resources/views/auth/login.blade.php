<x-guest-layout>
    <div class="dc-auth-heading">
        <p class="dc-auth-kicker">{{ __('Contul tău') }}</p>
        <h2 class="font-display dc-auth-title">{{ __('Autentificare') }}</h2>
        <p class="dc-auth-sub">{{ __('Intră în DateConta Facturare cu emailul și parola contului.') }}</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="dc-auth-form">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('Parolă')" />
            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-[var(--dc-teal)] shadow-sm focus:ring-[var(--dc-teal)]" name="remember">
                <span class="ms-2 text-sm text-slate-600">{{ __('Ține-mă minte') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-semibold text-[var(--dc-teal)] hover:text-[var(--dc-teal-dark)] underline-offset-2 hover:underline" href="{{ route('password.request') }}">
                    {{ __('Ai uitat parola?') }}
                </a>
            @endif
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a class="text-sm text-slate-600 hover:text-slate-900 underline-offset-2 hover:underline order-2 sm:order-1" href="{{ route('register') }}">
                {{ __('Nu ai cont? Creează unul') }}
            </a>
            <x-primary-button class="w-full sm:w-auto justify-center order-1 sm:order-2">
                {{ __('Intră în aplicație') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
