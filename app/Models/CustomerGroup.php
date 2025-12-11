<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerGroup extends Model
{
    protected $fillable = [
        'name',
        'type',
        'default_discount_percent',
        'default_payment_terms_days',
        'default_credit_limit',
    ];

    protected $casts = [
        'default_discount_percent' => 'float',
        'default_payment_terms_days' => 'integer',
        'default_credit_limit' => 'float',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
