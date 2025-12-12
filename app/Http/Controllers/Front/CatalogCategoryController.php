<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CatalogCategoryController extends Controller
{
    public function show(string $slug, Request $request)
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->firstOrFail();

        // bază query produse
        $query = Product::query()
            ->with(['brand', 'mainCategory'])
            ->where('status', 'published')
            ->where('main_category_id', $category->id);

        // filtre simple
        if ($brandIds = $request->input('brands')) {
            $ids = is_array($brandIds) ? $brandIds : explode(',', $brandIds);
            $query->whereIn('brand_id', $ids);
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->input('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->input('max_price'));
        }

        if ($stock = $request->input('stock_status')) {
            // adaptare la coloanele tale (stock_status, stock, etc.)
            if ($stock === 'in_stock') {
                $query->where('stock', '>', 0);
            } elseif ($stock === 'out_of_stock') {
                $query->where('stock', '<=', 0);
            }
        }

        // sortare
        switch ($request->input('sort')) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('name');
        }

        $perPage = min((int) $request->input('per_page', 24), 100);

        $paginator = $query->paginate($perPage);

        // meta pentru filtre (liste branduri, interval de preț etc.)
        $baseQuery = Product::query()
            ->where('status', 'published')
            ->where('main_category_id', $category->id);

        $brands = $baseQuery
            ->clone()
            ->selectRaw('brand_id, COUNT(*) as products_count')
            ->whereNotNull('brand_id')
            ->groupBy('brand_id')
            ->with('brand')
            ->get()
            ->map(function ($row) {
                return [
                    'id'   => $row->brand->id ?? $row->brand_id,
                    'name' => $row->brand->name ?? 'Fără brand',
                    'count' => $row->products_count,
                ];
            })
            ->values();

        $priceRange = $baseQuery
            ->clone()
            ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
            ->first();

        return response()->json([
            'category' => $category,
            'filters'  => [
                'brands' => $brands,
                'price'  => [
                    'min' => (float) ($priceRange->min_price ?? 0),
                    'max' => (float) ($priceRange->max_price ?? 0),
                ],
            ],
            'products' => $paginator,
        ]);
    }
}
