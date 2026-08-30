<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Șterge contul
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            Contul tău de autentificare va fi închis și nu te vei mai putea conecta.
            Datele societăților (facturi, clienți, produse etc.) <strong>rămân în baza de date</strong>
            pentru o eventuală salvare, export sau backup ulterior.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Șterge contul') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium text-gray-900">
                Confirmi închiderea contului?
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Nu te vei mai putea autentifica. Documentele și datele firmelor rămân stocate
                (nu sunt șterse definitiv). Introdu parola pentru confirmare.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Parolă" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="{{ __('Parolă') }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Anulează
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    Șterge contul
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
