<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Pricing\PromotionPricingService;
use App\Models\Customer;

class CatalogController extends Controller
{
    public function __construct(
        protected PromotionPricingService $pricingService
    ){}

    public function home(Request $request)
    {
        $customer = optional($request->user())->customer;

        $promotions = Promotion::where('status', 'active')->take(5)->get();
        
        $newProducts = Product::with('images')->where('is_new', true)->take(8)->get()
            ->map(fn($p) => $this->pricingService->formatProductForFrontend($p, $customer));

        $recommended = Product::with('images')->where('is_best_seller', true)->take(8)->get()
            ->map(fn($p) => $this->pricingService->formatProductForFrontend($p, $customer));

        return response()->json([
            'promotions' => $promotions,
            'new_products' => $newProducts,
            'recommended' => $recommended,
        ]);
    }

    public function promotions()
    {
        return Promotion::where('status', 'active')->paginate(20);
    }

<<<<<<< HEAD
    /**
     * List all products with filters (for /produse page)
     */
    public function products(Request $request)
    {
        $customer = optional($request->user())->customer;
        $priceMode = $request->get('price_mode', 'net');

        $query = Product::where('status', 'published')
            ->with(['brand', 'images']);

        if ($brandId = $request->get('brand_id')) {
            $query->where('brand_id', $brandId);
        }
        
        // Support array of brands
        if ($brands = $request->get('brands')) {
             if (is_array($brands)) {
                 $query->whereIn('brand_id', $brands);
             }
        }

        // Helper expression for price
        $rawPriceSql = 'COALESCE(products.price_override, products.list_price)';
        $grossPriceSql = "
            (CASE 
                WHEN products.vat_included = 1 THEN {$rawPriceSql}
                ELSE {$rawPriceSql} * (1 + COALESCE(products.vat_rate, 0))
            END)
        ";
        $priceSql = ($priceMode === 'gross') ? $grossPriceSql : $rawPriceSql;

        if ($minPrice = $request->get('min_price') ?? $request->get('price_min')) {
            $query->whereRaw("{$priceSql} >= ?", [$minPrice]);
        }

        if ($maxPrice = $request->get('max_price') ?? $request->get('price_max')) {
            $query->whereRaw("{$priceSql} <= ?", [$maxPrice]);
        }

        if ($sort = $request->get('sort')) {
            if ($sort === 'price_asc') {
                $query->orderByRaw("{$priceSql} asc");
            } elseif ($sort === 'price_desc') {
                $query->orderByRaw("{$priceSql} desc");
            } elseif ($sort === 'newest') {
                $query->orderBy('id', 'desc');
            }
        } else {
            $query->orderBy('id', 'desc');
        }

        $paginator = $query->paginate(24);

        $paginator->getCollection()->transform(function ($product) use ($customer) {
            return $this->pricingService->formatProductForFrontend($product, $customer);
        });

        // Calculate min/max for facets
        $minMax = Product::where('status', 'published')
             ->selectRaw("MIN({$priceSql}) as min_price, MAX({$priceSql}) as max_price")
             ->first();

        // Aggregations for filters
        $filters = [
            'brands' => Brand::whereHas('products', function($q) {
                $q->where('status', 'published');
            })->orderBy('name')->get(['id', 'name']),
            
            'price' => [
                'min' => $minMax->min_price ?? 0,
                'max' => $minMax->max_price ?? 0,
            ]
        ];

        return response()->json([
            'category' => ['name' => 'Toate produsele', 'description' => 'Catalog complet de produse'],
            'products' => $paginator,
            'filters' => $filters
        ]);
    }

    public function categories(Request $request)
    {
=======
    public function categories(Request $request)
    {
>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76
        $query = Category::whereNull('parent_id')
            ->with('children')
            ->where('is_published', true)
            ->orderBy('sort_order');

        if ($request->has('limit')) {
            $query->limit($request->input('limit'));
        }

        return $query->get();
    }

    public function category($slug, Request $request)
    {
        $customer = optional($request->user())->customer;
        $category = Category::where('slug', $slug)->firstOrFail();
        $priceMode = $request->get('price_mode', 'net');

        $query = $category->products()->with(['brand', 'images']);

        if ($brandId = $request->get('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        // Helper expression for price
        $rawPriceSql = 'COALESCE(products.price_override, products.list_price)';
        $grossPriceSql = "
            (CASE 
                WHEN products.vat_included = 1 THEN {$rawPriceSql}
                ELSE {$rawPriceSql} * (1 + COALESCE(products.vat_rate, 0))
            END)
        ";
        $priceSql = ($priceMode === 'gross') ? $grossPriceSql : $rawPriceSql;

        if ($minPrice = $request->get('min_price')) {
            $query->whereRaw("{$priceSql} >= ?", [$minPrice]);
        }

        if ($maxPrice = $request->get('max_price')) {
            $query->whereRaw("{$priceSql} <= ?", [$maxPrice]);
        }

        if ($search = $request->get('search')) {
            $query->where(function($q) use ($search) {
                $q->where('product_name', 'like', "%{$search}%")
                  ->orWhere('internal_code', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($sort = $request->get('sort')) {
            if ($sort === 'price_asc') {
                $query->orderByRaw("{$priceSql} asc");
            } elseif ($sort === 'price_desc') {
                $query->orderByRaw("{$priceSql} desc");
            } elseif ($sort === 'newest') {
                $query->orderBy('id', 'desc');
            }
        }

        $paginator = $query->paginate(24);

        // Transform items using pricing service
        $paginator->getCollection()->transform(function ($product) use ($customer) {
            return $this->pricingService->formatProductForFrontend($product, $customer);
        });

        // Calculate min/max for facets in this category
        $minMax = $category->products()
             ->where('status', 'published')
             ->selectRaw("MIN({$priceSql}) as min_price, MAX({$priceSql}) as max_price")
             ->first();

        // Aggregations for filters
        $filters = [
            'brands' => Brand::whereHas('products', function($q) use ($category) {
                $q->where('status', 'published')
                  ->whereHas('categories', function($q2) use ($category) {
                      $q2->where('categories.id', $category->id);
                  });
            })->orderBy('name')->get(['id', 'name']),
            
            'price' => [
                'min' => $minMax->min_price ?? 0,
                'max' => $minMax->max_price ?? 0,
            ]
        ];

        return response()->json([
            'category' => $category,
            'products' => $paginator,
            'filters'  => $filters
        ]);
    }

    public function product(string $slug, Request $request)
    {
        $customer = optional($request->user())->customer;

        // 1. Încercăm să găsim produsul după slug
        $product = Product::query()
<<<<<<< HEAD
            ->where('slug', $slug)
            ->where('status', 'published')
            ->with([
                'brand',
                'mainCategory',
                'categories',
                'images',
                'variants.attributes.attribute', // Load variant attributes
                'variants.units', // Load variant units
                'units', // Load product units
                'attributeValues.attribute',
                'documents',
                'relatedProducts.relatedProduct.images',
                'complementaryProducts.relatedProduct.images',
                'reviews',
            ])
            ->first();
=======
    ->where('slug', $slug)
    ->where('status', 'published')
    ->with([
        'brand',
        'mainCategory',
        'categories',
        'images',
        'variants',
        'attributeValues.attribute',
        'documents',
        'relatedProducts',
        'complementaryProducts',
        'reviews',
    ])
    ->firstOrFail();
>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76

        $currentVariant = null;

        // 2. Dacă nu am găsit produs, verificăm dacă e o variantă
        if (!$product) {
            $variant = ProductVariant::query()
                ->where('slug', $slug)
                ->with([
                    'product.brand',
                    'product.mainCategory',
                    'product.categories',
                    'product.images',
                    'product.variants.attributes.attribute',
                    'product.variants.units',
                    'product.units',
                    'product.attributeValues.attribute',
                    'product.documents',
                    'product.relatedProducts.relatedProduct.images',
                    'product.complementaryProducts.relatedProduct.images',
                    'product.reviews',
                    'attributes.attribute', // Load current variant attributes
                    'units' // Load current variant units
                ])
                ->first();

            if ($variant && $variant->product && $variant->product->status === 'published') {
                $product = $variant->product;
                $currentVariant = $variant;
            } else {
                abort(404);
            }
        }

        // 3. Formatăm produsul (cu sau fără variantă selectată)
        // Folosim serviciul actualizat care știe să facă merge și să calculeze unitățile
        $formattedProduct = $this->pricingService->formatProductForFrontend($product, $customer, $currentVariant);

<<<<<<< HEAD
        // 4. Gestionăm produsele similare / complementare
=======
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

        $productArray['attributes'] = $product->attributeValues?->map(function ($av) {
            return [
                'name'  => $av->attribute->name ?? '',
                'value' => $av->value,
            ];
        })->values() ?? [];

        // produse similare / complementare cu pricing
>>>>>>> bfb5b04ca9c1881d6b1bc203b41a8819391dca76
        $relatedProducts = $product->relatedProducts
            ? $product->relatedProducts
                ->map(fn($rel) => $rel->relatedProduct)
                ->filter(fn($p) => $p && $p->status === 'published')
                ->map(function ($p) use ($customer) {
                    return $this->pricingService->formatProductForFrontend($p, $customer);
                })->values()
            : [];

        $complementaryProducts = $product->complementaryProducts
            ? $product->complementaryProducts
                ->map(fn($rel) => $rel->relatedProduct)
                ->filter(fn($p) => $p && $p->status === 'published')
                ->map(function ($p) use ($customer) {
                    return $this->pricingService->formatProductForFrontend($p, $customer);
                })->values()
            : [];

        // 5. Review-uri (rămân pe produsul părinte deocamdată)
        $approved = $product->reviews()->where('status', 'approved')->get();
        $avg = $approved->count() ? round($approved->avg('rating'), 2) : null;
        $count = $approved->count();
        
        // Adăugăm datele extra în obiectul formatat (dacă nu sunt deja puse de service)
        // Service-ul pune structura de bază, noi adăugăm listele de produse conexe și review-urile detaliate
        
        $formattedProduct['aggregate_rating'] = $avg ? ['ratingValue' => (float) $avg, 'ratingCount' => (int) $count] : null;
        $formattedProduct['average_rating'] = $avg;
        $formattedProduct['rating_count'] = $count;
        $formattedProduct['reviews'] = $approved->sortByDesc('created_at')->take(10)->map(function ($r) {
            return [
                'author_name' => $r->author_name,
                'rating' => (int) $r->rating,
                'title' => $r->title,
                'body' => $r->body,
                'created_at' => $r->created_at?->toDateString(),
            ];
        })->values();

        // Documents, Images, Attributes are already handled inside formatProductForFrontend via $product->... 
        // Wait, formatProductForFrontend adds main_image_url, but NOT the full images list, documents list, etc.
        // We need to add those back to ensure full details page works.
        
        // --- Re-adding rich data that might be missing from the light formatProductForFrontend ---
        
        $formattedProduct['images'] = $product->images?->map(function ($img) {
            $path = $img->path;
            $url = (str_starts_with($path, 'http') || str_starts_with($path, '/storage/')) 
                ? $path 
                : \Illuminate\Support\Facades\Storage::url($path);

            return [
                'id'       => $img->id,
                'url'      => $url,
                'is_main'  => (bool) ($img->is_main ?? false),
                'position' => $img->position ?? 0,
            ];
        })->values() ?? [];

        $formattedProduct['documents'] = $product->documents?->map(function ($doc) {
            return [
                'id'       => $doc->id,
                'name'     => $doc->name,
                'type'     => $doc->type,
                'url'      => $doc->url ?? null,
                'is_locked'=> (bool) ($doc->is_locked ?? false),
            ];
        })->values() ?? [];

        // Attributes for the parent product (technical specs) - separate from variant attributes
        $formattedProduct['attributes'] = $product->attributeValues?->map(function ($av) {
            return [
                'name'  => $av->attribute->name ?? '',
                'value' => $av->value,
            ];
        })->values() ?? [];

        return response()->json([
            'product'                => $formattedProduct,
            'related_products'       => $relatedProducts,
            'complementary_products' => $complementaryProducts,
        ]);
    }

    public function brands()
    {
        return Brand::where('is_published', true)->orderBy('sort_order')->get();
    }

    public function brand($slug, Request $request)
    {
        $customer = optional($request->user())->customer;
        $brand = Brand::where('slug', $slug)->firstOrFail();

        $paginator = $brand->products()->paginate(24);

        $paginator->getCollection()->transform(function ($product) use ($customer) {
            return $this->pricingService->formatProductForFrontend($product, $customer);
        });

        return response()->json([
            'brand'    => $brand,
            'products' => $paginator,
        ]);
    }
}
