<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'barcode',
        'erp_id',
        'slug',
        'list_price',
        'price_override',
        'stock_qty',
        'stock_status',
    ];

    protected $casts = [
        'list_price' => 'float',
        'price_override' => 'float',
        'stock_qty' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function attributes(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }
}
