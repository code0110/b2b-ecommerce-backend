<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\PartnerRequest;
use Illuminate\Http\Request;

class PartnerRequestController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'company_name'  => 'required|string|max:255',
            'cif'           => 'nullable|string|max:50',
            'reg_com'       => 'nullable|string|max:50',
            'iban'          => 'nullable|string|max:50',
            'contact_name'  => 'required|string|max:255',
            'email'         => 'required|email|max:255',
            'phone'         => 'nullable|string|max:50',
            'region'        => 'nullable|string|max:100',
            'activity_type' => 'nullable|string|max:100',
            'notes'         => 'nullable|string',
        ]);

        $partnerRequest = PartnerRequest::create($data);

        return response()->json([
            'message' => 'Cererea a fost înregistrată. Vei fi contactat de un reprezentant.',
            'request' => $partnerRequest,
        ], 201);
    }
}
