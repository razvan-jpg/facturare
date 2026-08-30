<x-guest-layout>
    <div class="dc-auth-heading">
        <p class="dc-auth-kicker">{{ __('Cont nou') }}</p>
        <h2 class="font-display dc-auth-title">{{ __('Creează cont') }}</h2>
        <p class="dc-auth-sub">{{ __('În câteva câmpuri ai acces la facturare multi-firmă.') }}</p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <p class="mb-4 text-sm text-slate-600 rounded-lg border border-teal-100 bg-teal-50/70 px-3 py-2">
            @if(now()->lte(\Illuminate\Support\Carbon::parse(config('dateconta.promo_free_until'))->endOfDay()))
                {!! __('Acces <strong>gratuit până la :date</strong>. După această dată, conturile noi primesc <strong>:months luni gratuite</strong>.', [
                    'date' => e(\Illuminate\Support\Carbon::parse(config('dateconta.promo_free_until'))->format('d.m.Y')),
                    'months' => (int) config('dateconta.trial_months_after_promo', 6),
                ]) !!}
            @else
                {!! __('Cont nou: <strong>:months luni gratuite</strong> de la înregistrare.', [
                    'months' => (int) config('dateconta.trial_months_after_promo', 6),
                ]) !!}
            @endif
        </p>

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Nume')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Parolă')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirmă parola')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Ai deja cont?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Înregistrează-te') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
