<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Promotion;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
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

    public function product($slug)
    {
        $product = Product::where('slug', $slug)
            ->with(['brand', 'mainCategory', 'images', 'variants', 'attributes.attribute', 'related.relatedProduct'])
            ->firstOrFail();

        return $product;
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
