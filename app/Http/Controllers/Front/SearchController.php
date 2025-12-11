<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $query = Product::query()->with(['brand', 'categories']);

        if ($q !== '') {
            $query->where(function ($qBuilder) use ($q) {
                $qBuilder
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('internal_code', 'like', "%{$q}%")
                    ->orWhere('barcode', 'like', "%{$q}%");
            });
        }

        if ($categorySlug = $request->get('category')) {
            $query->whereHas('categories', function ($qBuilder) use ($categorySlug) {
                $qBuilder->where('slug', $categorySlug);
            });
        }

        if ($brandSlug = $request->get('brand')) {
            $query->whereHas('brand', function ($qBuilder) use ($brandSlug) {
                $qBuilder->where('slug', $brandSlug);
            });
        }

        if ($minPrice = $request->get('min_price')) {
            $query->where('list_price', '>=', (float) $minPrice);
        }

        if ($maxPrice = $request->get('max_price')) {
            $query->where('list_price', '<=', (float) $maxPrice);
        }

        // disponibilitate
        if ($availability = $request->get('availability')) {
            if ($availability === 'in_stock') {
                $query->where('stock_status', 'in_stock');
            } elseif ($availability === 'on_order') {
                $query->where('stock_status', 'on_order');
            }
        }

        $sort = $request->get('sort', 'relevance');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('list_price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('list_price', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('name');
        }

        $perPage = (int) $request->get('per_page', 20);

        return $query->paginate($perPage);
    }

    public function autocomplete(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        if ($q === '') {
            return response()->json([
                'products'  => [],
                'categories'=> [],
                'brands'    => [],
            ]);
        }

        $products = Product::select('id', 'name', 'slug', 'internal_code')
            ->where(function ($qBuilder) use ($q) {
                $qBuilder
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('internal_code', 'like', "%{$q}%")
                    ->orWhere('barcode', 'like', "%{$q}%");
            })
            ->limit(10)
            ->get();

        $categories = Category::select('id', 'name', 'slug')
            ->where('name', 'like', "%{$q}%")
            ->limit(5)
            ->get();

        $brands = Brand::select('id', 'name', 'slug')
            ->where('name', 'like', "%{$q}%")
            ->limit(5)
            ->get();

        return response()->json([
            'products'   => $products,
            'categories' => $categories,
            'brands'     => $brands,
        ]);
    }
}
