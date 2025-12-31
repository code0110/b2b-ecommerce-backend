<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductUnit extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'conversion_factor',
        'is_default',
        'price_calculation_mode',
        'specific_price',
    ];

    protected $casts = [
        'conversion_factor' => 'float',
        'is_default' => 'boolean',
        'specific_price' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
