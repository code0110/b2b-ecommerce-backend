<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CatalogHighlightController extends Controller
{
    public function newProducts(Request $request)
    {
        $query = Product::query()
            ->with(['brand', 'mainCategory'])
            ->where('status', 'published')
            ->orderByDesc('created_at');

        // exemplu: ultimele 60 zile
        $query->where('created_at', '>=', Carbon::now()->subDays(60));

        $perPage = min((int) $request->input('per_page', 24), 100);

        return response()->json(
            $query->paginate($perPage)
        );
    }

    public function discountedProducts(Request $request)
    {
        $query = Product::query()
            ->with(['brand', 'mainCategory'])
            ->where('status', 'published')
            ->whereNotNull('promo_price') // adaptează la coloanele tale
            ->orderByDesc('updated_at');

        $perPage = min((int) $request->input('per_page', 24), 100);

        return response()->json(
            $query->paginate($perPage)
        );
    }
}
