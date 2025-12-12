<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class AccountOrderController extends Controller
{
    /**
     * Listă comenzi pentru un client (B2B/B2C).
     *
     * Pentru simplificare în dev, primim user_id din query.
     * În producție, îl vei lua din $request->user().
     */
    public function index(Request $request)
    {
        $userId = (int) $request->input('user_id');

        if (!$userId) {
            return response()->json([
                'message' => 'user_id este obligatoriu pentru această rută (dev mode).',
            ], 400);
        }

        $query = Order::query()
            ->where('placed_by_user_id', $userId)
            ->orderByDesc('created_at');

        // filtre opționale
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($paymentStatus = $request->input('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);

        $orders = $query->paginate($perPage);

        return response()->json($orders);
    }

    /**
     * Detaliu de comandă pentru un client.
     */
    public function show(int $id, Request $request)
    {
        $userId = (int) $request->input('user_id');

        if (!$userId) {
            return response()->json([
                'message' => 'user_id este obligatoriu pentru această rută (dev mode).',
            ], 400);
        }

        $order = Order::query()
            ->with([
                'items.product',
                'invoices',
                'shipments',
            ])
            ->where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();

        return response()->json($order);
    }
}
