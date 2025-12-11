<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function overview(Request $request)
    {
        $days = (int) $request->get('days', 7);
        $from = now()->subDays($days);

        // Comenzi noi / în derulare (în ultimele X zile)
        $newOrders = Order::where('created_at', '>=', $from)
            ->whereIn('status', ['pending', 'processing'])
            ->count();

        $totalSales = Order::where('created_at', '>=', $from)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->sum('grand_total');

        $pendingApproval = Order::where('approval_status', 'pending')->count();

        // Top clienți după total comenzi
        $topCustomers = Order::select('customer_id', DB::raw('SUM(grand_total) as total_amount'), DB::raw('COUNT(*) as orders_count'))
            ->where('created_at', '>=', $from)
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->groupBy('customer_id')
            ->orderByDesc('total_amount')
            ->with('customer')
            ->limit(5)
            ->get();

        // Top produse (după cantitate comandată)
        $topProducts = DB::table('order_items')
            ->select('product_id', DB::raw('SUM(quantity) as total_qty'), DB::raw('SUM(total) as total_amount'))
            ->where('created_at', '>=', $from)
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        $topProductsModels = Product::whereIn('id', $topProducts->pluck('product_id'))->get()->keyBy('id');
        $topProducts = $topProducts->map(function ($row) use ($topProductsModels) {
            $product = $topProductsModels->get($row->product_id);
            return [
                'product_id'    => $row->product_id,
                'product_name'  => $product?->name,
                'internal_code' => $product?->internal_code,
                'total_qty'     => (float) $row->total_qty,
                'total_amount'  => (float) $row->total_amount,
            ];
        });

        // Produse frecvent comandate B2B
        $frequentB2BProducts = DB::table('orders')
            ->join('order_items', 'orders.id', '=', 'order_items.order_id')
            ->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->where('customers.type', 'b2b')
            ->where('orders.created_at', '>=', $from)
            ->select('order_items.product_id', DB::raw('COUNT(DISTINCT orders.id) as orders_count'), DB::raw('SUM(order_items.quantity) as total_qty'))
            ->groupBy('order_items.product_id')
            ->orderByDesc('orders_count')
            ->limit(5)
            ->get();

        $frequentProductsModels = Product::whereIn('id', $frequentB2BProducts->pluck('product_id'))->get()->keyBy('id');
        $frequentB2BProducts = $frequentB2BProducts->map(function ($row) use ($frequentProductsModels) {
            $product = $frequentProductsModels->get($row->product_id);
            return [
                'product_id'    => $row->product_id,
                'product_name'  => $product?->name,
                'internal_code' => $product?->internal_code,
                'orders_count'  => (int) $row->orders_count,
                'total_qty'     => (float) $row->total_qty,
            ];
        });

        // Clienți cu credit depășit
        $creditExceeded = Customer::where('type', 'b2b')
            ->where('credit_limit', '>', 0)
            ->whereColumn('current_balance', '>', 'credit_limit')
            ->with('agent', 'salesDirector')
            ->get();

        // Notificări generice dashboard (număr comenzi noi, oferte noi, tichete noi)
        $newOrdersCount = Order::where('created_at', '>=', $from)->count();

        return response()->json([
            'summary' => [
                'days'              => $days,
                'new_orders'        => $newOrders,
                'pending_approval'  => $pendingApproval,
                'total_sales'       => $totalSales,
                'new_orders_count'  => $newOrdersCount,
            ],
            'top_customers'        => $topCustomers,
            'top_products'         => $topProducts,
            'frequent_b2b_products'=> $frequentB2BProducts,
            'credit_exceeded'      => $creditExceeded,
        ]);
    }
}
