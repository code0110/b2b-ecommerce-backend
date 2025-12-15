<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * GET /api/search?q=...
     * Căutare simplă în produse (nume, cod intern, barcode).
     */
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $page = (int) $request->query('page', 1);
        $perPage = min((int) $request->query('per_page', 24), 100);

        if ($q === '') {
            return response()->json([
                'data' => [],
                'meta' => [
                    'total'        => 0,
                    'current_page' => 1,
                    'last_page'    => 1,
                ],
            ]);
        }

        $builder = DB::table('products')
            ->leftJoin('categories', 'products.main_category_id', '=', 'categories.id')
            ->select(
                'products.*',
                'categories.name as category_name'
            )
            ->where('products.status', 'published')
            ->where(function ($sql) use ($q) {
                $like = '%' . $q . '%';
                $sql->where('products.name', 'like', $like)
                    ->orWhere('products.internal_code', 'like', $like)
                    ->orWhere('products.barcode', 'like', $like);
            });

        $total = (clone $builder)->count();

        $items = $builder
            ->orderBy('products.name')
            ->forPage($page, $perPage)
            ->get()
            ->map(function ($row) {
                $price = $row->price_override ?? $row->list_price;

                return [
                    'id'       => $row->id,
                    'slug'     => $row->slug,
                    'name'     => $row->name,
                    'code'     => $row->internal_code,
                    'category' => $row->category_name,
                    'price'    => (float) $price,
                    'is_new'   => (bool) $row->is_new,
                ];
            });

        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;

        return response()->json([
            'data' => $items,
            'meta' => [
                'total'        => $total,
                'current_page' => $page,
                'last_page'    => $lastPage,
            ],
        ]);
    }
}
