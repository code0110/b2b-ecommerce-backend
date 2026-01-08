<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'internal_code',
        'barcode',
        'erp_id',
        'short_description',
        'long_description',
        'main_category_id',
        'brand_id',
        'status',
        'sort_order',
        'list_price',
        'rrp_price',
        'vat_rate',
        'price_override',
        'stock_status',
        'stock_qty',
        'supplier_stock_qty',
        'lead_time_days',
        'is_new',
        'is_promo',
        'is_best_seller',
        'type',
        'visibility',
        'tags',
        'key_benefits',
        'technical_specs',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'video_url',
        'vat_included',
        'currency',
        'min_stock_limit',
        'allow_backorder',
        'overstock_policy',
        'estimated_delivery_text',
        'unit_of_measure',
        'min_order_quantity',
        'order_quantity_step',
        'requires_quote',
        'erp_sync_status',
        'erp_last_sync_at',
    ];

    protected $casts = [
        'list_price'          => 'float',
        'rrp_price'           => 'float',
        'vat_rate'            => 'float',
        'price_override'      => 'float',
        'stock_qty'           => 'integer',
        'supplier_stock_qty'  => 'integer',
        'lead_time_days'      => 'integer',
        'is_new'              => 'boolean',
        'is_promo'            => 'boolean',
        'is_best_seller'      => 'boolean',
        'tags'                => 'array',
        'key_benefits'        => 'array',
        'technical_specs'     => 'array',
        'vat_included'        => 'boolean',
        'allow_backorder'     => 'boolean',
        'requires_quote'      => 'boolean',
        'erp_last_sync_at'    => 'datetime',
    ];

    /* ---------- RELAȚII ---------- */

    public function units(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProductDocument::class);
    }

    public function mainCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'main_category_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }

    /**
     * Produse asociate (relație pivot simplă)
     * – „similar”, „cross_sell”, „up_sell”
     */
    public function relatedProducts(): HasMany
    {
        return $this->hasMany(RelatedProduct::class);
    }

    public function complementaryProducts(): HasMany
    {
        return $this->hasMany(RelatedProduct::class)->where('type', 'complementary');
    }

    public function upsellProducts(): HasMany
    {
        return $this->hasMany(RelatedProduct::class)->where('type', 'upsell');
    }

    /**
     * Alias pentru compatibilitate dacă undeva în cod se folosește `related()`.
     */
    public function related(): HasMany
    {
        return $this->relatedProducts();
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
