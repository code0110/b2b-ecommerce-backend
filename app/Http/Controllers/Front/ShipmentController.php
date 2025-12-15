<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Shipment;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $customer = $request->user()->customer;

        $shipments = Shipment::with('order')
            ->whereHas('order', function ($q) use ($customer) {
                $q->where('customer_id', $customer?->id);
            })
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($shipments);
    }

    public function show(Request $request, Shipment $shipment)
    {
        $customer = $request->user()->customer;

        $shipment->load('order');

        if ($shipment->order->customer_id !== $customer?->id) {
            abort(403);
        }

        return response()->json($shipment);
    }
}
