<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $fillable = [
        'order_id',
        'courier',
        'awb_number',
        'label_url',
        'tracking_url',
        'status',
        'raw_payload',
        'is_active',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'is_active'   => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
