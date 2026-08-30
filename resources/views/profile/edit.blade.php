<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl text-sm text-slate-600 space-y-2">
                    <p class="font-semibold text-slate-800">Ștergerea contului</p>
                    <p>
                        Conturile din DateConta Facturare <strong>nu se pot șterge din Contul meu</strong>
                        (nici utilizatorii principali, nici subuserii).
                    </p>
                    @if(auth()->user()->isSubUser())
                        <p>
                            Contul tău a fost creat de un alt utilizator. Poți actualiza numele, emailul și parola;
                            doar creatorul poate închide acest subuser din <strong>Setări → Utilizatori</strong>.
                        </p>
                    @else
                        <p>
                            Poți crea sau invita colaboratori pe firmele tale și poți șterge doar
                            <strong>subuserii pe care i-ai creat</strong>. Utilizatorii invitați (cu cont propriu)
                            pot fi doar deconectați de pe firmele tale, fără a le șterge contul.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
