<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'priority',
        'stacking_type',
        'bonus_type',       // gratuitate / value / percent etc.
        'trigger_type',
        'trigger_payload',
        'benefit_type',
        'benefit_payload',
        'min_cart_total',
        'min_qty_per_product',
        'customer_type',    // b2b / b2c / both
        'logged_in_only',
        'applies_to',
        'discount_percent',
        'discount_value',   // <--- presupun: numeric
    ];

    protected $casts = [
        'start_at'          => 'datetime',
        'end_at'            => 'datetime',
        'is_exclusive'      => 'boolean',
        'is_iterative'      => 'boolean',
        'priority'          => 'integer',
        'trigger_payload'   => 'array',
        'benefit_payload'   => 'array',
        'logged_in_only'    => 'boolean',
        'min_cart_total'    => 'float',
        'min_qty_per_product' => 'integer',
        'discount_percent'  => 'float',
        'discount_value'    => 'float',
    ];

    /* ==== Relații simple pentru segmentare ==== */

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'promotion_product');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'promotion_category');
    }

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(Brand::class, 'promotion_brand');
    }

    public function customerGroups(): BelongsToMany
    {
        return $this->belongsToMany(CustomerGroup::class, 'promotion_customer_group');
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'promotion_customer');
    }

    /* ==== Scope: promoții active acum ==== */
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

    /* ==== Verifică dacă promoția se aplică pe un anumit client ==== */
    public function appliesToCustomer(?User $user): bool
    {
        if ($this->logged_in_only && !$user) {
            return false;
        }

        if ($this->customer_type && $this->customer_type !== 'both') {
            if (!$user) {
                // If promotion is B2B, guests (assumed B2C) don't qualify.
                if ($this->customer_type === 'b2b') {
                    return false;
                }
            } else {
                // Check the user's customer type
                $customer = $user->customer;
                if ($customer) {
                    if ($this->customer_type === 'b2b' && $customer->type !== 'b2b') return false;
                    if ($this->customer_type === 'b2c' && $customer->type !== 'b2c') return false;
                } else {
                     // User has no customer linked? Treat as B2C or check role?
                     // Usually internal users (admin/agent) don't have customer linked, so this might block them.
                     // But this method is for "does this promotion apply to THIS user as a buyer?".
                     // If user is admin/agent buying for themselves? 
                     // Let's assume if no customer linked, they are generic/B2C.
                     if ($this->customer_type === 'b2b') return false;
                }
            }
        }

        if ($user) {
            if ($this->customers()->exists()) {
                if (!$this->customers()->where('users.id', $user->id)->exists()) {
                    return false;
                }
            }

            if ($this->customerGroups()->exists()) {
                $groupId = $user->customer_group_id ?? null;
                // If user has no group directly on model, check via customer relation
                if (!$groupId && $user->customer) {
                    $groupId = $user->customer->group_id;
                }

                if (!$groupId) {
                    return false;
                }

                if (!$this->customerGroups()->where('customer_groups.id', $groupId)->exists()) {
                    return false;
                }
            }
        }

        return true;
    }

    /* ==== Verifică dacă promoția se aplică pe un produs ==== */
    public function appliesToProduct(Product $product): bool
    {
        if ($this->products()->exists() &&
            !$this->products()->where('products.id', $product->id)->exists()) {
            return false;
        }

        if ($this->brands()->exists()) {
            if (!$product->brand_id) {
                return false;
            }

            if (!$this->brands()->where('brands.id', $product->brand_id)->exists()) {
                return false;
            }
        }

        if ($this->categories()->exists()) {
            $categoryIds = collect([
                $product->main_category_id,
                ...($product->categories()->pluck('categories.id')->all() ?? []),
            ])->filter()->unique();

            if ($categoryIds->isEmpty()) {
                return false;
            }

            if (!$this->categories()->whereIn('categories.id', $categoryIds)->exists()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculează discount-ul pentru o linie simplă
     * în funcție de discount_type și discount_value.
     *
     * Returnează:
     *  [
     *    'discount_amount' => float,
     *    'final_unit_price' => float,
     *  ]
     */
    public function calculateLineDiscount(float $unitPrice, int $qty): array
    {
        if (!$this->discount_type || !$this->discount_value) {
            return [
                'discount_amount'   => 0.0,
                'final_unit_price'  => $unitPrice,
            ];
        }

        $discountPerUnit = 0.0;

        if ($this->discount_type === 'percent') {
            $discountPerUnit = $unitPrice * ($this->discount_value / 100);
        } elseif ($this->discount_type === 'fixed') {
            $discountPerUnit = $this->discount_value;
        }

        // asigurare să nu fie negativ
        $finalUnitPrice = max(0, $unitPrice - $discountPerUnit);

        return [
            'discount_amount'   => $discountPerUnit * $qty,
            'final_unit_price'  => $finalUnitPrice,
        ];
    }
}
