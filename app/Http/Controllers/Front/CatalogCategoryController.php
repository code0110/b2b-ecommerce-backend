<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Attribute;
use App\Services\Pricing\PromotionPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
     *  - available_filters (branduri, interval preț, atribute)
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

        // Filtre simple din query string
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
            $inStock = filter_var($request->query('in_stock'), FILTER_VALIDATE_BOOLEAN);
            if ($inStock) {
                $query->where('stock_qty', '>', 0);
            }
        }

        // Filtrare după atribute
        // Format așteptat: attributes[slug] = value (string sau array)
        if ($request->filled('attributes') && is_array($request->input('attributes'))) {
            foreach ($request->input('attributes') as $attrSlug => $values) {
                if (empty($values)) continue;
                
                // Normalizăm valorile la array
                $valuesArray = is_array($values) ? $values : explode(',', $values);
                $valuesArray = array_filter($valuesArray, fn($v) => !is_null($v) && $v !== '');
                
                if (empty($valuesArray)) continue;

                // Găsim atributul după slug pentru a fi siguri de ID
                $attr = Attribute::where('slug', $attrSlug)->first();
                if (!$attr) continue;

                $query->whereHas('attributeValues', function ($q) use ($attr, $valuesArray) {
                    $q->where('attribute_id', $attr->id)
                      ->whereIn('value', $valuesArray);
                });
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
                // sortare implicită
                $query->orderBy('sort_order')->orderBy('name');
        }

        $products = $query->paginate(24);

        // Aplicăm pricing promo pentru fiecare produs
        $products->getCollection()->transform(function (Product $product) use ($pricing, $customer) {
            return $pricing->formatProductForFrontend($product, $customer);
        });

        // ---------------------------------------------------------
        // Construim filtrele disponibile (Faceted Search)
        // ---------------------------------------------------------
        
        // 1. Branduri disponibile în categorie
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

        // 2. Statistici preț (min/max)
        $priceStats = Product::where('status', 'published')
            ->where('main_category_id', $category->id)
            ->selectRaw('MIN(COALESCE(price_override, list_price)) as min_price')
            ->selectRaw('MAX(COALESCE(price_override, list_price)) as max_price')
            ->first();

        // 3. Atribute filtrabile și valorile lor existente
        $filterableAttributes = $category->attributes()
            ->where('is_filterable', true)
            ->get();
            
        $attributeFilters = [];

        foreach ($filterableAttributes as $attr) {
            // Obținem valorile distincte pentru acest atribut din produsele categoriei
            $values = DB::table('attribute_values')
                ->join('products', 'attribute_values.product_id', '=', 'products.id')
                ->where('attribute_values.attribute_id', $attr->id)
                ->where('products.status', 'published')
                ->where('products.main_category_id', $category->id) // TODO: recursive categories?
                ->select('attribute_values.value')
                ->distinct()
                ->orderBy('attribute_values.value')
                ->pluck('value');
            
            if ($values->isNotEmpty()) {
                $attributeFilters[] = [
                    'id'      => $attr->id,
                    'name'    => $attr->name,
                    'slug'    => $attr->slug,
                    'type'    => $attr->type,
                    'options' => $values
                ];
            }
        }

        return response()->json([
            'category' => [
                'id'          => $category->id,
                'name'        => $category->name,
                'slug'        => $category->slug,
                'description' => $category->description ?? null,
            ],
            'products'  => $products,
            'filters'   => [
                'brands'     => $availableBrands,
                'price'      => [
                    'min' => $priceStats?->min_price ? (float) $priceStats->min_price : null,
                    'max' => $priceStats?->max_price ? (float) $priceStats->max_price : null,
                ],
                'attributes' => $attributeFilters,
            ],
        ]);
    }
}
