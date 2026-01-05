<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Payment extends Model
{
    protected $fillable = [
        'customer_id',
        'order_id',
        'recorded_by_user_id',
        'receipt_book_id',
        'type',
        'channel',
        'amount',
        'currency',
        'status',
        'payment_date',
        'document_number',
        'series',
        'number',
        'bank',
        'due_date',
        'notes',
        'customer_visit_id',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'due_date' => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function receiptBook(): BelongsTo
    {
        return $this->belongsTo(ReceiptBook::class);
    }

    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class, 'payment_invoices')
            ->withPivot('amount')
            ->withTimestamps();
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(CustomerVisit::class, 'customer_visit_id');
    }
}
