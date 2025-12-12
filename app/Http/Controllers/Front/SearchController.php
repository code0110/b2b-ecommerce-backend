<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $term = trim((string) $request->input('q', ''));

        if ($term === '') {
            return response()->json([
                'query'     => '',
                'products'  => ['data' => [], 'meta' => null],
                'categories' => [],
                'brands'    => [],
            ]);
        }

        $perPage = min((int) $request->input('per_page', 24), 100);

        // produse
        $productQuery = Product::query()
            ->with(['mainCategory', 'brand'])
            ->where('status', 'published')
            ->where(function ($q) use ($term) {
                $like = '%' . $term . '%';
                $q->where('name', 'like', $like)
                  ->orWhere('code', 'like', $like)
                  ->orWhere('sku', 'like', $like)
                  ->orWhere('barcode', 'like', $like);
            })
            ->orderBy('name');

        $products = $productQuery->paginate($perPage);

        // categorii sugestii
        $categories = Category::query()
            ->where('name', 'like', '%' . $term . '%')
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'slug']);

        // branduri sugestii
        $brands = Brand::query()
            ->where('name', 'like', '%' . $term . '%')
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'query'      => $term,
            'products'   => $products,
            'categories' => $categories,
            'brands'     => $brands,
        ]);
    }
}
