<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SyncOrderToErp;
use App\Models\Order;
use App\Models\ErpSyncLog;
use Illuminate\Http\Request;

class ErpController extends Controller
{
    public function logs(Request $request)
    {
        $query = ErpSyncLog::query();

        if ($entity = $request->get('entity_type')) {
            $query->where('entity_type', $entity);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return $query->orderByDesc('created_at')->paginate(50);
    }

    public function syncOrder($orderId)
    {
        $order = Order::findOrFail($orderId);

        SyncOrderToErp::dispatch($order);

        return response()->json(['message' => 'Order sync job dispatched.']);
    }
}
