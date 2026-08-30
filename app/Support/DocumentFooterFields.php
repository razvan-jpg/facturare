<?php

namespace App\Support;

use App\Models\Company;
use App\Services\CardProcessors;
use Illuminate\Http\Request;

class DocumentFooterFields
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:10000'],
            'contract_number' => ['nullable', 'string', 'max:100'],
            'despatch_advice' => ['nullable', 'string', 'max:100'],
            'prepared_by' => ['nullable', 'string', 'max:255'],
            'prepared_by_cnp' => ['nullable', 'string', 'max:20'],
            'delegate_name' => ['nullable', 'string', 'max:255'],
            'delegate_id_card' => ['nullable', 'string', 'max:50'],
            'vehicle_reg' => ['nullable', 'string', 'max:50'],
            'auto_email_cc_address' => ['nullable', 'email', 'max:255'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function fromRequest(Request $request, array $data = [], ?Company $company = null): array
    {
        $company = $company ?? ($data['company'] ?? null);
        if (! $company instanceof Company && filled($data['company_id'] ?? null)) {
            $company = Company::query()->find($data['company_id']);
        }

        $allowCard = $request->boolean('allow_card_payment')
            && $company instanceof Company
            && app(CardProcessors::class)->anyActive($company);

        return array_merge($data, [
            'notes' => $data['notes'] ?? $request->input('notes'),
            'allow_card_payment' => $allowCard,
            'contract_number' => self::nullableString($request->input('contract_number')),
            'despatch_advice' => self::nullableString($request->input('despatch_advice')),
            'prepared_by' => self::nullableString($request->input('prepared_by')),
            'prepared_by_cnp' => self::nullableString($request->input('prepared_by_cnp')),
            'delegate_name' => self::nullableString($request->input('delegate_name')),
            'delegate_id_card' => self::nullableString($request->input('delegate_id_card')),
            'vehicle_reg' => self::nullableString($request->input('vehicle_reg')),
            'auto_email_client' => $request->boolean('auto_email_client'),
            'auto_email_cc' => $request->boolean('auto_email_cc'),
            'auto_email_cc_address' => self::nullableString($request->input('auto_email_cc_address')),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function persistable(array $data): array
    {
        return [
            'notes' => $data['notes'] ?? null,
            'allow_card_payment' => (bool) ($data['allow_card_payment'] ?? false),
            'contract_number' => $data['contract_number'] ?? null,
            'despatch_advice' => $data['despatch_advice'] ?? null,
            'prepared_by' => $data['prepared_by'] ?? null,
            'prepared_by_cnp' => $data['prepared_by_cnp'] ?? null,
            'delegate_name' => $data['delegate_name'] ?? null,
            'delegate_id_card' => $data['delegate_id_card'] ?? null,
            'vehicle_reg' => $data['vehicle_reg'] ?? null,
            'auto_email_client' => (bool) ($data['auto_email_client'] ?? false),
            'auto_email_cc' => (bool) ($data['auto_email_cc'] ?? false),
            'auto_email_cc_address' => $data['auto_email_cc_address'] ?? null,
        ];
    }

    private static function nullableString(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : null;

        return $value === '' || $value === null ? null : $value;
    }
}
