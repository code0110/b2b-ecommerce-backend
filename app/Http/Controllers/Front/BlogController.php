<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\BlogCategory;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $categorySlug = $request->query('category');
        $search       = $request->query('q');

        $query = BlogPost::query()
            ->where('is_published', true)
            ->with('category');

        if ($categorySlug) {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('excerpt', 'like', '%' . $search . '%')
                  ->orWhere('content', 'like', '%' . $search . '%');
            });
        }

        $posts = $query->orderByDesc('published_at')->paginate(10);

        $categories = BlogCategory::where('is_published', true)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'posts'      => $posts,
            'categories' => $categories,
        ]);
    }

    public function show(string $slug)
    {
        $post = BlogPost::with('category')
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $related = BlogPost::where('is_published', true)
            ->where('id', '!=', $post->id)
            ->where('category_id', $post->category_id)
            ->orderByDesc('published_at')
            ->limit(3)
            ->get();

        return response()->json([
            'post'    => $post,
            'related' => $related,
        ]);
    }
}
