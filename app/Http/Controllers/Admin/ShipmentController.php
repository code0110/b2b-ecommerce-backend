<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Shipment::with('order.customer')
            ->orderByDesc('created_at');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($courier = $request->get('courier')) {
            $query->where('courier', $courier);
        }

        return response()->json($query->paginate(20));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'order_id'     => 'required|exists:orders,id',
            'courier'      => 'required|string|max:50',
            'awb_number'   => 'nullable|string|max:100',
            'label_url'    => 'nullable|string|max:255',
            'tracking_url' => 'nullable|string|max:255',
            'status'       => 'required|string|max:50',
        ]);

        $shipment = Shipment::create($data);

        return response()->json($shipment, 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $shipment = Shipment::findOrFail($id);

        $data = $request->validate([
            'status' => 'required|string|max:50',
        ]);

        $shipment->update($data);

        return response()->json($shipment);
    }
}
