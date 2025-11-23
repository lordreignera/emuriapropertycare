<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Models\ClientCustomProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductManagementController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index()
    {
        $products = Product::with(['creator', 'components', 'customProducts'])
            ->latest()
            ->paginate(25);

        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        $customizableProducts = Product::where('is_customizable', true)->count();
        $clientCustomProducts = ClientCustomProduct::count();

        return view('admin.products.index', compact(
            'products',
            'totalProducts',
            'activeProducts',
            'customizableProducts',
            'clientCustomProducts'
        ));
    }

    /**
     * Show the form for creating a new product.
     */
    public function create()
    {
        return view('admin.products.create');
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_code' => 'nullable|string|max:50|unique:products,product_code',
            'product_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => ['required', Rule::in([
                'maintenance', 'inspection', 'repair', 'emergency', 
                'preventive', 'subscription_package', 'custom'
            ])],
            'pricing_type' => ['required', Rule::in([
                'fixed', 'component_based', 'subscription', 'pay_per_use'
            ])],
            'base_price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'is_customizable' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        // Auto-generate product code if not provided
        if (empty($validated['product_code'])) {
            $validated['product_code'] = 'PROD-' . substr(time(), 0, 10);
            
            // Ensure uniqueness
            $counter = 1;
            while (Product::where('product_code', $validated['product_code'])->exists()) {
                $validated['product_code'] = 'PROD-' . substr(time(), 0, 10) . '-' . $counter;
                $counter++;
            }
        }

        $validated['created_by'] = Auth::id();
        $validated['is_active'] = $request->has('is_active');
        $validated['is_customizable'] = $request->has('is_customizable');

        $product = Product::create($validated);

        return redirect()
            ->route('admin.products.show', $product)
            ->with('success', 'Product created successfully! Now add components.');
    }

    /**
     * Display the specified product.
     */
    public function show(Product $product)
    {
        $product->load(['creator', 'components', 'customProducts.client', 'customProducts.property']);

        return view('admin.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit(Product $product)
    {
        $product->load('components');

        return view('admin.products.edit', compact('product'));
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'product_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'product_code')->ignore($product->id)
            ],
            'product_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => ['required', Rule::in([
                'maintenance', 'inspection', 'repair', 'emergency', 
                'preventive', 'subscription_package', 'custom'
            ])],
            'pricing_type' => ['required', Rule::in([
                'fixed', 'component_based', 'subscription', 'pay_per_use'
            ])],
            'base_price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'is_customizable' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['is_customizable'] = $request->has('is_customizable');

        $product->update($validated);

        return redirect()
            ->route('admin.products.show', $product)
            ->with('success', 'Product updated successfully!');
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product)
    {
        // Check if product has custom products
        if ($product->customProducts()->count() > 0) {
            return redirect()
                ->route('admin.products.index')
                ->with('error', 'Cannot delete product with existing custom products.');
        }

        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product deleted successfully!');
    }

    /**
     * Add a component to a product.
     */
    public function addComponent(Request $request, Product $product)
    {
        $validated = $request->validate([
            'component_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'calculation_type' => ['required', Rule::in([
                'fixed', 'multiply', 'add', 'percentage', 'hourly'
            ])],
            'parameter_name' => 'nullable|string|max:255',
            'parameter_value' => 'required|numeric|min:0',
            'unit_cost' => 'required|numeric|min:0',
            'sort_order' => 'nullable|integer',
            'is_required' => 'boolean',
            'is_customizable' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        $validated['product_id'] = $product->id;
        $validated['is_required'] = $request->has('is_required');
        $validated['is_customizable'] = $request->has('is_customizable');

        // Calculate cost based on type
        $calculated_cost = 0;
        switch ($validated['calculation_type']) {
            case 'fixed':
                $calculated_cost = $validated['unit_cost'];
                break;
            case 'multiply':
                $calculated_cost = $validated['parameter_value'] * $validated['unit_cost'];
                break;
            case 'hourly':
                $calculated_cost = $validated['parameter_value'] * $validated['unit_cost'];
                break;
            case 'percentage':
                $calculated_cost = ($validated['parameter_value'] / 100) * $validated['unit_cost'];
                break;
        }

        $validated['calculated_cost'] = $calculated_cost;

        ProductComponent::create($validated);

        return redirect()
            ->route('admin.products.show', $product)
            ->with('success', 'Component added successfully!');
    }

    /**
     * Recalculate all component costs for a product.
     */
    public function recalculateComponents(Product $product)
    {
        $product->recalculateComponents();

        return redirect()
            ->route('admin.products.show', $product)
            ->with('success', 'All component costs recalculated successfully!');
    }

    /**
     * Toggle product active status.
     */
    public function toggleStatus(Product $product)
    {
        $product->update(['is_active' => !$product->is_active]);

        $status = $product->is_active ? 'activated' : 'deactivated';

        return redirect()
            ->route('admin.products.index')
            ->with('success', "Product {$status} successfully!");
    }

    /**
     * Duplicate a product.
     */
    public function duplicate(Product $product)
    {
        DB::beginTransaction();

        try {
            $newProduct = $product->replicate();
            $newProduct->product_code = $product->product_code . '_COPY_' . time();
            $newProduct->product_name = $product->product_name . ' (Copy)';
            $newProduct->is_active = false;
            $newProduct->created_by = Auth::id();
            $newProduct->save();

            // Duplicate components
            foreach ($product->components as $component) {
                $newComponent = $component->replicate();
                $newComponent->product_id = $newProduct->id;
                $newComponent->save();
            }

            DB::commit();

            return redirect()
                ->route('admin.products.edit', $newProduct)
                ->with('success', 'Product duplicated successfully! Update details and activate when ready.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()
                ->route('admin.products.index')
                ->with('error', 'Failed to duplicate product: ' . $e->getMessage());
        }
    }
}
