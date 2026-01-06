<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'customer_id',
        'director_id',
        'is_active',
        'notification_preferences',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
        'notification_preferences' => 'array',
    ];





    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'placed_by_user_id');
    }




public function hasAnyRole(array $codes): bool
{
    return $this->roles()->whereIn('code', $codes)->exists();
}

public function permissions()
{
    return \App\Models\Permission::whereHas('roles', function ($q) {
        $q->whereIn('roles.id', $this->roles()->pluck('roles.id'));
    })->get();
}

public function customer()
{
    return $this->belongsTo(\App\Models\Customer::class);
}

public function isCompanyOwner(): bool
{
    return $this->company_role === 'owner';
}

public function canApproveCompanyOrders(): bool
{
    return in_array($this->company_role, ['owner', 'approver'], true);
}

public function requiresOrderApproval(): bool
{
    return (bool) $this->requires_approval;
}


public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function targets()
    {
        return $this->hasMany(SalesTarget::class);
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;

        return $this->roles()
            ->whereIn('slug', $roles)
            ->exists();
    }

    public function getRoleNames()
    {
        return $this->roles->pluck('name');
    }

    public function scopeRole($query, $roles)
    {
        $roles = (array) $roles;
        return $query->whereHas('roles', function ($q) use ($roles) {
            $q->whereIn('slug', $roles);
        });
    }

    public function managedCustomers(): HasMany
    {
        return $this->hasMany(Customer::class, 'agent_user_id');
    }

    /**
     * Customers assigned to this user as a team member (secondary).
     */
    public function teamCustomers()
    {
        return $this->belongsToMany(Customer::class, 'agent_customer', 'agent_id', 'customer_id')
                    ->withTimestamps();
    }

    public function directedCustomers(): HasMany
    {
        return $this->hasMany(Customer::class, 'sales_director_user_id');
    }

    public function director(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'director_id');
    }

    public function routes(): HasMany
    {
        return $this->hasMany(AgentRoute::class, 'agent_id');
    }

    public function visits(): HasMany
    {
        return $this->hasMany(CustomerVisit::class, 'agent_id');
    }
}
