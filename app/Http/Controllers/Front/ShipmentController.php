<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Shipment::with('order')
            ->whereHas('order', function ($q) use ($user) {
                $q->where('customer_id', $user->customer_id);
            });

        if ($orderId = $request->get('order_id')) {
            $query->where('order_id', $orderId);
        }

        if ($courier = $request->get('courier')) {
            $query->where('courier', $courier);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return $query->orderByDesc('created_at')->paginate(20);
    }

    public function show($id, Request $request)
    {
        $user = $request->user();

        $shipment = Shipment::with('order')
            ->where('id', $id)
            ->whereHas('order', function ($q) use ($user) {
                $q->where('customer_id', $user->customer_id);
            })
            ->firstOrFail();

        return $shipment;
    }
}
