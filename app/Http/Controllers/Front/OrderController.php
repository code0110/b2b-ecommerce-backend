<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return Order::where('customer_id', $user->customer_id)
            ->orderByDesc('placed_at')
            ->paginate(20);
    }

    public function show($id, Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $order = Order::where('customer_id', $user->customer_id)
            ->where('id', $id)
            ->with('items.product')
            ->firstOrFail();

        return $order;
    }

    public function reorder($id, Request $request)
    {
        // Pentru început, doar întoarcem comanda cerută ca "template".
        $order = Order::with('items.product')->findOrFail($id);

        return response()->json([
            'order' => $order,
            'message' => 'Funcționalitatea de „Comandă din nou” poate recrea coșul pe baza acestei comenzi.',
        ]);
    }
}
