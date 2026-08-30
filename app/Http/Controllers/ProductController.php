<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ChecksCompanyPermission;
use App\Models\Product;
use App\Services\CompanyContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    use ChecksCompanyPermission;

    public function index(CompanyContext $context): View
    {
        $company = $context->current();
        $this->authorizeCompanyAbility($company, 'products_view');
        $products = $company->products()->orderBy('name')->paginate(25);

        return view('products.index', compact('products', 'company'));
    }

    public function create(CompanyContext $context): View
    {
        $this->authorizeCompanyAbility($context->current(), 'products_manage');

        return view('products.create', ['company' => $context->current()]);
    }

    public function store(Request $request, CompanyContext $context): RedirectResponse
    {
        $this->authorizeCompanyAbility($context->current(), 'products_manage');
        $company = $context->current();
        $data = $this->validated($request, $company);
        $data['company_id'] = $company->id;
        $data['active'] = $request->boolean('active', true);
        Product::create($data);

        return redirect()->route('products.index')->with('status', 'Produs/serviciu adăugat.');
    }

    /** Creare rapidă din formular factură (JSON). */
    public function quickStore(Request $request, CompanyContext $context): JsonResponse
    {
        $company = $context->current();
        $this->authorizeCompanyAbility($company, 'products_manage');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:32'],
            'price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'type' => ['nullable', 'in:service,product'],
        ]);

        $product = Product::create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'unit' => app(\App\Services\MeasureUnitService::class)->ensure($company, $data['unit'] ?? null),
            'price' => round((float) $data['price'], 2),
            'vat_rate' => round((float) $data['vat_rate'], 2),
            'type' => $data['type'] ?? 'service',
            'active' => true,
        ]);

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'unit' => $product->unit,
                'price' => (float) $product->price,
                'vat_rate' => (float) $product->vat_rate,
            ],
        ], 201);
    }

    public function edit(Product $product, CompanyContext $context): View
    {
        abort_unless($product->company_id === $context->current()?->id, 403);
        $this->authorizeCompanyAbility($context->current(), 'products_manage');

        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product, CompanyContext $context): RedirectResponse
    {
        abort_unless($product->company_id === $context->current()?->id, 403);
        $this->authorizeCompanyAbility($context->current(), 'products_manage');
        $data = $this->validated($request, $context->current());
        $data['active'] = $request->boolean('active', true);
        $product->update($data);

        return redirect()->route('products.index')->with('status', 'Produs actualizat.');
    }

    public function destroy(Product $product, CompanyContext $context): RedirectResponse
    {
        abort_unless($product->company_id === $context->current()?->id, 403);
        $this->authorizeCompanyAbility($context->current(), 'products_manage');
        $product->delete();

        return redirect()->route('products.index')->with('status', 'Produs șters.');
    }

    private function validated(Request $request, \App\Models\Company $company): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:50'],
            'unit' => ['nullable', 'string', 'max:32'],
            'price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
            'type' => ['nullable', 'in:service,product'],
            'description' => ['nullable', 'string', 'max:2000'],
            'active' => ['nullable', 'boolean'],
        ]);
        $data['unit'] = app(\App\Services\MeasureUnitService::class)->ensure($company, $data['unit'] ?? null);
        $data['price'] = round((float) $data['price'], 2);
        $data['vat_rate'] = round((float) $data['vat_rate'], 2);
        $data['type'] = $data['type'] ?? 'service';

        return $data;
    }
}
