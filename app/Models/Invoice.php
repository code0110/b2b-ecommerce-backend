<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'order_id',
        'erp_id',
        'type',
        'series',
        'number',
        'status',
        'issue_date',
        'due_date',
        'subtotal',
        'tax_total',
        'total',
        'currency',
        'pdf_url',
    ];

    protected $appends = ['balance'];

    protected $casts = [
        'issue_date' => 'date',
        'due_date'   => 'date',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function payments(): BelongsToMany
    {
        return $this->belongsToMany(Payment::class, 'payment_invoices')
            ->withPivot('amount')
            ->withTimestamps();
    }

    public function getBalanceAttribute()
    {
        return $this->total - $this->payments()->sum('payment_invoices.amount');
    }
}
