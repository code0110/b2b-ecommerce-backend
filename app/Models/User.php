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
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    public function hasAnyRole(array $slugs): bool
    {
        return $this->roles->whereIn('slug', $slugs)->count() > 0;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'placed_by_user_id');
    }

    public function roles()
{
    return $this->belongsToMany(\App\Models\Role::class, 'role_user');
}

public function hasRole(string $code): bool
{
    return $this->roles()->where('code', $code)->exists();
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

}
