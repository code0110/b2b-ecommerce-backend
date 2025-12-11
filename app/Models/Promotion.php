<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Promotion extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'description',
        'hero_image',
        'banner_image',
        'mobile_image',
        'start_at',
        'end_at',
        'status',
        'is_exclusive',
        'is_iterative',
        'bonus_type',
        'min_cart_total',
        'min_qty_per_product',
        'customer_type',
        'logged_in_only',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_exclusive' => 'boolean',
        'is_iterative' => 'boolean',
        'min_cart_total' => 'float',
        'min_qty_per_product' => 'integer',
        'logged_in_only' => 'boolean',
    ];

    public function customerGroups(): BelongsToMany
    {
        return $this->belongsToMany(CustomerGroup::class);
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
