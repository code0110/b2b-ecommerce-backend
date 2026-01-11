<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        'latitude',
        'longitude',
        'agent_user_id',
        'sales_director_user_id',
        'financial_derogation_until',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_active' => 'boolean',
        'is_partner' => 'boolean',
        'credit_limit' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'financial_derogation_until' => 'datetime',
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

    /**
     * The secondary agents assigned to this customer (Team).
     */
    public function teamMembers()
    {
        return $this->belongsToMany(User::class, 'agent_customer', 'customer_id', 'agent_id')
                    ->withTimestamps();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function visits(): HasMany
    {
        return $this->hasMany(CustomerVisit::class);
    }

    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'customer_promotion');
    }

    public function scopeVisibleTo($query, $user)
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        if ($user->hasRole('sales_director')) {
            // Directorul vede clienții asignați direct LUI sau clienții asignați agenților din subordine
            $subordinateIds = User::where('director_id', $user->id)->pluck('id');

            return $query->where(function($q) use ($user, $subordinateIds) {
                $q->where('sales_director_user_id', $user->id)
                  ->orWhereIn('agent_user_id', $subordinateIds);
            });
        }

        if ($user->hasRole('sales_agent')) {
            return $query->where(function ($q) use ($user) {
                $q->where('agent_user_id', $user->id)
                  ->orWhereHas('teamMembers', function ($subQ) use ($user) {
                      $subQ->where('users.id', $user->id);
                  });
            });
        }

        return $query;
    }
}
