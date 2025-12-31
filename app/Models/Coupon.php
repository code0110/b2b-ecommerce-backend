<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'usage_limit',
        'usage_limit_per_user',
        'min_cart_value',
        'start_at',
        'end_at',
        'is_active',
        'is_stackable',
    ];

    protected $casts = [
        'discount_value' => 'float',
        'usage_limit' => 'integer',
        'usage_limit_per_user' => 'integer',
        'min_cart_value' => 'float',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_active' => 'boolean',
        'is_stackable' => 'boolean',
    ];

    public function isValid(): bool
    {
        if (!$this->is_active) return false;
        $now = now();
        if ($this->start_at && $now->lt($this->start_at)) return false;
        if ($this->end_at && $now->gt($this->end_at)) return false;
        if ($this->usage_limit && $this->usage_limit <= 0) return false; // This requires decrementing logic elsewhere

        return true;
    }
}
