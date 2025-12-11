<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Shipment::with('order');

        if ($courier = $request->get('courier')) {
            $query->where('courier', $courier);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return $query->orderByDesc('created_at')->paginate(50);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'order_id'    => ['required', 'integer', 'exists:orders,id'],
            'courier'     => ['required', 'string', 'max:50'],
            'awb_number'  => ['nullable', 'string', 'max:100'],
            'tracking_url'=> ['nullable', 'string'],
        ]);

        // TODO: aici se poate apela API-ul curierului pentru generare AWB
        $shipment = Shipment::create([
            'order_id'    => $data['order_id'],
            'courier'     => $data['courier'],
            'awb_number'  => $data['awb_number'] ?? null,
            'tracking_url'=> $data['tracking_url'] ?? null,
            'status'      => 'created',
            'is_active'   => true,
        ]);

        return response()->json($shipment->load('order'), 201);
    }

    public function updateStatus($id, Request $request)
    {
        $shipment = Shipment::findOrFail($id);

        $data = $request->validate([
            'status' => ['required', 'string', 'max:50'],
        ]);

        $shipment->status = $data['status'];
        $shipment->save();

        return response()->json($shipment);
    }
}
