<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $customer = $request->user()->customer;

        $query = Invoice::where('customer_id', $customer?->id)
            ->orderByDesc('issue_date');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $invoices = $query->paginate(10);

        return response()->json($invoices);
    }

    public function show(Request $request, Invoice $invoice)
    {
        $customer = $request->user()->customer;

        if ($invoice->customer_id !== $customer?->id) {
            abort(403);
        }

        return response()->json($invoice);
    }
}
