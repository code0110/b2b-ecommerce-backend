<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Customer extends Model
{
    protected $fillable = [
        'type',
        'name',
        'legal_name',
        'cif',
        'reg_com',
        'iban',
        'email',
        'phone',
        'group_id',
        'payment_terms_days',
        'credit_limit',
        'current_balance',
        'currency',
        'is_active',
        'is_partner',
    ];

    protected $casts = [
        'payment_terms_days' => 'integer',
        'credit_limit' => 'float',
        'current_balance' => 'float',
        'is_active' => 'boolean',
        'is_partner' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class, 'group_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_user_id');
    }

    public function salesDirector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_director_user_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
