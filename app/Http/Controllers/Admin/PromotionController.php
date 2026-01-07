<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PromotionController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Promotion::class, 'promotion');
    }

    public function index()
    {
        return Promotion::with(['customerGroups', 'categories', 'brands'])->paginate(25);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => ['required', 'string', 'max:191'],
            'slug'                => ['required', 'string', 'max:191', 'unique:promotions,slug'],
            'short_description'   => ['nullable', 'string'],
            'description'         => ['nullable', 'string'],
            'hero_image'          => ['nullable', 'string'],
            'banner_image'        => ['nullable', 'string'],
            'mobile_image'        => ['nullable', 'string'],
            'start_at'            => ['nullable', 'date'],
            'end_at'              => ['nullable', 'date', 'after_or_equal:start_at'],
            'status'              => ['required', Rule::in(['draft', 'active', 'inactive'])],
            
            // New Fields
            'type'                => ['required', Rule::in(['standard', 'volume', 'bundle', 'shipping', 'special_price', 'gift'])],
            'value_type'          => ['required', Rule::in(['percent', 'fixed_amount', 'fixed_price'])],
            'value'               => ['numeric', 'min:0'],
            'min_cart_total'      => ['nullable', 'numeric', 'min:0'],
            'min_qty_per_product' => ['nullable', 'integer', 'min:0'],
            
            'settings'            => ['nullable', 'array'],
            'conditions'          => ['nullable', 'array'],
            
            'customer_type'       => ['required', Rule::in(['b2b', 'b2c', 'both'])],
            'logged_in_only'      => ['boolean'],
            
            'customer_group_ids'  => ['array'],
            'customer_group_ids.*'=> ['integer', 'exists:customer_groups,id'],

            'customer_ids'        => ['array'],
            'customer_ids.*'      => ['integer', 'exists:customers,id'],
            
            'category_ids'        => ['array'],
            'category_ids.*'      => ['integer', 'exists:categories,id'],

            'brand_ids'           => ['array'],
            'brand_ids.*'         => ['integer', 'exists:brands,id'],

            'product_ids'         => ['array'],
            'product_ids.*'       => ['integer', 'exists:products,id'],

            // Tiers for Volume Discounts
            'tiers'               => ['nullable', 'array'],
            'tiers.*.min_qty'     => ['required_with:tiers', 'integer', 'min:1'],
            'tiers.*.value'       => ['required_with:tiers', 'numeric', 'min:0'],
        ]);

        $promotion = Promotion::create($data);

        if (!empty($data['customer_group_ids'])) {
            $promotion->customerGroups()->sync($data['customer_group_ids']);
        }

        if (!empty($data['customer_ids'])) {
            $promotion->customers()->sync($data['customer_ids']);
        }

        if (!empty($data['category_ids'])) {
            $promotion->categories()->sync($data['category_ids']);
        }

        if (!empty($data['brand_ids'])) {
            $promotion->brands()->sync($data['brand_ids']);
        }

        if (!empty($data['product_ids'])) {
            $promotion->products()->sync($data['product_ids']);
        }

        // Handle Tiers
        if ($data['type'] === 'volume' && !empty($data['tiers'])) {
            foreach ($data['tiers'] as $tier) {
                $promotion->tiers()->create([
                    'min_qty' => $tier['min_qty'],
                    'value'   => $tier['value'],
                ]);
            }
        }

        return response()->json($promotion->load(['customerGroups', 'tiers']), 201);
    }

    public function show(Promotion $promotion)
    {
        return $promotion->load(['customerGroups', 'customers', 'categories', 'brands', 'products', 'tiers']);
    }

    public function update(Request $request, Promotion $promotion)
    {
        $data = $request->validate([
            'name'                => ['sometimes', 'string', 'max:191'],
            'slug'                => ['sometimes', 'string', 'max:191', Rule::unique('promotions', 'slug')->ignore($promotion->id)],
            'short_description'   => ['nullable', 'string'],
            'description'         => ['nullable', 'string'],
            'hero_image'          => ['nullable', 'string'],
            'banner_image'        => ['nullable', 'string'],
            'mobile_image'        => ['nullable', 'string'],
            'start_at'            => ['nullable', 'date'],
            'end_at'              => ['nullable', 'date', 'after_or_equal:start_at'],
            'status'              => ['sometimes', Rule::in(['draft', 'active', 'inactive'])],

            'type'                => ['sometimes', Rule::in(['standard', 'volume', 'bundle', 'shipping', 'special_price', 'gift'])],
            'value_type'          => ['sometimes', Rule::in(['percent', 'fixed_amount', 'fixed_price'])],
            'value'               => ['numeric', 'min:0'],
            'min_cart_total'      => ['nullable', 'numeric', 'min:0'],
            'min_qty_per_product' => ['nullable', 'integer', 'min:0'],
            
            'settings'            => ['nullable', 'array'],
            'conditions'          => ['nullable', 'array'],

            'customer_type'       => ['sometimes', Rule::in(['b2b', 'b2c', 'both'])],
            'logged_in_only'      => ['boolean'],
            
            'customer_group_ids'  => ['array'],
            'customer_group_ids.*'=> ['integer', 'exists:customer_groups,id'],

            'customer_ids'        => ['array'],
            'customer_ids.*'      => ['integer', 'exists:customers,id'],

            'category_ids'        => ['array'],
            'category_ids.*'      => ['integer', 'exists:categories,id'],

            'brand_ids'           => ['array'],
            'brand_ids.*'         => ['integer', 'exists:brands,id'],

            'product_ids'         => ['array'],
            'product_ids.*'       => ['integer', 'exists:products,id'],

            'tiers'               => ['nullable', 'array'],
            'tiers.*.min_qty'     => ['required_with:tiers', 'integer', 'min:1'],
            'tiers.*.value'       => ['required_with:tiers', 'numeric', 'min:0'],
        ]);

        $promotion->update($data);

        if (array_key_exists('customer_group_ids', $data)) {
            $promotion->customerGroups()->sync($data['customer_group_ids'] ?? []);
        }

        if (array_key_exists('customer_ids', $data)) {
            $promotion->customers()->sync($data['customer_ids'] ?? []);
        }

        if (array_key_exists('category_ids', $data)) {
            $promotion->categories()->sync($data['category_ids'] ?? []);
        }

        if (array_key_exists('brand_ids', $data)) {
            $promotion->brands()->sync($data['brand_ids'] ?? []);
        }

        if (array_key_exists('product_ids', $data)) {
            $promotion->products()->sync($data['product_ids'] ?? []);
        }

        // Sync Tiers (Delete old, create new) - simplest approach for now
        if (isset($data['tiers']) && $promotion->type === 'volume') {
            $promotion->tiers()->delete();
            foreach ($data['tiers'] as $tier) {
                $promotion->tiers()->create([
                    'min_qty' => $tier['min_qty'],
                    'value'   => $tier['value'],
                ]);
            }
        }

        return response()->json($promotion->load(['customerGroups', 'tiers']));
    }

    public function destroy(Promotion $promotion)
    {
        $promotion->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
