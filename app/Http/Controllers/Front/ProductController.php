<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Services\Pricing\PromotionPricingService;

class ProductController extends Controller
{
    protected $pricingService;

    public function __construct(PromotionPricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    public function index(Request $request)
    {
        $query = Product::query()
            ->where('status', 'published')
            ->with(['brand', 'mainCategory']);

        // Filters
        if ($request->has('on_sale') && $request->boolean('on_sale')) {
            // This is complex as sale depends on promotions, but for now we can check price_override
            // or we might need a scope. For simplicity, let's assume price_override < list_price
            $query->where(function($q) {
                 $q->whereNotNull('price_override')
                   ->whereColumn('price_override', '<', 'list_price');
            });
            // Or use the 'is_promo' flag if it existed, but usually it's dynamic.
            // Alternatively, we can rely on specific flags like 'is_best_seller' or 'is_new'
        }

        if ($request->has('is_new') && $request->boolean('is_new')) {
            $query->where('is_new', true);
        }

        if ($request->has('featured') && $request->boolean('featured')) {
             // If we don't have 'featured', maybe use 'is_best_seller'
             $query->where('is_best_seller', true);
        }

        if ($request->has('category_id')) {
            $query->where('main_category_id', $request->input('category_id'));
        }

        // Sorting
        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('list_price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('list_price', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $limit = $request->input('limit', 12);
        $products = $query->paginate($limit);

        // Format prices
        $customer = optional($request->user())->customer;
        $products->getCollection()->transform(function ($product) use ($customer) {
            return $this->pricingService->formatProductForFrontend($product, $customer);
        });

        return response()->json($products);
    }
}
