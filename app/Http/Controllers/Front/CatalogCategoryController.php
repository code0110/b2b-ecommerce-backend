<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\Pricing\PromotionPricingService;
use Illuminate\Http\Request;

class CatalogCategoryController extends Controller
{
    /**
     * Pagina de categorie:
     *  - /api/catalog/category/{slug}
     *  - folosită de /categorie/:slug în front
     *
     * Returnează:
     *  - category: info categorie
     *  - products: paginator cu produse + prețuri promo
     *  - available_filters (simplu – branduri și interval preț)
     */
    public function show(string $slug, Request $request, PromotionPricingService $pricing)
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $customer = optional($request->user())->customer;

        $query = Product::query()
            ->with(['brand', 'mainCategory'])
            ->where('status', 'published')
            ->where(function ($q) use ($category) {
                // produsele cu main_category_id = categoria
                $q->where('main_category_id', $category->id);

                // dacă ai relație many-to-many category_product:
                if (method_exists(Product::class, 'categories')) {
                    $q->orWhereHas('categories', function ($qq) use ($category) {
                        $qq->where('categories.id', $category->id);
                    });
                }
            });

        // Filtre simple din query string (adaptabile)
        if ($brandId = $request->query('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        if ($request->filled('min_price')) {
            $query->where(function ($q) use ($request) {
                $min = (float) $request->query('min_price');
                $q->where('list_price', '>=', $min)
                  ->orWhere('price_override', '>=', $min);
            });
        }

        if ($request->filled('max_price')) {
            $query->where(function ($q) use ($request) {
                $max = (float) $request->query('max_price');
                $q->where('list_price', '<=', $max)
                  ->orWhere('price_override', '<=', $max);
            });
        }

        if ($request->filled('in_stock')) {
            $inStock = (bool) $request->query('in_stock');
            if ($inStock) {
                $query->where('stock_qty', '>', 0);
            }
        }

        // Sortare (preț asc/desc, noi etc.)
        switch ($request->query('sort')) {
            case 'price_asc':
                $query->orderByRaw('COALESCE(price_override, list_price) asc');
                break;
            case 'price_desc':
                $query->orderByRaw('COALESCE(price_override, list_price) desc');
                break;
            case 'newest':
                $query->orderByDesc('created_at');
                break;
            default:
                // sortare implicită (după relevanță / ordine din categorie)
                $query->orderBy('sort_order')->orderBy('name');
        }

        $products = $query->paginate(24);

        // Aplicăm pricing promo pentru fiecare produs
        $products->getCollection()->transform(function (Product $product) use ($pricing, $customer) {
            return $pricing->formatProductForFrontend($product, $customer);
        });

        // Filtre disponibile (simplu: branduri + range preț)
        $availableBrands = Product::where('status', 'published')
            ->where('main_category_id', $category->id)
            ->whereNotNull('brand_id')
            ->with('brand')
            ->get()
            ->pluck('brand')
            ->filter()
            ->unique('id')
            ->values()
            ->map(fn ($brand) => [
                'id'   => $brand->id,
                'name' => $brand->name,
                'slug' => $brand->slug ?? null,
            ]);

        $priceStats = Product::where('status', 'published')
            ->where('main_category_id', $category->id)
            ->selectRaw('MIN(COALESCE(price_override, list_price)) as min_price')
            ->selectRaw('MAX(COALESCE(price_override, list_price)) as max_price')
            ->first();

        return response()->json([
            'category' => [
                'id'          => $category->id,
                'name'        => $category->name,
                'slug'        => $category->slug,
                'description' => $category->description ?? null,
            ],
            'products'  => $products,
            'filters'   => [
                'brands' => $availableBrands,
                'price'  => [
                    'min' => $priceStats?->min_price ? (float) $priceStats->min_price : null,
                    'max' => $priceStats?->max_price ? (float) $priceStats->max_price : null,
                ],
            ],
        ]);
    }
}
