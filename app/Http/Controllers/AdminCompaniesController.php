<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\AccessGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminCompaniesController extends Controller
{
    /** Preset-uri rapide: cheie → etichetă + săptămâni sau luni. */
    private const PRESETS = [
        '1w' => ['label' => '1 săptămână', 'weeks' => 1],
        '2w' => ['label' => '2 săptămâni', 'weeks' => 2],
        '4w' => ['label' => '4 săptămâni', 'weeks' => 4],
        '1m' => ['label' => '1 lună', 'months' => 1],
        '3m' => ['label' => '3 luni', 'months' => 3],
        '6m' => ['label' => '6 luni', 'months' => 6],
        '1y' => ['label' => '1 an', 'months' => 12],
    ];

    public function index(Request $request, AccessGate $accessGate): View
    {
        $q = trim((string) $request->query('q', ''));

        $companies = Company::query()
            ->with([
                'owner:id,name,email,plan,access_until,trial_ends_at,is_admin,created_at',
                'referredByCompany:id,name,promo_code',
            ])
            ->withCount('referredCompanies')
            ->withCount([
                'documents as platform_invoices_count' => fn ($query) => $query
                    ->where('type', 'invoice')
                    ->where('status', 'issued'),
            ])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('name', 'like', '%'.$q.'%')
                        ->orWhere('cui', 'like', '%'.$q.'%')
                        ->orWhere('promo_code', 'like', '%'.$q.'%')
                        ->orWhereHas('owner', function ($owner) use ($q) {
                            $owner->where('name', 'like', '%'.$q.'%')
                                ->orWhere('email', 'like', '%'.$q.'%');
                        });
                });
            })
            ->orderByDesc('platform_invoices_count')
            ->orderByDesc('updated_at')
            ->paginate(40)
            ->withQueryString();

        foreach ($companies as $company) {
            $owner = $company->owner;
            $until = $owner ? $accessGate->effectiveAccessUntil($owner) : null;
            $company->setAttribute('access_until_effective', $until);
            $company->setAttribute(
                'access_days_remaining',
                $until ? max(0, (int) now()->startOfDay()->diffInDays($until->copy()->startOfDay(), false)) : null
            );
            $company->setAttribute(
                'access_label',
                $owner ? ($accessGate->accessLabel($owner) ?: ($owner->is_admin ? 'Admin' : '—')) : '—'
            );
        }

        return view('admin.companies', compact('companies', 'q'));
    }

    public function grantPromo(Request $request, Company $company, AccessGate $accessGate): RedirectResponse
    {
        $data = $request->validate([
            'preset' => ['nullable', Rule::in(array_keys(self::PRESETS))],
            'weeks' => ['nullable', 'integer', 'min:1', 'max:104'],
            'direction' => ['nullable', Rule::in(['add', 'sub'])],
        ]);

        $owner = $company->owner;
        if (! $owner) {
            return back()->with('status', 'Societatea nu are proprietar — perioada nu poate fi modificată.');
        }

        if ($owner->is_admin) {
            return back()->with('status', 'Contul administrator nu primește promoții (acces nelimitat).');
        }

        $preset = $data['preset'] ?? null;
        $weeks = isset($data['weeks']) ? (int) $data['weeks'] : null;
        $direction = $data['direction'] ?? 'add';

        if ($preset) {
            $cfg = self::PRESETS[$preset];
            if (isset($cfg['months'])) {
                $until = $accessGate->extendAccess($owner, months: (int) $cfg['months']);
            } else {
                $until = $accessGate->adjustAccessByWeeks($owner, (int) $cfg['weeks']);
            }
            $sign = '+';
            $label = $cfg['label'];
        } elseif ($weeks !== null) {
            $delta = $direction === 'sub' ? -$weeks : $weeks;
            $until = $accessGate->adjustAccessByWeeks($owner, $delta);
            $sign = $direction === 'sub' ? '−' : '+';
            $label = $weeks === 1 ? '1 săptămână' : $weeks.' săptămâni';
        } else {
            return back()->withErrors(['weeks' => 'Alege un preset sau un număr de săptămâni.']);
        }

        return back()->with(
            'status',
            'Perioadă '.$sign.$label.' pentru „'.$company->name.'” (cont '.$owner->email.'). Acces până la '.dc_date($until).'.'
        );
    }
}
