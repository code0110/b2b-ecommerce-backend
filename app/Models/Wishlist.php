<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'token',
        'is_public',
        'is_default'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(WishlistItem::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'wishlist_items')
                    ->withPivot('quantity', 'created_at')
                    ->withTimestamps();
    }
}
