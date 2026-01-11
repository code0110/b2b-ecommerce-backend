<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Invoice;
use Illuminate\Http\Request;

class AccountDashboardController extends Controller
{
    public function overview(Request $request)
    {
        $user = $request->user();
        $customer = $user->customer;

        $ordersQuery = Order::with('items.product.images')
            ->where('customer_id', $customer?->id)
            ->orderByDesc('created_at');

        $recentOrders = $ordersQuery->take(5)->get();

        $totalOrders = (clone $ordersQuery)->count();
        $openOrders  = (clone $ordersQuery)->whereIn('status', ['pending', 'processing'])->count();

        $invoicesQuery = Invoice::where('customer_id', $customer?->id);
        $recentInvoices = $invoicesQuery->orderByDesc('issue_date')->take(5)->get();

        return response()->json([
            'user' => [
                'id'         => $user->id,
                'name'       => $user->full_name,
                'email'      => $user->email,
                'customerId' => $customer?->id,
            ],
            'customer' => $customer ? [
                'id'            => $customer->id,
                'name'          => $customer->name,
                'type'          => $customer->type,
                'credit_limit'  => $customer->credit_limit,
                'current_sold'  => $customer->current_balance,
                'payment_terms' => $customer->payment_terms,
            ] : null,
            'orders' => [
                'total'         => $totalOrders,
                'open'          => $openOrders,
                'recent'        => $recentOrders,
            ],
            'invoices' => [
                'recent' => $recentInvoices,
            ],
        ]);
    }
}
