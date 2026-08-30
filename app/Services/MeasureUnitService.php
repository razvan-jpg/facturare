<?php

namespace App\Services;

use App\Models\Company;
use App\Models\MeasureUnit;
use App\Support\MeasureUnits;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class MeasureUnitService
{
    /** Asigură tabela + catalogul standard pentru firmă. */
    public function ensureSchemaAndSeed(Company $company): void
    {
        $this->ensureTable();
        $this->seedDefaults($company);
    }

    /**
     * Listă live pentru UI: nume afișat + cod UNECE.
     *
     * @return array{default:string,units:list<array{name:string,unece:?string}>,lookup:array<string,string>}
     */
    public function listForJs(Company $company): array
    {
        $this->ensureSchemaAndSeed($company);

        $units = MeasureUnit::query()
            ->where('company_id', $company->id)
            ->where('active', true)
            ->orderBy('name')
            ->get(['name', 'unece_code']);

        $lookup = MeasureUnits::lookupMap();
        foreach ($units as $u) {
            $lookup[mb_strtolower($u->name)] = $u->name;
            if ($u->unece_code) {
                $lookup[mb_strtolower($u->unece_code)] = $u->name;
            }
        }

        return [
            'default' => MeasureUnits::defaultName(),
            'units' => $units->map(fn (MeasureUnit $u) => [
                'name' => $u->name,
                'unece' => $u->unece_code,
            ])->values()->all(),
            'lookup' => $lookup,
        ];
    }

    /**
     * Creează U/M dacă lipsește; returnează numele de stocat pe linie/produs.
     * Actualizează unece_code când există corespondență cunoscută.
     */
    public function ensure(Company $company, ?string $raw): string
    {
        $this->ensureSchemaAndSeed($company);

        $name = MeasureUnits::canonicalName($raw);
        $unece = MeasureUnits::resolveUnece($raw) ?? MeasureUnits::resolveUnece($name);

        $unit = MeasureUnit::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => $name,
            ],
            [
                'unece_code' => $unece,
                'active' => true,
            ]
        );

        if ($unece && blank($unit->unece_code)) {
            $unit->update(['unece_code' => $unece]);
        }

        return $unit->name;
    }

    /**
     * Cod UNECE pentru e-Factura XML; actualizează catalogul dacă află maparea.
     */
    public function uneceForXml(Company $company, ?string $raw): string
    {
        $this->ensureSchemaAndSeed($company);

        $name = MeasureUnits::canonicalName($raw);
        $unit = MeasureUnit::query()
            ->where('company_id', $company->id)
            ->where('name', $name)
            ->first();

        if ($unit && filled($unit->unece_code)) {
            return strtoupper((string) $unit->unece_code);
        }

        $unece = MeasureUnits::resolveUnece($raw) ?? MeasureUnits::resolveUnece($name);
        if ($unece) {
            if ($unit) {
                $unit->update(['unece_code' => $unece]);
            } else {
                MeasureUnit::query()->firstOrCreate(
                    ['company_id' => $company->id, 'name' => $name],
                    ['unece_code' => $unece, 'active' => true]
                );
            }

            return $unece;
        }

        // Necunoscută → H87 în XML; păstrăm numele custom în catalog fără mapare falsă.
        if (! $unit) {
            MeasureUnit::query()->firstOrCreate(
                ['company_id' => $company->id, 'name' => $name],
                ['unece_code' => null, 'active' => true]
            );
        }

        return MeasureUnits::defaultCode();
    }

    /** @return Collection<int, MeasureUnit> */
    public function activeForCompany(Company $company): Collection
    {
        $this->ensureSchemaAndSeed($company);

        return MeasureUnit::query()
            ->where('company_id', $company->id)
            ->where('active', true)
            ->orderBy('name')
            ->get();
    }

    private function seedDefaults(Company $company): void
    {
        if (! Schema::hasTable('measure_units')) {
            return;
        }

        $exists = MeasureUnit::query()->where('company_id', $company->id)->exists();
        if ($exists) {
            return;
        }

        $now = now();
        $rows = [];
        foreach (MeasureUnits::definitions() as $code => $row) {
            $rows[] = [
                'company_id' => $company->id,
                'name' => $row['short'],
                'unece_code' => $code,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        MeasureUnit::query()->insert($rows);
    }

    private function ensureTable(): void
    {
        if (Schema::hasTable('measure_units')) {
            return;
        }

        Schema::create('measure_units', function ($table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 32);
            $table->string('unece_code', 10)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'name']);
        });
    }
}
