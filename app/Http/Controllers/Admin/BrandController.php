<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    public function index()
    {
        return Brand::orderBy('sort_order')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:191'],
            'slug'        => ['required', 'string', 'max:191', 'unique:brands,slug'],
            'description' => ['nullable', 'string'],
            'is_published'=> ['boolean'],
            'sort_order'  => ['nullable', 'integer'],
        ]);

        $brand = Brand::create($data);

        return response()->json($brand, 201);
    }

    public function show(Brand $brand)
    {
        return $brand;
    }

    public function update(Request $request, Brand $brand)
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:191'],
            'slug'        => ['sometimes', 'string', 'max:191', Rule::unique('brands', 'slug')->ignore($brand->id)],
            'description' => ['nullable', 'string'],
            'is_published'=> ['boolean'],
            'sort_order'  => ['nullable', 'integer'],
        ]);

        $brand->update($data);

        return response()->json($brand);
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
