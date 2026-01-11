<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductUnit extends Model
{
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'name',
        'unit',
        'conversion_factor',
        'is_default',
        'is_base',
        'price_calculation_mode',
        'specific_price',
    ];

    protected $casts = [
        'conversion_factor' => 'float',
        'is_default' => 'boolean',
        'is_base' => 'boolean',
        'specific_price' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
