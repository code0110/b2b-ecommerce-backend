<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Promotion; // dacă modelul tău de promoție are alt nume, ajustezi
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function homepage(Request $request)
    {
        // Promoții active (campanii)
        $promotions = Promotion::where('status', 'active')
            ->where(function ($q) {
                $now = now();
                $q->whereNull('start_date')->orWhere('start_date', '<=', $now);
            })
            ->where(function ($q) {
                $now = now();
                $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
            })
            ->orderByDesc('start_date')
            ->limit(5)
            ->get();

        $newProducts = Product::where('is_new', true)
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->limit(12)
            ->get();

        $recommended = Product::where('is_recommended', true)
            ->where('status', 'published')
            ->orderByDesc('updated_at')
            ->limit(12)
            ->get();

        $onSale = Product::where('is_on_sale', true)
            ->where('status', 'published')
            ->orderByDesc('updated_at')
            ->limit(12)
            ->get();

        return response()->json([
            'promotions'   => $promotions,
            'new_products' => $newProducts,
            'recommended'  => $recommended,
            'on_sale'      => $onSale,
        ]);
    }

    public function newProducts(Request $request)
    {
        $query = Product::where('is_new', true)
            ->where('status', 'published');

        // filtre simple: brand, categorie etc. pot fi adăugate similar cu SearchController
        $query->orderByDesc('created_at');

        return $query->paginate((int) $request->get('per_page', 20));
    }

    public function discountedProducts(Request $request)
    {
        $query = Product::where('is_on_sale', true)
            ->where('status', 'published')
            ->orderByDesc('updated_at');

        return $query->paginate((int) $request->get('per_page', 20));
    }
}
