<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyBank;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompanyBankService
{
    public function sync(Company $company, array $banks): void
    {
        $normalized = [];
        $invoiceFlags = 0;

        foreach (array_values($banks) as $bankIndex => $bank) {
            $name = mb_strtoupper(trim((string) ($bank['name'] ?? '')), 'UTF-8');
            $accounts = array_values(array_filter($bank['accounts'] ?? [], function ($account) {
                return filled(trim((string) ($account['iban'] ?? '')));
            }));

            if ($name === '' && $accounts === []) {
                continue;
            }

            if ($name === '' && $accounts !== []) {
                $firstIban = (string) ($accounts[0]['iban'] ?? '');
                $name = $this->bankNameFromIban($firstIban) ?: '';
            }

            if ($name === '') {
                throw ValidationException::withMessages([
                    'banks' => 'Completează denumirea băncii pentru fiecare grup de conturi (sau un IBAN RO valid).',
                ]);
            }

            $name = mb_strtoupper($name, 'UTF-8');

            $rows = [];
            foreach ($accounts as $accountIndex => $account) {
                $iban = mb_strtoupper(preg_replace('/\s+/', '', (string) $account['iban']) ?? '', 'UTF-8');
                $show = filter_var($account['show_on_invoice'] ?? false, FILTER_VALIDATE_BOOLEAN)
                    || ($account['show_on_invoice'] ?? null) === '1'
                    || ($account['show_on_invoice'] ?? null) === 1
                    || ($account['show_on_invoice'] ?? null) === 'on';

                if ($show) {
                    $invoiceFlags++;
                }

                $rows[] = [
                    'iban' => $iban,
                    'currency' => strtoupper((string) ($account['currency'] ?? 'RON')) ?: 'RON',
                    'show_on_invoice' => $show,
                    'sort_order' => $accountIndex,
                ];
            }

            if ($rows === []) {
                throw ValidationException::withMessages([
                    'banks' => 'Banca „'.$name.'” trebuie să aibă cel puțin un IBAN.',
                ]);
            }

            $normalized[] = [
                'name' => $name,
                'sort_order' => $bankIndex,
                'accounts' => $rows,
            ];
        }

        if ($invoiceFlags > 3) {
            throw ValidationException::withMessages([
                'banks' => 'Poți bifa maxim 3 conturi IBAN pentru afișare pe factură.',
            ]);
        }

        DB::transaction(function () use ($company, $normalized) {
            foreach ($company->banks()->with('accounts')->get() as $bank) {
                $bank->accounts()->delete();
                $bank->delete();
            }

            $legacyIban = null;
            $legacyBank = null;

            foreach ($normalized as $bankData) {
                $bank = $company->banks()->create([
                    'name' => $bankData['name'],
                    'sort_order' => $bankData['sort_order'],
                ]);

                foreach ($bankData['accounts'] as $accountData) {
                    $account = $bank->accounts()->create($accountData);
                    if ($account->show_on_invoice && ! $legacyIban) {
                        $legacyIban = $account->iban;
                        $legacyBank = $bank->name;
                    }
                }
            }

            if (! $legacyIban && ! empty($normalized[0]['accounts'][0]['iban'])) {
                $legacyIban = $normalized[0]['accounts'][0]['iban'];
                $legacyBank = $normalized[0]['name'];
            }

            $company->forceFill([
                'iban' => $legacyIban,
                'bank_name' => $legacyBank,
            ])->save();
        });
    }

    public function bankNameFromIban(?string $iban): ?string
    {
        $clean = strtoupper(preg_replace('/\s+/', '', (string) $iban) ?? '');
        if (strlen($clean) < 8 || ! str_starts_with($clean, 'RO')) {
            return null;
        }

        $code = substr($clean, 4, 4);
        $map = config('romanian_banks', []);
        $name = $map[$code] ?? null;

        return $name ? mb_strtoupper($name, 'UTF-8') : null;
    }
}
