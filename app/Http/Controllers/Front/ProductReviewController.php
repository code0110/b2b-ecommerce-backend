<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;

class ProductReviewController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'author_name' => ['required', 'string', 'max:255'],
            'rating'      => ['required', 'integer', 'min:1', 'max:5'],
            'title'       => ['nullable', 'string', 'max:255'],
            'body'        => ['required', 'string', 'max:5000'],
        ]);

        $review = ProductReview::create([
            'product_id'  => $product->id,
            'author_name' => $data['author_name'],
            'rating'      => $data['rating'],
            'title'       => $data['title'] ?? null,
            'body'        => $data['body'],
            'status'      => 'approved',
        ]);

        return response()->json($review, 201);
    }
}
