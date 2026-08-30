<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\IosSubscriptionGate;
use Carbon\Carbon;
use Tests\TestCase;

class IosSubscriptionGateTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_free_period_grants_access_without_entitlement(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-09 12:00:00', 'Europe/Bucharest'));
        config([
            'dateconta.ios_subscription.free_until' => '2027-03-31',
            'dateconta.ios_subscription.trial_months_after_promo' => 1,
            'dateconta.ios_subscription.review_force_paywall_emails' => [],
            'app.timezone' => 'Europe/Bucharest',
        ]);

        $gate = new IosSubscriptionGate;
        $user = new User;

        $this->assertTrue($gate->hasIosAccess($user));
        $this->assertTrue($gate->statusPayload($user)['in_free_period']);
        $this->assertFalse($gate->statusPayload($user)['in_trial']);
    }

    public function test_existing_client_after_free_period_requires_entitlement(): void
    {
        Carbon::setTestNow(Carbon::parse('2027-04-02 12:00:00', 'Europe/Bucharest'));
        config([
            'dateconta.ios_subscription.free_until' => '2027-03-31',
            'dateconta.ios_subscription.trial_months_after_promo' => 1,
            'dateconta.ios_subscription.review_force_paywall_emails' => [],
            'app.timezone' => 'Europe/Bucharest',
        ]);

        $gate = new IosSubscriptionGate;
        $user = new User([
            'created_at' => Carbon::parse('2026-06-01 10:00:00', 'Europe/Bucharest'),
            'ios_subscription_status' => null,
            'ios_expires_at' => null,
        ]);

        $this->assertFalse($gate->isInIosTrial($user));
        $this->assertFalse($gate->hasIosAccess($user));

        $user->ios_subscription_status = 'active';
        $user->ios_expires_at = Carbon::parse('2027-05-01');
        $this->assertTrue($gate->hasIosAccess($user));
    }

    public function test_new_client_after_promo_gets_one_month_trial(): void
    {
        Carbon::setTestNow(Carbon::parse('2027-04-15 12:00:00', 'Europe/Bucharest'));
        config([
            'dateconta.ios_subscription.free_until' => '2027-03-31',
            'dateconta.ios_subscription.trial_months_after_promo' => 1,
            'dateconta.ios_subscription.review_force_paywall_emails' => [],
            'app.timezone' => 'Europe/Bucharest',
        ]);

        $gate = new IosSubscriptionGate;
        $user = new User([
            'created_at' => Carbon::parse('2027-04-01 09:00:00', 'Europe/Bucharest'),
            'ios_subscription_status' => null,
            'ios_expires_at' => null,
        ]);

        $this->assertTrue($gate->isInIosTrial($user));
        $this->assertTrue($gate->hasIosAccess($user));
        $this->assertTrue($gate->statusPayload($user)['in_trial']);
        $this->assertNotNull($gate->statusPayload($user)['trial_ends_at']);
    }

    public function test_new_client_trial_expires_then_requires_entitlement(): void
    {
        Carbon::setTestNow(Carbon::parse('2027-05-05 12:00:00', 'Europe/Bucharest'));
        config([
            'dateconta.ios_subscription.free_until' => '2027-03-31',
            'dateconta.ios_subscription.trial_months_after_promo' => 1,
            'dateconta.ios_subscription.review_force_paywall_emails' => [],
            'app.timezone' => 'Europe/Bucharest',
        ]);

        $gate = new IosSubscriptionGate;
        $user = new User([
            'created_at' => Carbon::parse('2027-04-01 09:00:00', 'Europe/Bucharest'),
            'ios_subscription_status' => null,
            'ios_expires_at' => null,
        ]);

        $this->assertFalse($gate->isInIosTrial($user));
        $this->assertFalse($gate->hasIosAccess($user));
    }

    public function test_force_paywall_ignores_free_period_when_expired(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-18 12:00:00', 'Europe/Bucharest'));
        config([
            'dateconta.ios_subscription.free_until' => '2027-03-31',
            'dateconta.ios_subscription.review_force_paywall_emails' => ['review-expired@dateconta.ro'],
            'app.timezone' => 'Europe/Bucharest',
        ]);

        $gate = new IosSubscriptionGate;
        $user = new User([
            'email' => 'review-expired@dateconta.ro',
            'ios_force_paywall' => true,
            'ios_subscription_status' => 'expired',
            'ios_expires_at' => Carbon::parse('2026-01-01'),
        ]);

        $this->assertTrue($gate->forcesPaywall($user));
        $this->assertFalse($gate->hasIosAccess($user));
        $payload = $gate->statusPayload($user);
        $this->assertTrue($payload['force_paywall']);
        $this->assertFalse($payload['in_free_period']);
        $this->assertFalse($payload['has_access']);
    }
}
