<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Invoice::where('customer_id', $user->customer_id);

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $query->orderByDesc('issue_date')->orderByDesc('id');

        return $query->paginate(20);
    }

    public function show($id, Request $request)
    {
        $user = $request->user();

        $invoice = Invoice::where('customer_id', $user->customer_id)
            ->where('id', $id)
            ->with('order')
            ->firstOrFail();

        return $invoice;
    }
}
