<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rule_type',
        'target_type',
        'target_id',
        'limit_percent',
        'priority',
        'is_exclusive',
        'apply_to_total',
        'active'
    ];

    protected $casts = [
        'limit_percent' => 'decimal:2',
        'is_exclusive' => 'boolean',
        'apply_to_total' => 'boolean',
        'active' => 'boolean',
    ];
}
