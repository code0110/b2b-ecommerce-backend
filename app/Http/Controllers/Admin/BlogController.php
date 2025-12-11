<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BlogController extends Controller
{
    public function index()
    {
        return BlogPost::with('category')->orderByDesc('published_at')->paginate(25);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'            => ['required', 'string', 'max:191'],
            'slug'             => ['required', 'string', 'max:191', 'unique:blog_posts,slug'],
            'excerpt'          => ['nullable', 'string'],
            'content'          => ['required', 'string'],
            'category_id'      => ['nullable', 'integer', 'exists:blog_categories,id'],
            'is_published'     => ['boolean'],
            'published_at'     => ['nullable', 'date'],
            'meta_title'       => ['nullable', 'string', 'max:191'],
            'meta_description' => ['nullable', 'string', 'max:255'],
        ]);

        $post = BlogPost::create($data + [
            'author_user_id' => $request->user()->id,
        ]);

        return response()->json($post->load('category'), 201);
    }

    public function show(BlogPost $blogPost)
    {
        return $blogPost->load('category', 'author');
    }

    public function update(Request $request, BlogPost $blogPost)
    {
        $data = $request->validate([
            'title'            => ['sometimes', 'string', 'max:191'],
            'slug'             => ['sometimes', 'string', 'max:191', Rule::unique('blog_posts', 'slug')->ignore($blogPost->id)],
            'excerpt'          => ['nullable', 'string'],
            'content'          => ['sometimes', 'string'],
            'category_id'      => ['nullable', 'integer', 'exists:blog_categories,id'],
            'is_published'     => ['boolean'],
            'published_at'     => ['nullable', 'date'],
            'meta_title'       => ['nullable', 'string', 'max:191'],
            'meta_description' => ['nullable', 'string', 'max:255'],
        ]);

        $blogPost->update($data);

        return response()->json($blogPost->load('category'));
    }

    public function destroy(BlogPost $blogPost)
    {
        $blogPost->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    // categorii
    public function categories()
    {
        return BlogCategory::orderBy('sort_order')->get();
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:191'],
            'slug'        => ['required', 'string', 'max:191', 'unique:blog_categories,slug'],
            'description' => ['nullable', 'string'],
            'is_published'=> ['boolean'],
            'sort_order'  => ['nullable', 'integer'],
        ]);

        $category = BlogCategory::create($data);

        return response()->json($category, 201);
    }

    public function updateCategory($id, Request $request)
    {
        $category = BlogCategory::findOrFail($id);

        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:191'],
            'slug'        => ['sometimes', 'string', 'max:191', Rule::unique('blog_categories', 'slug')->ignore($category->id)],
            'description' => ['nullable', 'string'],
            'is_published'=> ['boolean'],
            'sort_order'  => ['nullable', 'integer'],
        ]);

        $category->update($data);

        return response()->json($category);
    }

    public function destroyCategory($id)
    {
        $category = BlogCategory::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
