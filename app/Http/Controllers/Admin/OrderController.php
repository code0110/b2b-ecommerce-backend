<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Listă comenzi (admin) cu filtre simple.
     * GET /api/admin/orders
     */
    public function index(Request $request)
    {
        $query = Order::query()
            ->with(['customer', 'placedBy'])
            ->orderByDesc('placed_at');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($paymentStatus = $request->get('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                    ->orWhere('order_number', 'like', '%' . $search . '%')
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $perPage = (int) $request->get('per_page', 20);

        return response()->json(
            $query->paginate($perPage)
        );
    }

    /**
     * Detaliu comandă (admin).
     * GET /api/admin/orders/{order}
     */
    public function show(Order $order)
    {
        $order->load(['customer', 'placedBy', 'items.product', 'shippingMethod']);

        return response()->json($order);
    }

    /**
     * Update general (note interne, due_date etc).
     * PUT /api/admin/orders/{order}
     */
    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'internal_notes' => ['nullable', 'string'],
            'due_date'       => ['nullable', 'date'],
        ]);

        $order->fill($data);
        $order->save();

        $order->load(['customer', 'placedBy']);

        return response()->json($order);
    }

    /**
     * Update status logistic / workflow (pending, processing, shipped etc).
     * POST /api/admin/orders/{order}/status
     */
    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => ['required', 'string'],
        ]);

        $order->status = $data['status'];
        $order->save();

        return response()->json([
            'message' => 'Statusul comenzii a fost actualizat.',
            'order'   => $order,
        ]);
    }

    /**
     * Update status plată (unpaid, pending, paid etc).
     * POST /api/admin/orders/{order}/payment-status
     */
    public function updatePaymentStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'payment_status' => ['required', 'string'],
        ]);

        $order->payment_status = $data['payment_status'];
        $order->save();

        return response()->json([
            'message' => 'Statusul plății a fost actualizat.',
            'order'   => $order,
        ]);
    }
}
