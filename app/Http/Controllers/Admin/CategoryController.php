<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        return Category::with('parent')->orderBy('sort_order')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:191'],
            'slug'        => ['required', 'string', 'max:191', 'unique:categories,slug'],
            'parent_id'   => ['nullable', 'integer', 'exists:categories,id'],
            'sort_order'  => ['nullable', 'integer'],
            'is_published'=> ['boolean'],
        ]);

        $category = Category::create($data);

        return response()->json($category, 201);
    }

    public function show(Category $category)
    {
        return $category->load('children');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:191'],
            'slug'        => ['sometimes', 'string', 'max:191', Rule::unique('categories', 'slug')->ignore($category->id)],
            'parent_id'   => ['nullable', 'integer', 'exists:categories,id'],
            'sort_order'  => ['nullable', 'integer'],
            'is_published'=> ['boolean'],
        ]);

        $category->update($data);

        return response()->json($category);
    }

    public function destroy(Category $category)
    {
        // TODO: validare că nu are produse atașate, înainte de ștergere
        $category->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
