<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Listă de comenzi cu filtre & paginare.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Order::query()
            ->with(['customer']);

        // RBAC Filtering
        if ($user->hasRole('sales_agent')) {
            $query->whereHas('customer', function ($q) use ($user) {
                $q->where('agent_user_id', $user->id)
                  ->orWhereHas('teamMembers', function ($sq) use ($user) {
                      $sq->where('users.id', $user->id);
                  });
            });
        } elseif ($user->hasRole('sales_director')) {
            $subordinateIds = \App\Models\User::where('director_id', $user->id)->pluck('id');
            $query->whereHas('customer', function ($q) use ($user, $subordinateIds) {
                $q->where('sales_director_user_id', $user->id)
                  ->orWhereIn('agent_user_id', $subordinateIds);
            });
        }

        // Filtre
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($paymentStatus = $request->query('payment_status')) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($orderNo = $request->query('order_number')) {
            $query->where('order_number', 'like', '%' . $orderNo . '%');
        }

        if ($customer = $request->query('customer')) {
            $query->whereHas('customer', function ($q) use ($customer) {
                $q->where('name', 'like', '%' . $customer . '%')
                  ->orWhere('email', 'like', '%' . $customer . '%')
                  ->orWhere('legal_name', 'like', '%' . $customer . '%');
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->query('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->query('to_date'));
        }

        if ($request->filled('credit_blocked')) {
            $query->where('credit_blocked', (bool) $request->query('credit_blocked'));
        }

        // Sortare: cele mai noi primele
        $query->orderByDesc('placed_at')->orderByDesc('created_at');

        $perPage = (int) $request->query('per_page', 20);
        $orders  = $query->paginate($perPage);

        // Transformare pentru UI admin
        $orders->getCollection()->transform(function (Order $order) {
            return [
                'id'              => $order->id,
                'order_number'    => $order->order_number,
                'type'            => $order->type,
                'status'          => $order->status,
                'payment_status'  => $order->payment_status,
                'approval_status' => $order->approval_status,
                'total_items'     => (int) $order->total_items,
                'grand_total'     => (float) $order->grand_total,
                'credit_blocked'  => (bool) $order->credit_blocked,
                'placed_at'       => optional($order->placed_at)->toDateTimeString(),
                'created_at'      => optional($order->created_at)->toDateTimeString(),
                'customer'        => $order->customer ? [
                    'id'    => $order->customer->id,
                    'name'  => $order->customer->name,
                    'email' => $order->customer->email,
                    'type'  => $order->customer->type,
                ] : null,
            ];
        });

        return response()->json($orders);
    }

    /**
     * Detaliu comandă – info generală + items + adrese.
     */
    public function show(Order $order)
    {
        $order->load(['customer', 'items']);

        $billingAddress  = null;
        $shippingAddress = null;

        if ($order->billing_address_id) {
            $billingAddress = DB::table('addresses')->where('id', $order->billing_address_id)->first();
        }

        if ($order->shipping_address_id) {
            $shippingAddress = DB::table('addresses')->where('id', $order->shipping_address_id)->first();
        }

        $items = $order->items->map(function (OrderItem $item) {
            return [
                'id'              => $item->id,
                'product_id'      => $item->product_id,
                'product_name'    => $item->product_name,
                'sku'             => $item->sku,
                'quantity'        => (int) $item->quantity,
                'unit_price'      => (float) $item->unit_price,
                'discount_amount' => (float) $item->discount_amount,
                'tax_amount'      => (float) $item->tax_amount,
                'total'           => (float) $item->total,
            ];
        })->values();

        return response()->json([
            'order' => [
                'id'              => $order->id,
                'order_number'    => $order->order_number,
                'status'          => $order->status,
                'payment_status'  => $order->payment_status,
                'approval_status' => $order->approval_status,
                'type'            => $order->type,
                'total_items'     => (int) $order->total_items,
                'subtotal'        => (float) $order->subtotal,
                'discount_total'  => (float) $order->discount_total,
                'tax_total'       => (float) $order->tax_total,
                'shipping_total'  => (float) $order->shipping_total,
                'grand_total'     => (float) $order->grand_total,
                'payment_method'  => $order->payment_method,
                'credit_blocked'  => (bool) $order->credit_blocked,
                'placed_at'       => optional($order->placed_at)->toDateTimeString(),
                'cancelled_at'    => optional($order->cancelled_at)->toDateTimeString(),
                'cancel_reason'   => $order->cancel_reason,
                'due_date'        => optional($order->due_date)->toDateTimeString(),
                'created_at'      => optional($order->created_at)->toDateTimeString(),
                'updated_at'      => optional($order->updated_at)->toDateTimeString(),
            ],
            'customer'        => $order->customer ? [
                'id'                => $order->customer->id,
                'name'              => $order->customer->name,
                'legal_name'        => $order->customer->legal_name,
                'email'             => $order->customer->email,
                'phone'             => $order->customer->phone,
                'type'              => $order->customer->type,
                'payment_terms_days'=> (int) $order->customer->payment_terms_days,
                'credit_limit'      => (float) $order->customer->credit_limit,
                'current_balance'   => (float) $order->customer->current_balance,
            ] : null,
            'billing_address' => $billingAddress,
            'shipping_address'=> $shippingAddress,
            'items'           => $items,
        ]);
    }

    /**
     * Actualizare câmpuri generale (fără status / payment_status).
     */
    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'cancel_reason'  => ['nullable', 'string', 'max:255'],
            'due_date'       => ['nullable', 'date'],
            'credit_blocked' => ['nullable', 'boolean'],
            'payment_method' => ['nullable', 'string', 'max:50'],
        ]);

        $order->fill($data);
        $order->save();

        return $this->show($order->fresh());
    }

    /**
     * Schimbare status comandă.
     */
    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status'        => ['required', 'string', 'in:pending,processing,completed,cancelled,awaiting_payment,on_hold'],
            'cancel_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $order->status = $data['status'];

        if ($data['status'] === 'cancelled') {
            $order->cancel_reason = $data['cancel_reason'] ?? $order->cancel_reason;
            $order->cancelled_at  = now();
        }

        $order->save();

        return $this->show($order->fresh());
    }

    /**
     * Schimbare status plată.
     */
    public function updatePaymentStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'payment_status' => ['required', 'string', 'in:pending,paid,failed,refunded,partially_paid'],
            'payment_method' => ['nullable', 'string', 'max:50'],
        ]);

        $order->payment_status = $data['payment_status'];

        if (!empty($data['payment_method'])) {
            $order->payment_method = $data['payment_method'];
        }

        $order->save();

        return $this->show($order->fresh());
    }
}
