<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use App\Models\ShippingRule;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function index()
    {
        return ShippingMethod::with('rules')->get();
    }

    public function config()
    {
        return response()->json([
            'types' => [
                ['value' => 'courier', 'label' => 'Curier'],
                ['value' => 'own_fleet', 'label' => 'Flotă Proprie'],
                ['value' => 'pickup', 'label' => 'Ridicare din sediu'],
            ]
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:191'],
            'code'      => ['required', 'string', 'max:50', 'unique:shipping_methods,code'],
            'type'      => ['required', 'in:courier,own_fleet,pickup'],
            'is_active' => ['boolean'],
        ]);

        $method = ShippingMethod::create($data);

        return response()->json($method, 201);
    }

    public function update(Request $request, $id)
    {
        $method = ShippingMethod::findOrFail($id);

        $data = $request->validate([
            'name'      => ['sometimes', 'string', 'max:191'],
            'code'      => ['sometimes', 'string', 'max:50'],
            'type'      => ['sometimes', 'in:courier,own_fleet,pickup'],
            'is_active' => ['boolean'],
        ]);

        $method->update($data);

        return response()->json($method);
    }

    public function destroy($id)
    {
        $method = ShippingMethod::findOrFail($id);
        $method->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
