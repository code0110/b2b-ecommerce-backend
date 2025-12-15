<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderApprovalController extends Controller
{
    public function pending(Request $request)
    {
        $user = $request->user();
        $customer = $user->customer;

        $orders = Order::where('customer_id', $customer?->id)
            ->where('approval_status', 'pending')
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($orders);
    }

    public function approve(Request $request, Order $order)
    {
        $user = $request->user();
        $customer = $user->customer;

        if ($order->customer_id !== $customer?->id) {
            abort(403);
        }

        if ($order->approval_status !== 'pending') {
            return response()->json([
                'message' => 'Order is not in pending approval state.'
            ], 422);
        }

        $order->update([
            'approval_status'     => 'approved',
            'approved_by_user_id' => $user->id,
            'approved_at'         => now(),
        ]);

        return response()->json($order);
    }

    public function reject(Request $request, Order $order)
    {
        $user = $request->user();
        $customer = $user->customer;

        if ($order->customer_id !== $customer?->id) {
            abort(403);
        }

        if ($order->approval_status !== 'pending') {
            return response()->json([
                'message' => 'Order is not in pending approval state.'
            ], 422);
        }

        $order->update([
            'approval_status'     => 'rejected',
            'approved_by_user_id' => $user->id,
            'approved_at'         => now(),
        ]);

        return response()->json($order);
    }
}
