<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $ordersInProgress = Order::whereIn('status', ['pending', 'confirmed', 'awaiting_payment'])->count();
        $topCustomers     = Customer::orderByDesc('current_balance')->take(5)->get();
        $topProducts      = Product::orderByDesc('stock_qty')->take(5)->get();

        return response()->json([
            'orders_in_progress' => $ordersInProgress,
            'top_customers'      => $topCustomers,
            'top_products'       => $topProducts,
        ]);
    }
}
