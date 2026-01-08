<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use App\Models\Product;
use App\Models\User;

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
        'stacking_type',
        'type',             // standard, volume, bundle, shipping, special_price, gift
        'value_type',       // percent, fixed_amount, fixed_price
        'value',
        'settings',
        'conditions',
        'min_cart_total',
        'min_qty_per_product',
        'usage_limit',
        'per_customer_usage_limit',
        'uses',
        'customer_type',    // b2b / b2c / both
        'logged_in_only',
    ];

    protected $casts = [
        'start_at'          => 'datetime',
        'end_at'            => 'datetime',
        'is_exclusive'      => 'boolean',
        'is_iterative'      => 'boolean',
        'logged_in_only'    => 'boolean',
        'min_cart_total'    => 'float',
        'min_qty_per_product' => 'integer',
        'usage_limit'       => 'integer',
        'per_customer_usage_limit' => 'integer',
        'uses'              => 'integer',
        'value'             => 'float',
        'settings'          => 'array',
        'conditions'        => 'array',
    ];

    /* ==== Relationships ==== */

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_promotion');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_promotion');
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'brand_promotion');
    }

    public function customerGroups(): BelongsToMany
    {
        return $this->belongsToMany(CustomerGroup::class, 'customer_group_promotion');
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_promotion');
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(PromotionTier::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    /* ==== Scope: Active Promotions ==== */
    public function scopeActive(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query->where('status', 'active')
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')
                  ->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')
                  ->orWhere('end_at', '>=', $now);
            });
    }
}
