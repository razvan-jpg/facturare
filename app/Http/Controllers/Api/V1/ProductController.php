<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ResolvesApiCompany;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ResolvesApiCompany;

    public function index(Request $request): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'products_view');
        $since = $request->query('since');
        $query = $company->products()->orderBy('name');
        if ($since) {
            $query->where('updated_at', '>', $since);
        }

        return response()->json([
            'data' => $query->get()->map(fn (Product $p) => $this->serialize($p))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'products_manage');
        $data = $this->validated($request, $company);
        $data['company_id'] = $company->id;
        $data['active'] = $request->boolean('active', true);
        $product = Product::create($data);

        return response()->json(['data' => $this->serialize($product)], 201);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'products_view');
        abort_unless($product->company_id === $company->id, 404);

        return response()->json(['data' => $this->serialize($product)]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'products_manage');
        abort_unless($product->company_id === $company->id, 404);
        $data = $this->validated($request, $company, true);
        if ($request->has('active')) {
            $data['active'] = $request->boolean('active');
        }
        $product->update($data);

        return response()->json(['data' => $this->serialize($product->fresh())]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $company = $this->authorizeAbility($request, 'products_manage');
        abort_unless($product->company_id === $company->id, 404);
        $id = $product->id;
        $product->delete();

        return response()->json(['deleted_id' => $id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, \App\Models\Company $company, bool $partial = false): array
    {
        $req = $partial ? 'sometimes' : 'required';
        $data = $request->validate([
            'name' => [$req, 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:50'],
            'unit' => ['nullable', 'string', 'max:32'],
            'price' => [$req, 'numeric', 'min:0'],
            'vat_rate' => [$req, 'numeric', 'min:0', 'max:100'],
            'type' => ['nullable', 'in:service,product'],
            'description' => ['nullable', 'string', 'max:2000'],
            'active' => ['nullable', 'boolean'],
            'client_uuid' => ['nullable', 'uuid'],
        ]);
        unset($data['client_uuid']);
        if (array_key_exists('unit', $data)) {
            $data['unit'] = app(\App\Services\MeasureUnitService::class)->ensure($company, $data['unit'] ?? null);
        }
        if (array_key_exists('price', $data)) {
            $data['price'] = round((float) $data['price'], 2);
        }
        if (array_key_exists('vat_rate', $data)) {
            $data['vat_rate'] = round((float) $data['vat_rate'], 2);
        }
        if (! $partial) {
            $data['type'] = $data['type'] ?? 'service';
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(Product $product): array
    {
        return [
            'id' => $product->id,
            'company_id' => $product->company_id,
            'name' => $product->name,
            'sku' => $product->sku,
            'unit' => $product->unit,
            'type' => $product->type,
            'price' => (float) $product->price,
            'vat_rate' => (float) $product->vat_rate,
            'description' => $product->description,
            'active' => (bool) $product->active,
            'updated_at' => optional($product->updated_at)?->toIso8601String(),
            'created_at' => optional($product->created_at)?->toIso8601String(),
        ];
    }
}
