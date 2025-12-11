<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with('customer', 'order');

        if ($customerId = $request->get('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($orderId = $request->get('order_id')) {
            $query->where('order_id', $orderId);
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return $query->orderByDesc('issue_date')->orderByDesc('id')->paginate(50);
    }

    public function show(Invoice $invoice)
    {
        return $invoice->load('customer', 'order');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'order_id'    => ['nullable', 'integer', 'exists:orders,id'],
            'type'        => ['required', 'string', 'max:20'],
            'series'      => ['nullable', 'string', 'max:20'],
            'number'      => ['nullable', 'string', 'max:50'],
            'status'      => ['required', 'string', 'max:20'],
            'issue_date'  => ['nullable', 'date'],
            'due_date'    => ['nullable', 'date'],
            'subtotal'    => ['nullable', 'numeric'],
            'tax_total'   => ['nullable', 'numeric'],
            'total'       => ['nullable', 'numeric'],
            'currency'    => ['nullable', 'string', 'max:10'],
            'pdf_url'     => ['nullable', 'string'],
            'meta'        => ['nullable', 'array'],
        ]);

        $invoice = Invoice::create($data);

        return response()->json($invoice->load('customer', 'order'), 201);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'status'     => ['sometimes', 'string', 'max:20'],
            'due_date'   => ['nullable', 'date'],
            'pdf_url'    => ['nullable', 'string'],
            'meta'       => ['nullable', 'array'],
        ]);

        $invoice->update($data);

        return response()->json($invoice->load('customer', 'order'));
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
