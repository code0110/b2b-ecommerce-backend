<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PromotionController extends Controller
{
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
            'is_exclusive'        => ['boolean'],
            'is_iterative'        => ['boolean'],
            'bonus_type'          => ['required', Rule::in(['free_item', 'discount_value', 'discount_percent'])],
            'min_cart_total'      => ['nullable', 'numeric'],
            'min_qty_per_product' => ['nullable', 'integer'],
            'customer_type'       => ['required', Rule::in(['b2b', 'b2c', 'both'])],
            'logged_in_only'      => ['boolean'],
            'customer_group_ids'  => ['array'],
            'customer_group_ids.*'=> ['integer'],
        ]);

        $promotion = Promotion::create($data);

        if (!empty($data['customer_group_ids'])) {
            $promotion->customerGroups()->sync($data['customer_group_ids']);
        }

        return response()->json($promotion->load('customerGroups'), 201);
    }

    public function show(Promotion $promotion)
    {
        return $promotion->load(['customerGroups', 'categories', 'brands', 'products']);
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
            'is_exclusive'        => ['boolean'],
            'is_iterative'        => ['boolean'],
            'bonus_type'          => ['sometimes', Rule::in(['free_item', 'discount_value', 'discount_percent'])],
            'min_cart_total'      => ['nullable', 'numeric'],
            'min_qty_per_product' => ['nullable', 'integer'],
            'customer_type'       => ['sometimes', Rule::in(['b2b', 'b2c', 'both'])],
            'logged_in_only'      => ['boolean'],
            'customer_group_ids'  => ['array'],
            'customer_group_ids.*'=> ['integer'],
        ]);

        $promotion->update($data);

        if (array_key_exists('customer_group_ids', $data)) {
            $promotion->customerGroups()->sync($data['customer_group_ids'] ?? []);
        }

        return response()->json($promotion->load('customerGroups'));
    }

    public function destroy(Promotion $promotion)
    {
        $promotion->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
