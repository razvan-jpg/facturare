<?php

namespace App\Services;

use App\Models\Company;

/**
 * Procesatoare card active per firmă (plata documentelor clienților).
 */
class CardProcessors
{
    public function __construct(private CompanyIntegrations $integrations) {}

    /**
     * @return array<string, array{key:string,label:string,short:string}>
     */
    public function active(?Company $company = null): array
    {
        if (! $company) {
            return [];
        }

        return $this->integrations->active($company);
    }

    public function anyActive(?Company $company = null): bool
    {
        return $this->active($company) !== [];
    }

    public function isActive(string $processor, ?Company $company = null): bool
    {
        return isset($this->active($company)[$processor]);
    }

    /**
     * @return list<string>
     */
    public function keys(?Company $company = null): array
    {
        return array_keys($this->active($company));
    }
}
