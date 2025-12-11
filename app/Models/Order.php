<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'customer_id',
        'placed_by_user_id',
        'status',
        'type',
        'total_items',
        'subtotal',
        'discount_total',
        'tax_total',
        'shipping_total',
        'grand_total',
        'currency',
        'payment_method',
        'payment_status',
        'shipping_method_id',
        'billing_address_id',
        'shipping_address_id',
        'credit_blocked',
        'placed_at',
        'due_date',
    ];

    protected $casts = [
        'total_items' => 'integer',
        'subtotal' => 'float',
        'discount_total' => 'float',
        'tax_total' => 'float',
        'shipping_total' => 'float',
        'grand_total' => 'float',
        'credit_blocked' => 'boolean',
        'placed_at' => 'datetime',
        'due_date' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function placedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'placed_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }
}
