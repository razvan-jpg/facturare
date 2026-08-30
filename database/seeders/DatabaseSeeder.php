<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\AccessGate;
use App\Services\AnafClient;
use App\Services\DocumentService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $gate = app(AccessGate::class);
        $anaf = app(AnafClient::class);
        $documents = app(DocumentService::class);

        $admin = User::query()->updateOrCreate(
            ['email' => 'razvan.ivan@icloud.com'],
            [
                'name' => 'Răzvan Ivan',
                'password' => Hash::make('328434'),
                'email_verified_at' => now(),
            ]
        );
        $gate->applyOnRegister($admin);
        $admin->forceFill(['is_admin' => true, 'plan' => 'paid'])->save();

        $operator = config('dateconta.platform_operator');
        $lookup = $anaf->lookup($operator['cui']) ?: $operator;

        // Platform operator data is stored in config/footer; working company is user-created.
        // Seed a demo company for the admin so the app is usable immediately.
        if ($admin->companies()->count() === 0) {
            $company = $admin->ownedCompanies()->create([
                'name' => $lookup['name'] ?? $operator['name'],
                'cui' => $lookup['cui'] ?? $operator['cui'],
                'reg_com' => $lookup['reg_com'] ?? $operator['reg_com'],
                'address' => $lookup['address'] ?? $operator['address'],
                'city' => $lookup['city'] ?? $operator['city'],
                'county' => $lookup['county'] ?? $operator['county'],
                'country' => $operator['country'],
                'vat_payer' => $lookup['vat_payer'] ?? true,
                'default_vat_rate' => 21,
                'invoice_notes' => 'Vă mulțumim pentru colaborare!',
            ]);
            $company->users()->attach($admin->id, ['role' => 'owner']);
            $documents->ensureDefaultSeries($company);

            $company->clients()->create([
                'name' => 'Client Demo SRL',
                'type' => 'company',
                'cui' => '12345678',
                'address' => 'Str. Exemplu nr. 1',
                'city' => 'București',
                'county' => 'București',
                'email' => 'client@example.com',
            ]);

            $company->products()->create([
                'name' => 'Servicii consultanță',
                'unit' => 'ore',
                'type' => 'service',
                'price' => 250,
                'vat_rate' => 21,
            ]);
        }

        $this->call(MobileDemoSeeder::class);
    }
}
