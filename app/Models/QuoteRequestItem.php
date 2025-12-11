<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteRequestItem extends Model
{
    protected $fillable = [
        'quote_request_id',
        'product_id',
        'product_variant_id',
        'quantity',
        'list_price',
        'requested_price',
        'offered_price',
    ];

    protected $casts = [
        'quantity'        => 'float',
        'list_price'      => 'float',
        'requested_price' => 'float',
        'offered_price'   => 'float',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class, 'quote_request_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
