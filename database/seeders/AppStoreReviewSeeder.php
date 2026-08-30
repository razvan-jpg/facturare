<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\AccessGate;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Cont App Review: abonament iOS expirat + paywall forțat (ignoră promo până la 31.03.2027).
 *
 * Email: review-expired@dateconta.ro
 * Parolă: ReviewExpired1!
 */
class AppStoreReviewSeeder extends Seeder
{
    public const EMAIL = 'review-expired@dateconta.ro';

    public const PASSWORD = 'ReviewExpired1!';

    public function run(): void
    {
        $gate = app(AccessGate::class);

        $user = User::query()->withTrashed()->updateOrCreate(
            ['email' => self::EMAIL],
            [
                'name' => 'App Review Expired',
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
                'is_admin' => false,
                'ios_force_paywall' => true,
                'ios_subscription_status' => 'expired',
                'ios_expires_at' => Carbon::parse('2026-01-01', config('app.timezone'))->endOfDay(),
                'ios_product_id' => 'ro.dateconta.facturare.premium.monthly',
                'ios_environment' => 'Sandbox',
                'deleted_at' => null,
            ]
        );

        if ($user->trashed()) {
            $user->restore();
        }

        $gate->applyOnRegister($user);
        $user->forceFill([
            'plan' => 'paid',
            'access_until' => null,
            'trial_ends_at' => null,
            'ios_force_paywall' => true,
            'ios_subscription_status' => 'expired',
            'ios_expires_at' => Carbon::parse('2026-01-01', config('app.timezone'))->endOfDay(),
        ])->save();

        $this->command?->info('App Review expired account: '.self::EMAIL.' / '.self::PASSWORD);
    }
}
