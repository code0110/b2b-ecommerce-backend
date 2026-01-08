<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttributeController extends Controller
{
    public function index(Request $request)
    {
        $query = Attribute::query();
        
        if ($q = $request->get('search')) {
            $query->where('name', 'like', "%{$q}%")
                  ->orWhere('slug', 'like', "%{$q}%");
        }
        
        $perPage = $request->get('per_page', 20);
        return $query->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'slug' => ['required', 'string', 'max:191', 'unique:attributes,slug'],
            'type' => ['required', 'string', 'in:string,number,boolean,select'],
            'is_filterable' => ['boolean'],
        ]);

        $attribute = Attribute::create($data);

        return response()->json($attribute, 201);
    }

    public function show(Attribute $attribute)
    {
        return $attribute;
    }

    public function update(Request $request, Attribute $attribute)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:191'],
            'slug' => ['sometimes', 'string', 'max:191', Rule::unique('attributes', 'slug')->ignore($attribute->id)],
            'type' => ['sometimes', 'string', 'in:string,number,boolean,select'],
            'is_filterable' => ['boolean'],
        ]);

        $attribute->update($data);

        return response()->json($attribute);
    }

    public function destroy(Attribute $attribute)
    {
        $attribute->delete();
        return response()->json(['message' => 'Attribute deleted.']);
    }
}
