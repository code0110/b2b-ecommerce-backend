<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * GET /api/search/suggestions?q=...
     * Sugestii rapide pentru autocomplete (produse, categorii, branduri).
     */
    public function suggestions(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        if (strlen($q) < 2) {
            return response()->json(['products' => [], 'categories' => [], 'brands' => []]);
        }

        $like = '%' . $q . '%';

        // Produse (limit 5)
        $products = DB::table('products')
            ->leftJoin('product_images', function($join) {
                $join->on('products.id', '=', 'product_images.product_id')
                     ->where('product_images.is_main', '=', 1);
            })
            ->select(
                'products.id', 
                'products.name', 
                'products.slug', 
                'products.internal_code', 
                'products.list_price', 
                'products.price_override', 
                'products.main_category_id', 
                'products.vat_rate', 
                'products.stock_status',
                'products.stock_qty',
                'product_images.path as main_image_path'
            )
            ->where('products.status', 'published')
            ->where(function($query) use ($like) {
                $query->where('products.name', 'like', $like)
                      ->orWhere('products.internal_code', 'like', $like)
                      ->orWhere('products.barcode', 'like', $like);
            })
            ->limit(5)
            ->get()
            ->map(function($p) {
                $p->price = $p->price_override ?? $p->list_price;
                $p->vat_rate = (float) $p->vat_rate;
                $p->vat_included = false;
                return $p;
            });

        // Categorii (limit 3)
        $categories = DB::table('categories')
            ->select('id', 'name', 'slug')
            ->where('is_published', 1)
            ->where('name', 'like', $like)
            ->limit(3)
            ->get();

        // Branduri (limit 3)
        $brands = DB::table('brands')
            ->select('id', 'name', 'slug')
            ->where('is_published', 1)
            ->where('name', 'like', $like)
            ->limit(3)
            ->get();

        return response()->json([
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands
        ]);
    }

    /**
     * GET /api/search?q=...
     * Căutare avansată în produse (nume, cod, barcode, descriere, specs, brand, categorie).
     * Suportă filtre și sortare.
     */
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $page = (int) $request->query('page', 1);
        $perPage = min((int) $request->query('per_page', 24), 100);

        // Filtre
        $brandIds = $request->query('brands', []);
        if (is_string($brandIds)) $brandIds = explode(',', $brandIds);

        $categoryIds = $request->query('categories', []);
        if (is_string($categoryIds)) $categoryIds = explode(',', $categoryIds);

        $minPrice = $request->query('min_price');
        $maxPrice = $request->query('max_price');
        $sort = $request->query('sort', 'relevance');
        $priceMode = $request->query('price_mode', 'net'); // 'net' or 'gross'

        // Construire query de bază
        $builder = DB::table('products')
            ->leftJoin('categories', 'products.main_category_id', '=', 'categories.id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.id')
            ->leftJoin('product_images', function($join) {
                $join->on('products.id', '=', 'product_images.product_id')
                     ->where('product_images.is_main', '=', 1);
            })
            ->select(
                'products.*',
                'categories.name as category_name',
                'brands.name as brand_name',
                'product_images.path as main_image_path'
            )
            ->where('products.status', 'published');

        // Helper expression for price (Raw/Net usually)
        $rawPriceSql = 'COALESCE(products.price_override, products.list_price)';
        
        // Helper expression for Gross Price
        // Assuming VAT Rate is percentage (e.g. 19 for 19%)
        // IF vat_included is not present, assume Net prices.
        $grossPriceSql = "
            ({$rawPriceSql} * (1 + (COALESCE(products.vat_rate, 0) / 100)))
        ";

        // Determine which price expression to use for filtering/sorting
        $priceSql = ($priceMode === 'gross') ? $grossPriceSql : $rawPriceSql;

        // Căutare Text
        if ($q !== '') {
            $like = '%' . $q . '%';
            $builder->where(function ($sql) use ($like, $q) {
                $sql->where('products.name', 'like', $like)
                    ->orWhere('products.internal_code', 'like', $like)
                    ->orWhere('products.barcode', 'like', $like)
                    ->orWhere('products.short_description', 'like', $like)
                    ->orWhere('brands.name', 'like', $like)
                    ->orWhere('categories.name', 'like', $like)
                    ->orWhereRaw('CAST(products.technical_specs AS CHAR) LIKE ?', ['%'.$q.'%']);
            });
        }

        // Aplicare Filtre
        if (!empty($brandIds)) {
            $builder->whereIn('products.brand_id', $brandIds);
        }
        if (!empty($categoryIds)) {
            $builder->whereIn('products.main_category_id', $categoryIds);
        }
        if ($minPrice !== null) {
            $builder->whereRaw("{$priceSql} >= ?", [$minPrice]);
        }
        if ($maxPrice !== null) {
            $builder->whereRaw("{$priceSql} <= ?", [$maxPrice]);
        }

        // Clonare pentru fațete (facets) - înainte de paginare
        $facetBuilder = clone $builder;
        $total = $builder->count();

        // Sortare
        switch ($sort) {
            case 'price_asc':
                $builder->orderByRaw("{$priceSql} ASC");
                break;
            case 'price_desc':
                $builder->orderByRaw("{$priceSql} DESC");
                break;
            case 'newest':
                $builder->orderBy('products.created_at', 'desc');
                break;
            case 'name_asc':
                $builder->orderBy('products.name', 'asc');
                break;
            case 'relevance':
            default:
                if ($q !== '') {
                    // Relevanță simplă: potrivire exactă > începe cu > conține
                    $builder->orderByRaw("CASE 
                        WHEN products.name = ? THEN 1 
                        WHEN products.name LIKE ? THEN 2 
                        WHEN products.internal_code = ? THEN 3
                        ELSE 4 END", [$q, $q.'%', $q]);
                } else {
                    $builder->orderBy('products.name', 'asc');
                }
                break;
        }

        // Paginare și rezultate
        $items = $builder
            ->forPage($page, $perPage)
            ->get()
            ->map(function ($row) {
                $price = $row->price_override ?? $row->list_price;
                return [
                    'id'       => $row->id,
                    'slug'     => $row->slug,
                    'name'     => $row->name,
                    'code'     => $row->internal_code,
                    'internal_code' => $row->internal_code,
                    'category' => $row->category_name,
                    'main_category_name' => $row->category_name,
                    'brand'    => $row->brand_name,
                    'price'    => (float) $price,
                    'list_price' => (float) $row->list_price,
                    'price_override' => (float) $row->price_override,
                    'short_description' => $row->short_description,
                    'description' => $row->long_description,
                    'is_new'   => (bool) $row->is_new,
                    'stock_qty' => (int) $row->stock_qty,
                    'stock_status' => $row->stock_status,
                    'vat_rate'     => (float) $row->vat_rate,
                    'vat_included' => false,
                    'main_image_url' => $row->main_image_path, 
                ];
            });

        // Calcul Fațete (Facets)
        $priceStats = (clone $facetBuilder)->select(
            DB::raw("MIN({$priceSql}) as min_price"),
            DB::raw("MAX({$priceSql}) as max_price")
        )->first();

        $brandsFacet = (clone $facetBuilder)
            ->select('brands.id', 'brands.name', DB::raw('count(*) as count'))
            ->groupBy('brands.id', 'brands.name')
            ->whereNotNull('brands.id')
            ->orderBy('count', 'desc')
            ->limit(20)
            ->get();

        $categoriesFacet = (clone $facetBuilder)
            ->select('categories.id', 'categories.name', DB::raw('count(*) as count'))
            ->groupBy('categories.id', 'categories.name')
            ->whereNotNull('categories.id')
            ->orderBy('count', 'desc')
            ->limit(20)
            ->get();

        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;

        return response()->json([
            'data' => $items,
            'meta' => [
                'total'        => $total,
                'current_page' => $page,
                'last_page'    => $lastPage,
            ],
            'facets' => [
                'min_price' => $priceStats->min_price ?? 0,
                'max_price' => $priceStats->max_price ?? 0,
                'brands' => $brandsFacet,
                'categories' => $categoriesFacet
            ]
        ]);
    }
}
