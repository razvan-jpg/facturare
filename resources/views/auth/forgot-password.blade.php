<x-guest-layout>
    <div class="dc-auth-heading">
        <p class="dc-auth-kicker">{{ __('Recuperare acces') }}</p>
        <h2 class="font-display dc-auth-title">{{ __('Parolă uitată') }}</h2>
        <p class="dc-auth-sub">
            {{ __('Introdu adresa de email a contului. Îți trimitem un link cu care poți alege o parolă nouă.') }}
        </p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="dc-auth-form">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <a class="text-sm text-slate-600 hover:text-slate-900 underline-offset-2 hover:underline order-2 sm:order-1" href="{{ route('login') }}">
                {{ __('← Înapoi la autentificare') }}
            </a>
            <x-primary-button class="w-full sm:w-auto justify-center order-1 sm:order-2">
                {{ __('Trimite link de resetare') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
