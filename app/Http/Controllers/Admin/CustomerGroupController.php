<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerGroup;
use Illuminate\Http\Request;

class CustomerGroupController extends Controller
{
    public function index(Request $request)
    {
        $query = CustomerGroup::query();

        if ($q = $request->get('q')) {
            $query->where('name', 'like', "%{$q}%");
        }

        return $query->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                     => ['required', 'string', 'max:191'],
            'type'                     => ['required', 'in:b2b,b2c'],
            'default_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'default_payment_terms_days' => ['nullable', 'integer', 'min:0'],
            'default_credit_limit'     => ['nullable', 'numeric', 'min:0'],
            'is_active'                => ['nullable', 'boolean'],
        ]);

        $group = CustomerGroup::create($data);

        return response()->json($group, 201);
    }

    public function show(CustomerGroup $customerGroup)
    {
        return $customerGroup;
    }

    public function update(Request $request, CustomerGroup $customerGroup)
    {
        $data = $request->validate([
            'name'                     => ['sometimes', 'string', 'max:191'],
            'type'                     => ['sometimes', 'in:b2b,b2c'],
            'default_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'default_payment_terms_days' => ['nullable', 'integer', 'min:0'],
            'default_credit_limit'     => ['nullable', 'numeric', 'min:0'],
            'is_active'                => ['nullable', 'boolean'],
        ]);

        $customerGroup->update($data);

        return response()->json($customerGroup);
    }

    public function destroy(CustomerGroup $customerGroup)
    {
        $customerGroup->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
