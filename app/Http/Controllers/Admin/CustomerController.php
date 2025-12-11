<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query()->with('group');

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return $query->paginate(25);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'               => ['required', Rule::in(['b2c', 'b2b'])],
            'name'               => ['required', 'string', 'max:191'],
            'legal_name'         => ['nullable', 'string', 'max:191'],
            'cif'                => ['nullable', 'string', 'max:50'],
            'reg_com'            => ['nullable', 'string', 'max:50'],
            'iban'               => ['nullable', 'string', 'max:50'],
            'email'              => ['nullable', 'email', 'max:191'],
            'phone'              => ['nullable', 'string', 'max:50'],
            'group_id'           => ['nullable', 'integer', 'exists:customer_groups,id'],
            'payment_terms_days' => ['nullable', 'integer'],
            'credit_limit'       => ['nullable', 'numeric'],
            'current_balance'    => ['nullable', 'numeric'],
            'currency'           => ['nullable', 'string', 'max:10'],
            'is_active'          => ['boolean'],
            'is_partner'         => ['boolean'],
        ]);

        $customer = Customer::create($data);

        return response()->json($customer, 201);
    }

    public function show(Customer $customer)
    {
        return $customer->load('addresses', 'users', 'group');
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'type'               => ['sometimes', Rule::in(['b2c', 'b2b'])],
            'name'               => ['sometimes', 'string', 'max:191'],
            'legal_name'         => ['nullable', 'string', 'max:191'],
            'cif'                => ['nullable', 'string', 'max:50'],
            'reg_com'            => ['nullable', 'string', 'max:50'],
            'iban'               => ['nullable', 'string', 'max:50'],
            'email'              => ['nullable', 'email', 'max:191'],
            'phone'              => ['nullable', 'string', 'max:50'],
            'group_id'           => ['nullable', 'integer', 'exists:customer_groups,id'],
            'payment_terms_days' => ['nullable', 'integer'],
            'credit_limit'       => ['nullable', 'numeric'],
            'current_balance'    => ['nullable', 'numeric'],
            'currency'           => ['nullable', 'string', 'max:10'],
            'is_active'          => ['boolean'],
            'is_partner'         => ['boolean'],
        ]);

        $customer->update($data);

        return response()->json($customer);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
