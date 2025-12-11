<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRule extends Model
{
    protected $fillable = [
        'shipping_method_id',
        'min_weight',
        'max_weight',
        'min_value',
        'max_value',
        'region',
        'county',
        'city',
        'price',
        'free_over',
    ];

    protected $casts = [
        'min_weight' => 'float',
        'max_weight' => 'float',
        'min_value' => 'float',
        'max_value' => 'float',
        'price' => 'float',
        'free_over' => 'float',
    ];

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }
}
