<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\PartnerRequest;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'company_name'  => ['required', 'string', 'max:191'],
            'cif'           => ['nullable', 'string', 'max:50'],
            'reg_com'       => ['nullable', 'string', 'max:50'],
            'iban'          => ['nullable', 'string', 'max:50'],
            'contact_name'  => ['required', 'string', 'max:191'],
            'email'         => ['required', 'email', 'max:191'],
            'phone'         => ['nullable', 'string', 'max:50'],
            'region'        => ['nullable', 'string', 'max:100'],
            'activity_type' => ['nullable', 'string', 'max:100'],
            'notes'         => ['nullable', 'string'],
        ]);

        $req = PartnerRequest::create($data + ['status' => 'new']);

        // aici poți adăuga logică de alocare automată la un agent, pe regiune

        return response()->json([
            'message' => 'Cererea de parteneriat a fost înregistrată.',
            'data'    => $req,
        ], 201);
    }
}
