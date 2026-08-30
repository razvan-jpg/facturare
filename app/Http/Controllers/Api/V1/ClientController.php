<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesApiCompany;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\AnafClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    use ResolvesApiCompany;

    public function index(Request $request): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'clients_view');
        $since = $request->query('since');

        $query = $company->clients()->orderBy('name');
        if ($since) {
            $query->where('updated_at', '>', $since);
        }

        return response()->json([
            'data' => $query->get()->map(fn (Client $c) => $this->serialize($c))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'clients_manage');
        $data = $this->validated($request);
        $data['company_id'] = $company->id;
        $client = Client::create($data);

        return response()->json(['data' => $this->serialize($client)], 201);
    }

    public function show(Request $request, Client $client): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'clients_view');
        abort_unless($client->company_id === $company->id, 404);

        return response()->json(['data' => $this->serialize($client)]);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'clients_manage');
        abort_unless($client->company_id === $company->id, 404);
        $client->update($this->validated($request, $client));

        return response()->json(['data' => $this->serialize($client->fresh())]);
    }

    public function destroy(Request $request, Client $client): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'clients_manage');
        abort_unless($client->company_id === $company->id, 404);
        $id = $client->id;
        $client->delete();

        return response()->json(['deleted_id' => $id]);
    }

    public function anafLookup(Request $request, AnafClient $anaf): JsonResponse
    {
        $this->authorizeAbility($request, 'clients_manage');
        $request->validate(['cui' => ['required', 'string']]);
        $data = $anaf->lookup($request->string('cui'));
        if (! $data) {
            return response()->json(['message' => 'Nu am găsit firma în ANAF.'], 404);
        }

        return response()->json(['data' => $data]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Client $client = null): array
    {
        $rawOpening = trim((string) $request->input('opening_balance', $client?->opening_balance ?? ''));
        if ($request->has('opening_balance')) {
            $request->merge([
                'opening_balance' => $rawOpening === '' ? 0 : str_replace(',', '.', $rawOpening),
            ]);
        }

        if ($request->has('penalty_percent')) {
            $rawPenalty = trim((string) $request->input('penalty_percent', ''));
            $request->merge([
                'penalty_percent' => $rawPenalty === '' ? null : str_replace(',', '.', $rawPenalty),
            ]);
        }
        if ($request->exists('penalty_billing_enabled')) {
            $request->merge([
                'penalty_billing_enabled' => $request->boolean('penalty_billing_enabled'),
            ]);
        }

        $data = $request->validate([
            'name' => [$client ? 'sometimes' : 'required', 'string', 'max:255'],
            'type' => [$client ? 'sometimes' : 'required', 'in:company,person'],
            'cui' => ['nullable', 'string', 'max:20'],
            'reg_com' => ['nullable', 'string', 'max:50'],
            'admin_last_name' => ['nullable', 'string', 'max:100'],
            'admin_first_name' => ['nullable', 'string', 'max:100'],
            'cnp' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'county' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'max:500'],
            'iban' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'opening_balance' => ['nullable', 'numeric'],
            'opening_balance_date' => ['nullable', 'date'],
            'penalty_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'penalty_billing_enabled' => ['sometimes', 'boolean'],
            'client_uuid' => ['nullable', 'uuid'],
        ]);

        unset($data['client_uuid']);

        if (array_key_exists('penalty_percent', $data)) {
            if ($data['penalty_percent'] === null || $data['penalty_percent'] === '') {
                $data['penalty_percent'] = null;
            } else {
                $data['penalty_percent'] = round((float) $data['penalty_percent'], 4);
                if ($data['penalty_percent'] <= 0) {
                    $data['penalty_percent'] = null;
                }
            }
        }
        if (array_key_exists('penalty_billing_enabled', $data)) {
            $data['penalty_billing_enabled'] = (bool) $data['penalty_billing_enabled'];
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Client $client): array
    {
        return [
            'id' => $client->id,
            'company_id' => $client->company_id,
            'name' => $client->name,
            'type' => $client->type,
            'cui' => $client->cui,
            'reg_com' => $client->reg_com,
            'admin_last_name' => $client->admin_last_name,
            'admin_first_name' => $client->admin_first_name,
            'cnp' => $client->cnp,
            'address' => $client->address,
            'city' => $client->city,
            'county' => $client->county,
            'country' => $client->country,
            'phone' => $client->phone,
            'email' => $client->email,
            'iban' => $client->iban,
            'bank_name' => $client->bank_name,
            'notes' => $client->notes,
            'opening_balance' => (float) $client->opening_balance,
            'opening_balance_date' => optional($client->opening_balance_date)?->toDateString(),
            'penalty_percent' => $client->penalty_percent !== null ? (float) $client->penalty_percent : null,
            'penalty_billing_enabled' => (bool) ($client->penalty_billing_enabled ?? false),
            'updated_at' => optional($client->updated_at)?->toIso8601String(),
            'created_at' => optional($client->created_at)?->toIso8601String(),
        ];
    }
}
