<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalesRepresentative;
use Illuminate\Http\Request;

class SalesRepresentativeController extends Controller
{
    public function index()
    {
        return SalesRepresentative::orderBy('sort_order')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => ['required', 'string', 'max:191'],
            'email'      => ['nullable', 'email', 'max:191'],
            'phone'      => ['nullable', 'string', 'max:50'],
            'region'     => ['nullable', 'string', 'max:100'],
            'counties'   => ['array'],
            'counties.*' => ['string'],
            'is_active'  => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $rep = SalesRepresentative::create($data);

        return response()->json($rep, 201);
    }

    public function show(SalesRepresentative $salesRepresentative)
    {
        return $salesRepresentative;
    }

    public function update(Request $request, SalesRepresentative $salesRepresentative)
    {
        $data = $request->validate([
            'name'       => ['sometimes', 'string', 'max:191'],
            'email'      => ['nullable', 'email', 'max:191'],
            'phone'      => ['nullable', 'string', 'max:50'],
            'region'     => ['nullable', 'string', 'max:100'],
            'counties'   => ['array'],
            'counties.*' => ['string'],
            'is_active'  => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $salesRepresentative->update($data);

        return response()->json($salesRepresentative);
    }

    public function destroy(SalesRepresentative $salesRepresentative)
    {
        $salesRepresentative->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
