<?php

namespace App\Http\Controllers;

use App\Services\EuPlatescPaymentService;
use App\Services\MolliePaymentService;
use App\Services\NetopiaPaymentService;
use App\Services\PlatformSettings;
use App\Services\StripePaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class AdminIntegrationsController extends Controller
{
    /** @var list<string> */
    private array $processors = ['netopia', 'euplatesc', 'mollie', 'stripe'];

    public function show(
        string $processor,
        PlatformSettings $settings,
        NetopiaPaymentService $netopia,
        EuPlatescPaymentService $euplatesc,
        MolliePaymentService $mollie,
        StripePaymentService $stripe,
    ): View {
        abort_unless(in_array($processor, $this->processors, true), 404);

        return view('admin.integrari.show', [
            'processor' => $processor,
            'processors' => $this->processors,
            'settings' => $settings,
            'status' => [
                'netopia' => $netopia->isConfigured(),
                'euplatesc' => $euplatesc->isConfigured(),
                'mollie' => $mollie->isConfigured(),
                'stripe' => $stripe->isConfigured(),
            ],
            'netopiaStatus' => $netopia->configurationStatus(),
            'labels' => [
                'netopia' => 'NETOPIA Payments',
                'euplatesc' => 'Eu Plătesc',
                'mollie' => 'Mollie',
                'stripe' => 'Stripe',
            ],
        ]);
    }

    public function update(Request $request, string $processor, PlatformSettings $settings): RedirectResponse
    {
        abort_unless(in_array($processor, $this->processors, true), 404);

        return match ($processor) {
            'netopia' => $this->updateNetopia($request, $settings),
            'euplatesc' => $this->updateEuPlatesc($request, $settings),
            'mollie' => $this->updateMollie($request, $settings),
            'stripe' => $this->updateStripe($request, $settings),
            default => abort(404),
        };
    }

    private function updateNetopia(Request $request, PlatformSettings $settings): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'sandbox' => ['nullable', 'boolean'],
            'signature' => ['nullable', 'string', 'max:64'],
            'public_cer' => ['nullable', 'file', 'max:512'],
            'private_key' => ['nullable', 'file', 'max:512'],
        ]);

        $signature = trim((string) ($data['signature'] ?? ''));
        // Nu șterge semnătura existentă dacă câmpul e lăsat gol la re-salvare.
        if ($signature === '') {
            $signature = trim((string) ($settings->get('netopia.signature') ?: config('netopia.signature') ?: ''));
        }

        $settings->setMany([
            'netopia.enabled' => $request->boolean('enabled') ? '1' : '0',
            'netopia.sandbox' => $request->boolean('sandbox') ? '1' : '0',
            'netopia.signature' => $signature,
        ]);

        $dir = storage_path('app/netopia');
        if (! is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        if ($request->hasFile('public_cer')) {
            $request->file('public_cer')->move($dir, 'public.cer');
        }
        if ($request->hasFile('private_key')) {
            $request->file('private_key')->move($dir, 'private.key');
        }

        $settings->applyToConfig();

        return back()->with('status', 'Setările NETOPIA au fost salvate.');
    }

    private function updateEuPlatesc(Request $request, PlatformSettings $settings): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'sandbox' => ['nullable', 'boolean'],
            'mid' => ['nullable', 'string', 'max:64'],
            'key' => ['nullable', 'string', 'max:128'],
        ]);

        $pairs = [
            'euplatesc.enabled' => $request->boolean('enabled') ? '1' : '0',
            'euplatesc.sandbox' => $request->boolean('sandbox') ? '1' : '0',
            'euplatesc.mid' => trim((string) ($data['mid'] ?? '')),
        ];
        if (array_key_exists('key', $data) && filled($data['key'] ?? null)) {
            $pairs['euplatesc.key'] = trim((string) $data['key']);
        }
        $settings->setMany($pairs);
        $settings->applyToConfig();

        return back()->with('status', 'Setările Eu Plătesc au fost salvate.');
    }

    private function updateMollie(Request $request, PlatformSettings $settings): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'key' => ['nullable', 'string', 'max:128', Rule::when(
                filled($request->input('key')),
                ['regex:/^(test_|live_)[A-Za-z0-9]+$/']
            )],
        ]);

        $pairs = [
            'mollie.enabled' => $request->boolean('enabled') ? '1' : '0',
        ];
        if (array_key_exists('key', $data) && filled($data['key'] ?? null)) {
            $pairs['mollie.key'] = trim((string) $data['key']);
        }
        $settings->setMany($pairs);
        $settings->applyToConfig();

        return back()->with('status', 'Setările Mollie au fost salvate.');
    }

    private function updateStripe(Request $request, PlatformSettings $settings): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'key' => ['nullable', 'string', 'max:255', Rule::when(
                filled($request->input('key')),
                ['regex:/^pk_(test|live)_[A-Za-z0-9]+$/']
            )],
            'secret' => ['nullable', 'string', 'max:255', Rule::when(
                filled($request->input('secret')),
                ['regex:/^(sk|rk)_(test|live)_[A-Za-z0-9]+$/']
            )],
            'webhook_secret' => ['nullable', 'string', 'max:255', Rule::when(
                filled($request->input('webhook_secret')),
                ['regex:/^whsec_[A-Za-z0-9]+$/']
            )],
        ]);

        $pairs = [
            'stripe.enabled' => $request->boolean('enabled') ? '1' : '0',
        ];
        if (array_key_exists('key', $data) && filled($data['key'] ?? null)) {
            $pairs['stripe.key'] = trim((string) $data['key']);
        }
        if (array_key_exists('secret', $data) && filled($data['secret'] ?? null)) {
            $pairs['stripe.secret'] = trim((string) $data['secret']);
        }
        if (array_key_exists('webhook_secret', $data) && filled($data['webhook_secret'] ?? null)) {
            $pairs['stripe.webhook_secret'] = trim((string) $data['webhook_secret']);
        }
        $settings->setMany($pairs);
        $settings->applyToConfig();

        return back()->with('status', 'Setările Stripe au fost salvate.');
    }
}
