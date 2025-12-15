<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        $customer = $request->user()->customer;

        $addresses = Address::where('customer_id', $customer?->id)->get();

        return response()->json($addresses);
    }

    public function store(Request $request)
    {
        $customer = $request->user()->customer;

        $data = $request->validate([
            'type'       => 'required|in:billing,shipping',
            'name'       => 'required|string|max:255',
            'line1'      => 'required|string|max:255',
            'line2'      => 'nullable|string|max:255',
            'city'       => 'required|string|max:255',
            'county'     => 'nullable|string|max:255',
            'postal_code'=> 'nullable|string|max:50',
            'country'    => 'required|string|max:2',
            'is_default' => 'boolean',
        ]);

        $data['customer_id'] = $customer?->id;

        if (!empty($data['is_default'])) {
            Address::where('customer_id', $customer?->id)
                ->where('type', $data['type'])
                ->update(['is_default' => false]);
        }

        $address = Address::create($data);

        return response()->json($address, 201);
    }

    public function update(Request $request, Address $address)
    {
        $customer = $request->user()->customer;

        if ($address->customer_id !== $customer?->id) {
            abort(403);
        }

        $data = $request->validate([
            'type'       => 'sometimes|in:billing,shipping',
            'name'       => 'sometimes|string|max:255',
            'line1'      => 'sometimes|string|max:255',
            'line2'      => 'nullable|string|max:255',
            'city'       => 'sometimes|string|max:255',
            'county'     => 'nullable|string|max:255',
            'postal_code'=> 'nullable|string|max:50',
            'country'    => 'sometimes|string|max:2',
            'is_default' => 'boolean',
        ]);

        if (isset($data['is_default']) && $data['is_default']) {
            Address::where('customer_id', $customer?->id)
                ->where('type', $address->type)
                ->update(['is_default' => false]);
        }

        $address->update($data);

        return response()->json($address);
    }

    public function destroy(Request $request, Address $address)
    {
        $customer = $request->user()->customer;

        if ($address->customer_id !== $customer?->id) {
            abort(403);
        }

        $address->delete();

        return response()->json(['message' => 'Address deleted']);
    }
}
