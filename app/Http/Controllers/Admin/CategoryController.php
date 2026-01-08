<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::with('parent')->orderBy('sort_order');
        if ($q = $request->get('q')) {
             $query->where('name', 'like', "%{$q}%");
        }
        return $query->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:191'],
            'slug'        => ['required', 'string', 'max:191', 'unique:categories,slug'],
            'parent_id'   => ['nullable', 'integer', 'exists:categories,id'],
            'sort_order'  => ['nullable', 'integer'],
            'is_published'=> ['boolean'],
            'attributes'  => ['nullable', 'array'],
            'attributes.*'=> ['integer', 'exists:attributes,id'],
        ]);

        $category = Category::create($data);

        if (isset($data['attributes'])) {
            $category->attributes()->sync($data['attributes']);
        }

        return response()->json($category->load('attributes'), 201);
    }

    public function show(Category $category)
    {
        return $category->load(['children', 'attributes']);
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:191'],
            'slug'        => ['sometimes', 'string', 'max:191', Rule::unique('categories', 'slug')->ignore($category->id)],
            'parent_id'   => ['nullable', 'integer', 'exists:categories,id'],
            'sort_order'  => ['nullable', 'integer'],
            'is_published'=> ['boolean'],
            'attributes'  => ['nullable', 'array'],
            'attributes.*'=> ['integer', 'exists:attributes,id'],
        ]);

        $category->update($data);

        if (isset($data['attributes'])) {
            $category->attributes()->sync($data['attributes']);
        }

        return response()->json($category->load('attributes'));
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            abort(400, 'Cannot delete category with attached products.');
        }
        
        $category->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
