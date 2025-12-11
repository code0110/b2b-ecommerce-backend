<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return Payment::where('customer_id', $user->customer_id)
            ->orderByDesc('payment_date')
            ->paginate(25);
    }

    public function payOrder($orderId, Request $request)
    {
        $user = $request->user();

        $order = Order::where('customer_id', $user->customer_id)
            ->where('id', $orderId)
            ->firstOrFail();

        $data = $request->validate([
            'type' => ['required', 'in:card,op'],
        ]);

        // aici se integrează ulterior procesatorul de plăți
        $payment = Payment::create([
            'customer_id'        => $user->customer_id,
            'order_id'           => $order->id,
            'recorded_by_user_id'=> $user->id,
            'type'               => $data['type'],
            'channel'            => 'online',
            'amount'             => $order->grand_total,
            'currency'           => $order->currency,
            'status'             => 'pending',
            'payment_date'       => null,
            'notes'              => 'Inițiere plată din cont client',
        ]);

        return response()->json([
            'message' => 'Plata a fost inițiată (mock). Integrarea procesatorului se face aici.',
            'payment' => $payment,
        ], 201);
    }
}
