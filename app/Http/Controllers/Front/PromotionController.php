<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PromotionController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();

        $query = Promotion::query()
            ->where('status', 'active')
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', $now);
            })
            ->orderBy('start_at', 'desc');

        if ($type = $request->input('type')) {
            $query->where('bonus_type', $type);
        }

        if ($customerType = $request->input('customer_type')) {
            $query->where(function ($q) use ($customerType) {
                $q->where('customer_type', $customerType)
                  ->orWhere('customer_type', 'both');
            });
        }

        return response()->json(
            $query->paginate(12)
        );
    }

    public function show(string $slug, Request $request)
    {
        $promotion = Promotion::query()
            ->where('slug', $slug)
            ->firstOrFail();

        // Exemplu simplu: presupunem că avem pivot promotion_product
        $productsQuery = Product::query()
            ->select('products.*')
            ->join('promotion_product', 'products.id', '=', 'promotion_product.product_id')
            ->where('promotion_product.promotion_id', $promotion->id)
            ->with(['brand', 'mainCategory'])
            ->orderBy('products.name');

        $perPage = min((int) $request->input('per_page', 24), 100);
        $products = $productsQuery->paginate($perPage);

        return response()->json([
            'promotion' => $promotion,
            'products'  => $products,
        ]);
    }
}
