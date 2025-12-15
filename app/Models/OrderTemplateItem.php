<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderTemplateItem extends Model
{
    protected $fillable = [
        'order_template_id',
        'product_id',
        'product_variant_id',
        'quantity',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(OrderTemplate::class, 'order_template_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
