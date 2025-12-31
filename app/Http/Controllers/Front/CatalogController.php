<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Promotion;
use Illuminate\Http\Request;
use App\Services\Pricing\PromotionEngine;
use App\Services\Pricing\PromotionPricingService;
use App\Models\Customer;

class CatalogController extends Controller
{
    public function __construct(
        protected PromotionEngine $promotionEngine
    ){}
    public function home()
    {
        return response()->json([
            'promotions' => Promotion::where('status', 'active')->take(5)->get(),
            'new_products' => Product::where('is_new', true)->take(8)->get(),
            'recommended' => Product::where('is_best_seller', true)->take(8)->get(),
        ]);
    }

    public function promotions()
    {
        return Promotion::where('status', 'active')->paginate(20);
    }

    public function categories()
    {
        return Category::whereNull('parent_id')->with('children')->where('is_published', true)->get();
    }

    public function category($slug, Request $request)
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $query = $category->products()->with('brand');

        if ($brandId = $request->get('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        if ($minPrice = $request->get('min_price')) {
            $query->where('list_price', '>=', $minPrice);
        }

        if ($maxPrice = $request->get('max_price')) {
            $query->where('list_price', '<=', $maxPrice);
        }

        if ($sort = $request->get('sort')) {
            if ($sort === 'price_asc') {
                $query->orderBy('list_price', 'asc');
            } elseif ($sort === 'price_desc') {
                $query->orderBy('list_price', 'desc');
            } elseif ($sort === 'newest') {
                $query->orderBy('id', 'desc');
            }
        }

        return response()->json([
            'category' => $category,
            'products' => $query->paginate(24),
        ]);
    }

    public function product(string $slug, Request $request, PromotionPricingService $pricing)
    {
        $customer = optional($request->user())->customer;

        $product = Product::query()
    ->where('slug', $slug)
    ->where('status', 'published')
    ->with([
        'brand',
        'mainCategory',
        'categories',
        'images',
        'variants',
        'attributes',
        'documents',
        'relatedProducts',
        'complementaryProducts',
    ])
    ->firstOrFail();


        // pricing principal produs
        $pricingData = $pricing->calculateProductPrice($product, $customer);

        $productArray = $product->toArray();
        $productArray['price']            = $pricingData['price'];
        $productArray['promoPrice']       = $pricingData['promo_price'];
        $productArray['hasDiscount']      = $pricingData['has_discount'];
        $productArray['discountPercent']  = $pricingData['discount_percent'];
        $productArray['appliedPromotion'] = $pricingData['applied_promotion'];

        // formatare minimală pentru relationships (imagini, documente etc.)
        $productArray['brand'] = $product->brand ? [
            'id'   => $product->brand->id,
            'name' => $product->brand->name,
            'slug' => $product->brand->slug ?? null,
        ] : null;

        $productArray['category'] = $product->mainCategory ? [
    'id'   => $product->mainCategory->id,
    'name' => $product->mainCategory->name,
    'slug' => $product->mainCategory->slug ?? null,
] : null;

        $productArray['images'] = $product->images?->map(function ($img) {
            return [
                'id'       => $img->id,
                'url'      => $img->url ?? $img->path ?? null,
                'is_main'  => (bool) ($img->is_main ?? false),
                'position' => $img->position ?? 0,
            ];
        })->values() ?? [];

        $productArray['documents'] = $product->documents?->map(function ($doc) {
            return [
                'id'       => $doc->id,
                'name'     => $doc->name,
                'type'     => $doc->type,
                'url'      => $doc->url ?? null,
                'is_locked'=> (bool) ($doc->is_locked ?? false),
            ];
        })->values() ?? [];

        // produse similare / complementare cu pricing
        $relatedProducts = $product->relatedProducts
            ? $product->relatedProducts->map(function (Product $p) use ($pricing, $customer) {
                return $pricing->formatProductForFrontend($p, $customer);
            })->values()
            : [];

        $complementaryProducts = $product->complementaryProducts
            ? $product->complementaryProducts->map(function (Product $p) use ($pricing, $customer) {
                return $pricing->formatProductForFrontend($p, $customer);
            })->values()
            : [];

        return response()->json([
            'product'                => $productArray,
            'related_products'       => $relatedProducts,
            'complementary_products' => $complementaryProducts,
        ]);
    }

    public function brands()
    {
        return Brand::where('is_published', true)->orderBy('sort_order')->get();
    }

    public function brand($slug)
    {
        $brand = Brand::where('slug', $slug)->firstOrFail();

        return response()->json([
            'brand'    => $brand,
            'products' => $brand->products()->paginate(24),
        ]);
    }
}
