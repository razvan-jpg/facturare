<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class IosSubscriptionService
{
    public function __construct(
        private AppleJwsVerifier $jws,
        private IosSubscriptionGate $gate,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function verifyAndAttach(User $user, string $signedTransaction): array
    {
        $decoded = $this->jws->decodeAndVerify($signedTransaction);
        $tx = $decoded['payload'];

        $bundleId = (string) ($tx['bundleId'] ?? '');
        $expectedBundle = (string) config('dateconta.ios_subscription.bundle_id');
        if ($expectedBundle !== '' && $bundleId !== $expectedBundle) {
            throw new RuntimeException('Bundle ID invalid.');
        }

        $productId = (string) ($tx['productId'] ?? '');
        if (! $this->gate->isKnownProduct($productId)) {
            throw new RuntimeException('Produs de abonament necunoscut.');
        }

        $originalTx = (string) ($tx['originalTransactionId'] ?? $tx['transactionId'] ?? '');
        if ($originalTx === '') {
            throw new RuntimeException('Tranzacție fără originalTransactionId.');
        }

        // Un originalTransactionId nu poate fi legat de alt cont.
        $owner = User::query()
            ->where('ios_original_transaction_id', $originalTx)
            ->where('id', '!=', $user->id)
            ->first();
        if ($owner) {
            throw new RuntimeException('Acest abonament Apple este deja legat de alt cont.');
        }

        $expiresMs = $tx['expiresDate'] ?? null;
        $expiresAt = $expiresMs
            ? Carbon::createFromTimestampMs((int) $expiresMs)
            : null;

        $revocationMs = $tx['revocationDate'] ?? null;
        $status = 'active';
        if ($revocationMs) {
            $status = 'revoked';
        } elseif ($expiresAt && $expiresAt->isPast()) {
            $status = 'expired';
        }

        $user->forceFill([
            'ios_original_transaction_id' => $originalTx,
            'ios_product_id' => $productId,
            'ios_expires_at' => $expiresAt,
            'ios_subscription_status' => $status,
            'ios_environment' => (string) ($tx['environment'] ?? null),
        ])->save();

        return $this->gate->statusPayload($user->fresh());
    }

    public function handleServerNotification(string $signedPayload): void
    {
        $outer = $this->jws->decodeAndVerify($signedPayload);
        $payload = $outer['payload'];
        $data = $payload['data'] ?? [];
        if (! is_array($data)) {
            return;
        }

        $signedTx = $data['signedTransactionInfo'] ?? null;
        if (! is_string($signedTx) || $signedTx === '') {
            Log::info('ios.asn.no_transaction', ['type' => $payload['notificationType'] ?? null]);

            return;
        }

        $tx = $this->jws->decodeAndVerify($signedTx)['payload'];
        $originalTx = (string) ($tx['originalTransactionId'] ?? '');
        if ($originalTx === '') {
            return;
        }

        $user = User::query()->where('ios_original_transaction_id', $originalTx)->first();
        if (! $user) {
            Log::info('ios.asn.unknown_tx', ['original' => $originalTx]);

            return;
        }

        $type = (string) ($payload['notificationType'] ?? '');
        $subtype = (string) ($payload['subtype'] ?? '');

        $expiresMs = $tx['expiresDate'] ?? null;
        $expiresAt = $expiresMs ? Carbon::createFromTimestampMs((int) $expiresMs) : $user->ios_expires_at;

        $status = match ($type) {
            'EXPIRED', 'GRACE_PERIOD_EXPIRED' => 'expired',
            'REVOKE', 'REFUND' => 'revoked',
            'DID_FAIL_TO_RENEW' => 'billing_retry',
            'DID_CHANGE_RENEWAL_STATUS' => $subtype === 'AUTO_RENEW_DISABLED' ? ($user->ios_subscription_status ?: 'active') : 'active',
            default => ($expiresAt && $expiresAt->isPast() ? 'expired' : 'active'),
        };

        if ($type === 'DID_FAIL_TO_RENEW' && $subtype === 'GRACE_PERIOD') {
            $status = 'grace_period';
        }

        DB::transaction(function () use ($user, $tx, $expiresAt, $status) {
            $user->forceFill([
                'ios_product_id' => (string) ($tx['productId'] ?? $user->ios_product_id),
                'ios_expires_at' => $expiresAt,
                'ios_subscription_status' => $status,
                'ios_environment' => (string) ($tx['environment'] ?? $user->ios_environment),
            ])->save();
        });
    }
}
