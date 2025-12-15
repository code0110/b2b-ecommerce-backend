<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['customer', 'order'])
            ->orderByDesc('payment_date');

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if ($customerId = $request->get('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        return response()->json($query->paginate(20));
    }

    public function show(Payment $payment)
    {
        $payment->load(['customer', 'order']);

        return response()->json($payment);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'order_id'    => 'nullable|exists:orders,id',
            'type'        => 'required|in:chs,bo,cec,card,op',
            'channel'     => 'nullable|string|max:50',
            'amount'      => 'required|numeric',
            'currency'    => 'required|string|max:10',
            'status'      => 'required|string|max:50',
            'payment_date'=> 'nullable|date',
            'document_number' => 'nullable|string|max:100',
            'notes'       => 'nullable|string',
        ]);

        $payment = Payment::create($data);

        return response()->json($payment, 201);
    }

    public function update(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'status'       => 'sometimes|string|max:50',
            'payment_date' => 'nullable|date',
            'notes'        => 'nullable|string',
        ]);

        $payment->update($data);

        return response()->json($payment);
    }
}
