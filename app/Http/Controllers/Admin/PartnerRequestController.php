<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PartnerRequest;
use Illuminate\Http\Request;

class PartnerRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = PartnerRequest::query();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($region = $request->get('region')) {
            $query->where('region', $region);
        }

        return $query->orderByDesc('id')->paginate(25);
    }

    public function show(PartnerRequest $partnerRequest)
    {
        return $partnerRequest->load('assignedAgent');
    }

    public function update(Request $request, PartnerRequest $partnerRequest)
    {
        $data = $request->validate([
            'status'            => ['sometimes', 'string', 'max:50'],
            'assigned_agent_id' => ['sometimes', 'nullable', 'integer'],
            'notes'             => ['nullable', 'string'],
        ]);

        $partnerRequest->update($data);

        return response()->json($partnerRequest->load('assignedAgent'));
    }
}
