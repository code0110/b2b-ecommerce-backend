<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class AccountOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $customer = $user->customer;

        $query = Order::with(['items.product', 'shippingMethod'])
            ->where('customer_id', $customer?->id)
            ->orderByDesc('created_at');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $orders = $query->paginate(10);

        return response()->json($orders);
    }

    public function show(Request $request, Order $order)
    {
        $user = $request->user();
        $customer = $user->customer;

        if ($order->customer_id !== $customer?->id) {
            abort(403);
        }

        $order->load([
            'items.product',
            'items.variant',
            'shippingMethod',
        ]);

        return response()->json($order);
    }
}
