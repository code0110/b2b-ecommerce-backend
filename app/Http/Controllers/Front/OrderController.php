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
        $customerId = $user->customer_id;

        if (!$customerId && ($user->hasRole('sales_agent') || $user->hasRole('sales_director') || $user->hasRole('admin'))) {
             $customerId = $request->input('customer_id') ?? $request->header('X-Customer-Id');
        }

        if (!$customerId) {
            return response()->json(['data' => []]);
        }

        return Order::where('customer_id', $customerId)
            ->orderByDesc('placed_at')
            ->paginate(20);
    }

    public function show($id, Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $customerId = $user->customer_id;

        if (!$customerId && ($user->hasRole('sales_agent') || $user->hasRole('sales_director') || $user->hasRole('admin'))) {
             $customerId = $request->input('customer_id') ?? $request->header('X-Customer-Id');
        }
        
        if (!$customerId) {
            abort(403, 'Context client lipsă.');
        }

        $order = Order::where('customer_id', $customerId)
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
