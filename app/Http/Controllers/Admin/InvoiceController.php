<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['customer', 'order'])
            ->orderByDesc('issue_date');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($customerId = $request->get('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        return response()->json($query->paginate(20));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['customer', 'order']);

        return response()->json($invoice);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'order_id'    => 'nullable|exists:orders,id',
            'type'        => 'required|string|max:20',
            'series'      => 'nullable|string|max:20',
            'number'      => 'nullable|string|max:50',
            'status'      => 'required|string|max:20',
            'issue_date'  => 'nullable|date',
            'due_date'    => 'nullable|date',
            'subtotal'    => 'required|numeric',
            'tax_total'   => 'required|numeric',
            'total'       => 'required|numeric',
            'currency'    => 'required|string|max:10',
            'pdf_url'     => 'nullable|string|max:255',
        ]);

        $invoice = Invoice::create($data);

        return response()->json($invoice, 201);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'status'     => 'sometimes|string|max:20',
            'due_date'   => 'nullable|date',
            'pdf_url'    => 'nullable|string|max:255',
        ]);

        $invoice->update($data);

        return response()->json($invoice);
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return response()->json(['message' => 'Invoice deleted']);
    }
}
