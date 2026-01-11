<?php

namespace App\Services\Pricing;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promotion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class PromotionPricingService
{
    protected PromotionEngine $promotionEngine;

    public function __construct(PromotionEngine $promotionEngine)
    {
        $this->promotionEngine = $promotionEngine;
    }

    /**
     * Promoții active pentru un anumit client.
     * Wrapper over PromotionEngine.
     */
    public function getActivePromotionsForCustomer(?Customer $customer = null): Collection
    {
        // If we have a customer, we assume they are "authenticated" in the business sense
        // even if the actual HTTP user is an Admin.
        // However, PromotionEngine checks $user object for 'logged_in_only'.
        // We pass Auth::user() which is the current session user.
        return $this->promotionEngine->getActivePromotions(Auth::user(), $customer);
    }

    /**
     * Verifică dacă promoția se aplică pentru un anumit produs.
     */
    public function promotionAppliesToProduct(Promotion $promotion, Product $product): bool
    {
        return $this->promotionEngine->promotionAppliesToProduct($promotion, $product);
    }

    /**
     * Calculează prețul efectiv al unui produs (preț listă + promoții).
     */
    public function calculateProductPrice(Product $product, ?Customer $customer = null): array
    {
        $user = Auth::user();
        // Assume quantity 1 for unit price
        $pricing = $this->promotionEngine->getProductPriceWithPromotions($product, $user, $customer, 1);

        // Extract single applied promotion for backward compatibility
        $appliedPromotion = null;
        if (!empty($pricing['applied_promotions'])) {
            // Usually take the most significant one or the first one
            // PromotionEngine returns array of applied promos.
            $first = $pricing['applied_promotions'][0];
            $appliedPromotion = [
                'id'   => $first['id'],
                'name' => $first['name'],
                'slug' => $first['slug'],
            ];
        }

        $discountPercent = 0;
        if ($pricing['base_price'] > 0) {
            $discountPercent = round(($pricing['base_price'] - $pricing['final_price']) / $pricing['base_price'] * 100, 2);
        }

        return [
            'has_discount'      => $pricing['has_discount'],
            'price'             => $pricing['base_price'],
            'promo_price'       => $pricing['final_price'],
            'discount_percent'  => $discountPercent,
            'applied_promotion' => $appliedPromotion,
        ];
    }

    /**
     * Aplică o promoție pe un preț brut și returnează [promoPrice, discountPercent].
     * Used by QuickOrderController.
     */
    public function applyPromotionOnPrice(Promotion $promotion, float $basePrice): array
    {
        [$newPrice] = $this->promotionEngine->applyPromotionToUnit($promotion, $basePrice, 1);
        
        $discountPercent = 0;
        if ($basePrice > 0) {
            $discountPercent = round(($basePrice - $newPrice) / $basePrice * 100, 2);
        }

        return [$newPrice, $discountPercent];
    }

    /**
     * Prețul de bază al produsului.
     */
    public function getBasePrice(Product $product, ?Customer $customer = null): float
    {
        return $this->promotionEngine->getBasePrice($product, $customer);
    }

    /**
     * Returnează un array gata de trimis la frontend pentru un produs (sau variantă).
     */
    public function formatProductForFrontend(Product $product, ?Customer $customer = null, ?ProductVariant $variant = null): array
    {
        // 1. Calculate Price (Variant or Product)
        $pricing = $this->calculatePriceForEntity($product, $variant, $customer);

        // 2. Build Units List (Base + Secondary)
        $unitsList = $this->buildUnitsList($product, $variant, $pricing['price'], $pricing['promo_price']);

        // 3. Determine effective fields
        $effectiveName = $variant ? $variant->name : $product->name;
        $effectiveSlug = $variant ? $variant->slug : $product->slug;
        $effectiveCode = $variant ? $variant->sku : $product->internal_code;
        $listPrice     = $variant ? (float)$variant->list_price : (float)$product->list_price;
        $stockQty      = $variant ? (int)$variant->stock_qty : (int)$product->stock_qty;
        $stockStatus   = $variant ? $variant->stock_status : $product->stock_status;

        // 4. Variant Attributes (if selected)
        $selectedAttributes = [];
        if ($variant) {
            $selectedAttributes = collect($variant->attributes)->map(function ($av) {
                return [
                    'name'  => $av->attribute ? $av->attribute->name : 'Attribute',
                    'slug'  => $av->attribute ? $av->attribute->slug : '',
                    'value' => $av->value,
                ];
            })->toArray();
        }

        // 5. Display Attributes (Specifications) for the "Specificații" tab
        $displayAttributes = collect();

        // Common Attributes (where product_variant_id is NULL)
        if ($product->relationLoaded('attributeValues')) {
            $common = $product->attributeValues->whereNull('product_variant_id');
            foreach ($common as $av) {
                $displayAttributes->push([
                    'name' => $av->attribute ? $av->attribute->name : 'Attribute',
                    'value' => $av->value,
                ]);
            }
        }

        // Variant Specific Attributes
        if ($variant && $variant->relationLoaded('attributes')) {
             foreach ($variant->attributes as $av) {
                 $displayAttributes->push([
                    'name' => $av->attribute ? $av->attribute->name : 'Attribute',
                    'value' => $av->value,
                 ]);
             }
        }

        return [
            'id'               => $product->id,
            'variant_id'       => $variant ? $variant->id : null,
            'parent_slug'      => $variant ? $product->slug : null,
            'name'             => $effectiveName,
            'slug'             => $effectiveSlug,
            'code'             => $effectiveCode,
            'category'         => $product->relationLoaded('mainCategory')
                                    ? optional($product->mainCategory)->name
                                    : null,
            'category_slug'    => $product->relationLoaded('mainCategory')
                                    ? optional($product->mainCategory)->slug
                                    : null,
            'brand'            => $product->relationLoaded('brand')
                                    ? optional($product->brand)->name
                                    : null,
            'price'            => $pricing['price'],
            'promoPrice'       => $pricing['promo_price'],
            'hasDiscount'      => $pricing['has_discount'],
            'discountPercent'  => $pricing['discount_percent'],
            'flags'            => [
                'is_new'         => (bool) $product->is_new,
                'is_recommended' => (bool) ($product->is_best_seller ?? false),
                'is_on_sale'     => (bool) ($product->is_promo ?? false),
                'is_promo'       => (bool) $product->is_promo,
            ],
            'appliedPromotion' => $pricing['applied_promotion'],
            'list_price'       => $listPrice,
            'stock_qty'        => $stockQty,
            'stock_status'     => $stockStatus,
            'allow_backorder'  => (bool) $product->allow_backorder,
            'vat_rate'         => (float) $product->vat_rate,
            'vat_included'     => (bool) $product->vat_included,
            'main_image_url'   => $product->main_image_url,
            'units'            => $unitsList,
            'selected_attributes' => $selectedAttributes,
            'attributes'       => $displayAttributes->toArray(),
            'variants'         => $product->variants->map(function ($v) {
                return [
                    'id' => $v->id,
                    'sku' => $v->sku,
                    'slug' => $v->slug, // Important for linking
                    'name' => $v->name,
                    'price' => (float) $v->list_price,
                    'stock_qty' => (int) $v->stock_qty,
                    'attributes' => collect($v->attributes)->map(function ($av) {
                        return [
                            'name' => $av->attribute ? $av->attribute->name : 'Attribute',
                            'slug' => $av->attribute ? $av->attribute->slug : '',
                            'value' => $av->value,
                        ];
                    })->toArray(),
                ];
            }),
        ];
    }

    /**
     * Calculează prețul pentru Produs sau Varianta (dacă există).
     * Aplică promoțiile de pe produsul părinte și pe variantă.
     */
    protected function calculatePriceForEntity(Product $product, ?ProductVariant $variant, ?Customer $customer): array
    {
        // Dacă nu avem variantă, folosim logica standard
        if (!$variant) {
            return $this->calculateProductPrice($product, $customer);
        }

        // Dacă avem variantă, prețul de bază este cel al variantei
        $basePrice = (float) $variant->list_price;

        // Putem folosi PromotionEngine, dar trebuie să-i spunem să folosească acest basePrice
        // Momentan, PromotionEngine ia prețul din produs ($product->list_price).
        // Putem "falsifica" prețul produsului temporar sau extinde engine-ul.
        // Pentru siguranță și simplitate, vom face o logică similară aici, reutilizand ce putem.
        
        // 1. Get promotions for parent product
        $user = Auth::user();
        $promotions = $this->promotionEngine->getActivePromotions($user, $customer);
        
        // Filter applicable to product
        $applicable = $promotions->filter(function ($promo) use ($product) {
            return $this->promotionEngine->promotionAppliesToProduct($promo, $product);
        });

        // 2. Apply best promotion on the VARIANT price
        $finalPrice = $basePrice;
        $appliedPromoData = null;

        // Simple logic: apply first/best. 
        // Note: This duplicates some logic from PromotionEngine::calculateProductPriceWithPromotions
        // Ideally we'd pass $variant to engine.
        
        foreach ($applicable as $promo) {
            [$newPrice] = $this->promotionEngine->applyPromotionToUnit($promo, $basePrice, 1);
            if ($newPrice < $finalPrice) {
                $finalPrice = $newPrice;
                $appliedPromoData = [
                    'id' => $promo->id,
                    'name' => $promo->name,
                    'slug' => $promo->slug,
                ];
            }
        }

        $hasDiscount = $finalPrice < $basePrice;
        $discountPercent = 0;
        if ($basePrice > 0) {
            $discountPercent = round(($basePrice - $finalPrice) / $basePrice * 100, 2);
        }

        return [
            'has_discount'      => $hasDiscount,
            'price'             => $basePrice,
            'promo_price'       => $finalPrice,
            'discount_percent'  => $discountPercent,
            'applied_promotion' => $appliedPromoData,
        ];
    }

    /**
     * Construiește lista de unități de măsură (Base + Secondary) cu prețuri calculate.
     */
    protected function buildUnitsList(Product $product, ?ProductVariant $variant, float $basePrice, float $promoPrice): array
    {
        $units = collect();

        // 1. Unitatea de bază (din ERP/Produs)
        // Dacă produsul are unit_of_measure setat, îl adăugăm ca unitate implicită
        // User-ul vrea: "O unitate de masura principala care este cea din erp"
        
        $mainUnitName = $product->unit_of_measure ?? 'buc';
        
        $units->push([
            'id'                => 'base',
            'name'              => $mainUnitName, // ex: buc
            'unit'              => $mainUnitName,
            'conversion_factor' => 1.0,
            'price'             => $basePrice,
            'promo_price'       => $promoPrice,
            'is_base'           => true,
            'is_default'        => true, // Default selection
        ]);

        // 2. Unități secundare definite în `product_units`
        // Acestea pot fi legate de produs sau de variantă
        
        $productUnits = $product->units;
        
        if ($variant) {
            // Dacă suntem pe variantă, luăm și unitățile variantei
            // (și posibil și cele ale produsului dacă sunt generice? 
            //  User-ul zice: "Fiecare produs/variatie are posibilitatea sa i se seteze...")
            // Presupunem că dacă varianta are unități, le folosim pe acelea.
            // Dacă nu, fallback la produs? Sau cumulate?
            // De obicei, ambalarea e specifică fizic.
            // Vom include unitățile produsului care NU sunt legate de o altă variantă.
            // + unitățile legate specific de varianta curentă.
            
            $variantUnits = $variant->units;
            $productUnits = $productUnits->whereNull('product_variant_id')->merge($variantUnits);
        } else {
             // Doar unitățile produsului care nu țin de variante specifice
             $productUnits = $productUnits->whereNull('product_variant_id');
        }

        foreach ($productUnits as $u) {
            $factor = (float) $u->conversion_factor;
            if ($factor <= 0) $factor = 1;

            // Calcul preț per unitate de ambalare
            // Ex: 1 bax = 10 buc. Preț bax = 10 * Preț buc.
            
            $unitPrice = $basePrice * $factor;
            $unitPromoPrice = $promoPrice * $factor;

            // Override price if specific price is set? (User didn't explicitly ask, but DB has `specific_price`)
            // if ($u->specific_price) { ... }

            $units->push([
                'id'                => $u->id,
                'name'              => $u->name, // ex: Bax 10 buc
                'unit'              => $u->unit ?? $mainUnitName, // ex: bax
                'conversion_factor' => $factor,
                'price'             => $unitPrice,
                'promo_price'       => $unitPromoPrice,
                'is_base'           => (bool) $u->is_base,
                'is_default'        => false,
            ]);
        }

        return $units->values()->toArray();
    }

    /**
     * Calculates prices for a collection of items.
     * Compatible with legacy return structure.
     */
    public function priceItems(Collection $items, ?Customer $customer = null, ?Coupon $coupon = null, ?Cart $cart = null): array
    {
        $user = Auth::user();
        
        // PromotionEngine expects items with 'product' relation and 'quantity'.
        // It returns a calculated structure.
        $engineResult = $this->promotionEngine->calculateItems($items, $user, $customer, $cart);

        // Map items to legacy structure
        $mappedItems = [];
        foreach ($engineResult['items'] as $itemData) {
            // Handle Gift Items (Virtual)
            if (($itemData['is_gift'] ?? false) === true) {
                 // It's a gift, no original item to match
                 $product = Product::find($itemData['product_id']);
                 $mappedItems[] = [
                    'id'                => $itemData['id'], // Virtual ID
                    'product_id'        => $itemData['product_id'],
                    'product_name'      => $itemData['product_name'],
                    'product_slug'      => $product->slug ?? '',
                    'quantity'          => $itemData['quantity'],
                    
                    'unit_price'        => 0,
                    'line_subtotal'     => 0,
                    'line_discount'     => 0,
                    'line_total'        => 0,
                    
                    'unit_base_price'   => 0,
                    'unit_final_price'  => 0,
                    'line_base_total'   => 0,
                    'line_final_total'  => 0,
                    
                    'product'           => [
                        'id'            => $product->id,
                        'name'          => $product->name,
                        'slug'          => $product->slug,
                        'internal_code' => $product->internal_code,
                        'sku'           => $product->internal_code,
                        'thumbnail'     => $product->main_image_url, 
                        'main_image_url'=> $product->main_image_url,
                    ],

                    'applied_promotions'=> collect($itemData['applied_promotions']),
                    'applied_promotion' => [
                        'id'   => $itemData['applied_promotions'][0]['id'],
                        'name' => $itemData['applied_promotions'][0]['name'],
                        'slug' => $itemData['applied_promotions'][0]['slug'],
                    ],
                    'is_gift' => true,
                ];
                continue;
            }

            // Find original product for details
            // We use product_id to match.
            // Items input might be CartItems or VirtualItems.
            $originalItem = $items->first(function($i) use ($itemData) {
                return ($i->id && $i->id == $itemData['id']) || ($i->product_id == $itemData['product_id']);
            });
            
            $product = $originalItem ? $originalItem->product : Product::find($itemData['product_id']);
            $variant = $originalItem ? $originalItem->variant : null;
            
            // Determine stock status and qty
            $stockStatus = 'in_stock';
            $stockQty = 0;

            if ($variant) {
                $stockStatus = $variant->stock_status;
                $stockQty = (int) $variant->stock_qty;
            } elseif ($product) {
                $stockStatus = $product->stock_status;
                $stockQty = (int) $product->stock_qty;
            }

            // applied_promotions is array of arrays in engine result
            $appliedPromosList = $itemData['applied_promotions'];
            
            $mappedItems[] = [
                'id'                => $itemData['id'],
                'product_id'        => $itemData['product_id'],
                'product_variant_id'=> $originalItem ? $originalItem->product_variant_id : null,
                'product_name'      => $itemData['product_name'],
                'product_code'      => $variant ? $variant->sku : ($product->internal_code ?? ''),
                'product_slug'      => $product->slug ?? '',
                'quantity'          => $itemData['quantity'],
                'unit'              => $originalItem->unit ?? 'buc',
                'unitInfo'          => $originalItem->unit ?? 'buc',
                'stockStatus'       => $stockStatus,
                
                // Legacy keys
                'unit_price'        => $itemData['unit_base_price'],
                'line_subtotal'     => $itemData['line_subtotal'],
                'line_discount'     => $itemData['line_subtotal'] - $itemData['line_total'],
                'line_total'        => $itemData['line_total'],
                
                // Extra keys often used
                'unit_base_price'   => $itemData['unit_base_price'],
                'unit_final_price'  => $itemData['unit_final_price'],
                'line_base_total'   => $itemData['line_subtotal'],
                'line_final_total'  => $itemData['line_total'],
                
                'product'           => [
                    'id'            => $product->id,
                    'name'          => $product->name,
                    'slug'          => $product->slug,
                    'internal_code' => $product->internal_code,
                    'sku'           => $product->internal_code,
                    'thumbnail'     => $product->main_image_url,
                    'main_image_url'=> $product->main_image_url,
                    'vat_rate'      => (float) $product->vat_rate,
                    'vat_included'  => (bool) $product->vat_included,
                    'stock_qty'     => $stockQty,
                    'stock_status'  => $stockStatus,
                ],
                'product_variant'   => $variant ? [
                    'id'   => $variant->id,
                    'sku'  => $variant->sku,
                    'name' => $variant->name,
                    'stock_qty' => (int) $variant->stock_qty,
                ] : null,
                'product_vat_rate'     => (float) $product->vat_rate,
                'product_vat_included' => (bool) $product->vat_included,

                'applied_promotions'=> collect($appliedPromosList),
                'applied_promotion' => count($appliedPromosList) > 0 ? [
                    'id'   => $appliedPromosList[0]['id'],
                    'name' => $appliedPromosList[0]['name'],
                    'slug' => $appliedPromosList[0]['slug'],
                ] : null,
                'is_gift' => false,
            ];
        }

        $subtotal = $engineResult['subtotal'];
        $discountTotal = $engineResult['discount_total'];
        $totalAfterLineDiscounts = $subtotal - $discountTotal;

        // Apply Coupon (Legacy Logic)
        $couponDiscount = 0.0;
        $appliedCoupon = null;

        if ($coupon && $coupon->isValid()) {
             if (!$coupon->min_cart_value || $totalAfterLineDiscounts >= $coupon->min_cart_value) {
                if ($coupon->is_stackable || $discountTotal <= 0) {
                    if ($coupon->discount_type === 'percent') {
                        $couponDiscount = $totalAfterLineDiscounts * ($coupon->discount_value / 100);
                    } elseif ($coupon->discount_type === 'fixed_cart') {
                        $couponDiscount = min($totalAfterLineDiscounts, $coupon->discount_value);
                    }
                    
                    $appliedCoupon = [
                        'code' => $coupon->code,
                        'discount_amount' => round($couponDiscount, 2)
                    ];
                }
             }
        }

        $finalTotal = max(0, $totalAfterLineDiscounts - $couponDiscount);
        
        // Include shipping promotions if any from engine?
        // Legacy didn't return shipping_promotions, but we can add it.
        $shippingPromos = $engineResult['shipping_promotions'] ?? [];

        return [
            'items'           => $mappedItems,
            'subtotal'        => round($subtotal, 2),
            'discount_total'  => round($discountTotal + $couponDiscount, 2),
            'coupon_discount' => round($couponDiscount, 2),
            'total'           => round($finalTotal, 2),
            'applied_coupon'  => $appliedCoupon,
            'shipping_promotions' => $shippingPromos,
        ];
    }

    public function priceCart(Cart $cart, ?Customer $customer = null): array
    {
        return $this->priceItems($cart->items, $customer, $cart->coupon, $cart);
    }
}
