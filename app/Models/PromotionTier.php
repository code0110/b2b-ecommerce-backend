<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionTier extends Model
{
    protected $fillable = [
        'promotion_id',
        'min_qty',
        'value',
        'settings',
    ];

    protected $casts = [
        'value' => 'float',
        'settings' => 'array',
    ];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }
}
