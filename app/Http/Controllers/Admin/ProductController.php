<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->with(['mainCategory', 'brand']);

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('internal_code', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($categoryId = $request->get('category_id')) {
            $query->where('main_category_id', $categoryId);
        }

        if ($brandId = $request->get('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        return $query->paginate(25);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:191'],
            'slug'             => ['required', 'string', 'max:191', 'unique:products,slug'],
            'internal_code'    => ['required', 'string', 'max:100', 'unique:products,internal_code'],
            'barcode'          => ['nullable', 'string', 'max:100'],
            'erp_id'           => ['nullable', 'string', 'max:100'],
            'short_description'=> ['nullable', 'string'],
            'long_description' => ['nullable', 'string'],
            'main_category_id' => ['required', 'integer', 'exists:categories,id'],
            'brand_id'         => ['nullable', 'integer', 'exists:brands,id'],
            'status'           => ['required', Rule::in(['published', 'hidden'])],
            'sort_order'       => ['nullable', 'integer'],
            'list_price'       => ['required', 'numeric'],
            'rrp_price'        => ['nullable', 'numeric'],
            'vat_rate'         => ['nullable', 'numeric'],
            'price_override'   => ['nullable', 'numeric'],
            'stock_status'     => ['required', 'string', 'max:50'],
            'stock_qty'        => ['nullable', 'integer'],
            'supplier_stock_qty' => ['nullable', 'integer'],
            'lead_time_days'   => ['nullable', 'integer'],
            'is_new'           => ['boolean'],
            'is_promo'         => ['boolean'],
            'is_best_seller'   => ['boolean'],
        ]);

        $product = Product::create($data);

        return response()->json($product, 201);
    }

    public function show(Product $product)
    {
        return $product->load(['mainCategory', 'brand', 'images', 'variants', 'attributes']);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'name'             => ['sometimes', 'string', 'max:191'],
            'slug'             => ['sometimes', 'string', 'max:191', Rule::unique('products', 'slug')->ignore($product->id)],
            'internal_code'    => ['sometimes', 'string', 'max:100', Rule::unique('products', 'internal_code')->ignore($product->id)],
            'barcode'          => ['nullable', 'string', 'max:100'],
            'erp_id'           => ['nullable', 'string', 'max:100'],
            'short_description'=> ['nullable', 'string'],
            'long_description' => ['nullable', 'string'],
            'main_category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'brand_id'         => ['nullable', 'integer', 'exists:brands,id'],
            'status'           => ['sometimes', Rule::in(['published', 'hidden'])],
            'sort_order'       => ['nullable', 'integer'],
            'list_price'       => ['sometimes', 'numeric'],
            'rrp_price'        => ['nullable', 'numeric'],
            'vat_rate'         => ['nullable', 'numeric'],
            'price_override'   => ['nullable', 'numeric'],
            'stock_status'     => ['sometimes', 'string', 'max:50'],
            'stock_qty'        => ['nullable', 'integer'],
            'supplier_stock_qty' => ['nullable', 'integer'],
            'lead_time_days'   => ['nullable', 'integer'],
            'is_new'           => ['boolean'],
            'is_promo'         => ['boolean'],
            'is_best_seller'   => ['boolean'],
        ]);

        $product->update($data);

        return response()->json($product);
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
