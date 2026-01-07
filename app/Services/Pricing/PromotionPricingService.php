<?php

namespace App\Services\Pricing;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Product;
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
        [$newPrice, $discountValue] = $this->promotionEngine->applyPromotionToUnit($promotion, $basePrice, 1);
        
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
     * Returnează un array gata de trimis la frontend pentru un produs.
     */
    public function formatProductForFrontend(Product $product, ?Customer $customer = null): array
    {
        $pricing = $this->calculateProductPrice($product, $customer);

        return [
            'id'               => $product->id,
            'name'             => $product->name,
            'slug'             => $product->slug,
            'code'             => $product->internal_code,
            'category'         => $product->relationLoaded('mainCategory')
                                    ? optional($product->mainCategory)->name
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
        ];
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
                        'thumbnail'     => null, // Can add if needed
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
            
            // applied_promotions is array of arrays in engine result
            $appliedPromosList = $itemData['applied_promotions'];
            
            $mappedItems[] = [
                'id'                => $itemData['id'],
                'product_id'        => $itemData['product_id'],
                'product_name'      => $itemData['product_name'],
                'product_slug'      => $product->slug ?? '',
                'quantity'          => $itemData['quantity'],
                
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
                    'thumbnail'     => null, 
                ],

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
