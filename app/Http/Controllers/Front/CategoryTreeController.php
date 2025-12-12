<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryTreeController extends Controller
{
    /**
     * Returnează categoriile de nivel 1 + subcategoriile lor,
     * pentru overlay-ul de catalog.
     */
    public function __invoke()
    {
        $categories = Category::query()
            ->with('children')
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json($categories);
    }
}
