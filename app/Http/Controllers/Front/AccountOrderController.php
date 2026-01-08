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
        $userCustomer = $user->customer;

        $hasAccess = false;

        // 1. Clientul propriu-zis
        if ($userCustomer && $order->customer_id === $userCustomer->id) {
            $hasAccess = true;
        }

        // 2. Director de vânzări (are dreptul să vadă comenzile pentru aprobare)
        if (!$hasAccess && $user->hasRole('sales_director')) {
            $orderCustomer = $order->customer;
            if ($orderCustomer) {
                $subordinateIds = \App\Models\User::where('director_id', $user->id)->pluck('id')->toArray();
                $isDirectClient = $orderCustomer->sales_director_user_id == $user->id;
                $isSubordinateClient = in_array($orderCustomer->agent_user_id, $subordinateIds);
                
                if ($isDirectClient || $isSubordinateClient) {
                    $hasAccess = true;
                }
            }
        }

        if (!$hasAccess) {
            abort(403);
        }

        $order->load([
            'items.product',
            'items.variant',
            'shippingMethod',
            'billingAddress',
            'shippingAddress',
            'customer',
        ]);

        return response()->json($order);
    }
}
