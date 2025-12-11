<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Customer;
use App\Traits\RecordsAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    use RecordsAudit;

    public function index(Request $request)
    {
        $query = Payment::with(['customer', 'order', 'recordedBy']);

        if ($customerId = $request->get('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return $query->orderByDesc('payment_date')->paginate(25);
    }

    public function show(Payment $payment)
    {
        return $payment->load(['customer', 'order', 'recordedBy']);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'customer_id'     => ['required', 'integer', 'exists:customers,id'],
            'order_id'        => ['nullable', 'integer', 'exists:orders,id'],
            'type'            => ['required', 'in:chs,bo,cec'],
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'currency'        => ['nullable', 'string', 'max:10'],
            'payment_date'    => ['nullable', 'date'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'notes'           => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($data, $user) {
            $customer = Customer::findOrFail($data['customer_id']);
            $beforeBalance = $customer->current_balance ?? 0;

            $payment = Payment::create([
                'customer_id'         => $customer->id,
                'order_id'            => $data['order_id'] ?? null,
                'recorded_by_user_id' => $user->id,
                'type'                => $data['type'],
                'channel'             => 'offline',
                'amount'              => $data['amount'],
                'currency'            => $data['currency'] ?? 'RON',
                'status'              => 'confirmed',
                'payment_date'        => $data['payment_date'] ?? now(),
                'document_number'     => $data['document_number'] ?? null,
                'notes'               => $data['notes'] ?? null,
            ]);

            // actualizăm soldul clientului (scădem încasarea)
            $customer->current_balance = $beforeBalance - $payment->amount;
            $customer->save();

            // Audit – intervenție manuală asupra soldului / încasare
            $this->recordAudit(
                action: 'payment_recorded',
                entityType: 'Payment',
                entityId: $payment->id,
                changes: [
                    'customer_id'    => $customer->id,
                    'before_balance' => $beforeBalance,
                    'after_balance'  => $customer->current_balance,
                    'amount'         => $payment->amount,
                    'type'           => $payment->type,
                ],
                meta: [
                    'order_id'        => $payment->order_id,
                    'document_number' => $payment->document_number,
                ]
            );

            return response()->json($payment->load('customer'), 201);
        });
    }

    public function update(Request $request, Payment $payment)
    {
        $before = $payment->toArray();

        $data = $request->validate([
            'status' => ['sometimes', 'string', 'max:50'],
            'notes'  => ['nullable', 'string'],
        ]);

        $payment->update($data);

        // Audit – modificare status/notes în plată
        $this->recordAudit(
            action: 'payment_updated',
            entityType: 'Payment',
            entityId: $payment->id,
            changes: [
                'before' => array_intersect_key($before, array_flip(['status', 'notes'])),
                'after'  => $payment->only(['status', 'notes']),
            ],
            meta: []
        );

        return response()->json($payment);
    }
}
