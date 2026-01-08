<?php

namespace App\Services\Pricing;

use App\Models\Promotion;
use App\Models\Product;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\ContractPrice;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;

use App\Services\DiscountRuleService;

class PromotionEngine
{
    protected DiscountRuleService $discountRuleService;

    public function __construct(DiscountRuleService $discountRuleService)
    {
        $this->discountRuleService = $discountRuleService;
    }

    /**
     * Returnează promoțiile active pentru contextul dat (user + customer + cart).
     */
    public function getActivePromotions(?Authenticatable $user = null, ?Customer $customer = null, ?Cart $cart = null): Collection
    {
        $now = Carbon::now();

        $query = Promotion::query()
            ->where('status', 'active')
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')
                  ->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')
                  ->orWhere('end_at', '>=', $now);
            });
            // REMOVED orderBy('priority') as requested. Logic will handle "Best Deal".

        // Segmentare B2B/B2C
        if ($customer) {
            if ($customer->type === 'b2b') {
                $query->whereIn('customer_type', ['b2b', 'both']);
            } else {
                $query->whereIn('customer_type', ['b2c', 'both']);
            }
        }

        // Logged-in only
        if (! $user) {
            $query->where('logged_in_only', false);
        }

        // Filtrare după client / grup client (dacă există legături)
        if ($customer) {
            $query->where(function ($q) use ($customer) {
                $q->whereDoesntHave('customers')
                  ->orWhereHas('customers', function ($q2) use ($customer) {
                      $q2->where('customer_promotion.customer_id', $customer->id);
                  });
            });

            if ($customer->group_id) {
                $query->where(function ($q) use ($customer) {
                    $q->whereDoesntHave('customerGroups')
                      ->orWhereHas('customerGroups', function ($q2) use ($customer) {
                          $q2->where('customer_group_promotion.customer_group_id', $customer->group_id);
                      });
                });
            }
        }

        $promotions = $query
            ->with(['categories', 'brands', 'products', 'tiers'])
            ->get();

        // Merge manually attached promotions from Cart
        if ($cart) {
            $attached = $cart->promotions()
                ->where('status', 'active') // Ensure they are still active
                ->with(['categories', 'brands', 'products', 'tiers'])
                ->get();
            
            $promotions = $promotions->merge($attached)->unique('id');
        }

        return $promotions;
    }

    /**
     * Calculate prices for a list of items (virtual items from QuickOrder).
     * 
     * @param Collection $items
     * @param Authenticatable|null $user
     * @param Customer|null $customer
     * @param Cart|null $cart
     * @return array
     */
    public function calculateItems(Collection $items, ?Authenticatable $user = null, ?Customer $customer = null, ?Cart $cart = null): array
    {
        $results = [];
        $subtotal = 0.0;
        $discountTotal = 0.0;
        $shippingCost = 0.0;
        $shippingDiscount = 0.0;

        // 1. Calculate standard line items
        foreach ($items as $item) {
            $product = $item->product;
            $quantity = $item->quantity;

            $priceData = $this->getProductPriceWithPromotions($product, $user, $customer, $quantity, $cart);

            $lineSubtotal = $priceData['base_price'] * $quantity;
            $lineTotal = $priceData['final_price'] * $quantity;
            $lineDiscount = $lineSubtotal - $lineTotal;

            $subtotal += $lineSubtotal;
            $discountTotal += $lineDiscount;

            $results[] = [
                'id' => $item->id ?? null,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'unit_base_price' => $priceData['base_price'],
                'unit_final_price' => $priceData['final_price'],
                'line_subtotal' => $lineSubtotal,
                'line_total' => $lineTotal,
                'applied_promotions' => $priceData['applied_promotions'],
                'is_gift' => false,
            ];
        }

        // 2. Process Gift Promotions
        $giftItems = $this->processGiftPromotions($results, $user, $customer, $cart);
        
        foreach ($giftItems as $gift) {
            $results[] = $gift;
            // Gifts don't add to subtotal/total usually, but if they have a "value", maybe?
            // Usually gifts are free (price 0).
            // If we want to show "Free (Value 100 RON)", we handle visual part.
            // Mathematically: Price 0.
        }
        
        return [
            'items' => $results,
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'final_shipping' => $shippingCost,
            'shipping_discount' => $shippingDiscount,
        ];
    }

    protected function processGiftPromotions(array $currentItems, ?Authenticatable $user = null, ?Customer $customer = null, ?Cart $cart = null): array
    {
        $giftItems = [];
        
        // Get all active GIFT promotions
        $promotions = $this->getActivePromotions($user, $customer, $cart)
            ->where('type', 'gift');

        foreach ($promotions as $promotion) {
            $settings = $promotion->settings ?? [];
            $triggerMinQty = $settings['min_qty_per_product'] ?? ($promotion->min_qty_per_product ?? 1);
            $giftQtyPerTrigger = $settings['gift_qty'] ?? 1;
            $giftProductId = $settings['gift_product_id'] ?? null;

            if (!$giftProductId) continue;

            // Calculate total qualifying quantity in cart
            $qualifyingQty = 0;
            
            foreach ($currentItems as $item) {
                // Skip if it's already a gift (prevent infinite recursion if we were recursive, but we are not)
                if (($item['is_gift'] ?? false) === true) continue;

                $product = Product::find($item['product_id']); // Ideally optimize this lookup
                if ($product && $this->promotionAppliesToProduct($promotion, $product)) {
                    $qualifyingQty += $item['quantity'];
                }
            }

            if ($qualifyingQty >= $triggerMinQty) {
                // Calculate how many sets of gifts
                // Example: Buy 1 Get 1. Bought 3. Get 3? Or is it sets?
                // Usually: floor(bought / trigger) * gift_qty
                $sets = floor($qualifyingQty / $triggerMinQty);
                $totalGiftQty = $sets * $giftQtyPerTrigger;

                if ($totalGiftQty > 0) {
                    $giftProduct = Product::find($giftProductId);
                    if ($giftProduct) {
                        $giftItems[] = [
                            'id' => 'gift_' . $promotion->id . '_' . $giftProduct->id, // Virtual ID
                            'product_id' => $giftProduct->id,
                            'product_name' => $giftProduct->name,
                            'quantity' => $totalGiftQty,
                            'unit_base_price' => 0, // Free
                            'unit_final_price' => 0, // Free
                            'line_subtotal' => 0,
                            'line_total' => 0,
                            'applied_promotions' => [[
                                'id' => $promotion->id,
                                'name' => $promotion->name,
                                'slug' => $promotion->slug,
                                'type' => 'gift',
                                'discount_amount' => 0,
                            ]],
                            'is_gift' => true,
                        ];
                    }
                }
            }
        }

        return $giftItems;
    }


    /**
     * Prețul unui produs cu toate promoțiile aplicate (unitar).
     * IMPORTANT: This method assumes quantity = 1 for unit price calculation.
     * For volume discounts, use enrichCart or pass quantity context.
     */
    public function getProductPriceWithPromotions(
        Product $product,
        ?Authenticatable $user = null,
        ?Customer $customer = null,
        int $quantity = 1,
        ?Cart $cart = null
    ): array {
        $basePrice = $this->getBasePrice($product, $customer);
        $promotions = $this->getActivePromotions($user, $customer, $cart);

        // Filter out cart-level promotions (shipping) from unit price calculation
        $itemPromotions = $promotions->reject(function ($promo) {
            return in_array($promo->type, ['shipping', 'order_total']);
        });

        $bestFinalPrice = $basePrice;
        $bestPromotion = null;
        $bestDiscountAmount = 0;

        // "Best Deal" Logic: Iterate all and find the one resulting in lowest price
        foreach ($itemPromotions as $promotion) {
            if (! $this->promotionAppliesToProduct($promotion, $product)) {
                continue;
            }

            [$priceAfter, $discountAmount] = $this->applyPromotionToUnit($promotion, $basePrice, $quantity);

            if ($discountAmount <= 0) {
                continue;
            }

            // Check if this promotion gives a better price (lower)
            if ($priceAfter < $bestFinalPrice) {
                $bestFinalPrice = $priceAfter;
                $bestDiscountAmount = $discountAmount;
                $bestPromotion = $promotion;
            }
        }

        $applicable = [];
        if ($bestPromotion) {
            $applicable[] = [
                'id'              => $bestPromotion->id,
                'name'            => $bestPromotion->name,
                'slug'            => $bestPromotion->slug,
                'type'            => $bestPromotion->type,
                'value_type'      => $bestPromotion->value_type,
                'value'           => $bestPromotion->value,
                'discount_amount' => $bestDiscountAmount,
            ];
        }

        $finalPrice = $bestFinalPrice;

        // Check for Global Total Cap (DiscountRule)
        $userObj = ($user instanceof \App\Models\User) ? $user : null;
        $maxTotalDiscount = $this->discountRuleService->getTotalMaxDiscount($userObj, $customer);
        
        if ($maxTotalDiscount !== null && $basePrice > 0) {
            $impliedDiscountPercent = (($basePrice - $finalPrice) / $basePrice) * 100;
            
            if ($impliedDiscountPercent > $maxTotalDiscount) {
                // Calculate allowed price
                $cappedPrice = $basePrice * (1 - ($maxTotalDiscount / 100));
                
                // If we need to increase price (reduce discount)
                if ($cappedPrice > $finalPrice) {
                    $finalPrice = max($cappedPrice, 0);
                    
                    // Add an explanatory entry
                    $applicable[] = [
                        'id'              => 999999, // dummy id
                        'name'            => "Limită Discount ({$maxTotalDiscount}%)",
                        'slug'            => 'max-discount-cap',
                        'type'            => 'restriction',
                        'value_type'      => 'percent',
                        'value'           => $maxTotalDiscount,
                        'discount_amount' => 0, // It's a restriction, not a discount
                    ];
                }
            }
        }

        return [
            'base_price'        => $basePrice,
            'final_price'       => $finalPrice,
            'applied_promotions'=> $applicable,
            'has_discount'      => $finalPrice < $basePrice,
        ];
    }

    public function getBasePrice(Product $product, ?Customer $customer = null): float
    {
        // 1. Check for Contract Price (B2B)
        if ($customer) {
            $contractPrice = ContractPrice::where('customer_id', $customer->id)
                ->where('product_id', $product->id)
                ->first();
            
            if ($contractPrice) {
                return (float) $contractPrice->price;
            }
        }

        // 2. Default Product Price
        return (float) $product->list_price;
    }

    public function promotionAppliesToProduct(Promotion $promotion, Product $product): bool
    {
        // 1. Check Product ID List (whitelist)
        if ($promotion->products()->exists()) {
            if ($promotion->products->contains($product->id)) {
                return true;
            }
            // If whitelist exists but product not in it, return false UNLESS categories/brands match?
            // Usually if specific products are selected, it applies ONLY to them.
            // But if categories are ALSO selected, it applies to them TOO.
            // Let's assume OR logic between filters (Product OR Category OR Brand).
            // Actually, typically it is OR.
        }

        // 2. Check Category
        if ($promotion->categories()->exists()) {
            if ($promotion->categories->contains($product->main_category_id)) {
                return true;
            }
        }

        // 3. Check Brand
        if ($promotion->brands()->exists()) {
            if ($promotion->brands->contains($product->brand_id)) {
                return true;
            }
        }

        // If NO filters are set, does it apply to ALL products?
        // Usually yes, store-wide sale.
        if ($promotion->products()->count() === 0 && 
            $promotion->categories()->count() === 0 && 
            $promotion->brands()->count() === 0) {
            return true;
        }

        return false;
    }

    public function applyPromotionToUnit(Promotion $promotion, float $price, int $quantity = 1): array
    {
        $discountAmount = 0;
        $finalPrice = $price;

        // Volume Discount Check
        if ($promotion->type === 'volume' && $promotion->tiers()->exists()) {
            // Find applicable tier
            $tier = $promotion->tiers()
                ->where('min_qty', '<=', $quantity)
                ->orderBy('min_qty', 'desc')
                ->first();

            if ($tier) {
                // Apply tier value
                if ($promotion->value_type === 'percent') {
                    $discountAmount = $price * ($tier->value / 100);
                    $finalPrice = $price - $discountAmount;
                } elseif ($promotion->value_type === 'fixed_amount') {
                    $discountAmount = $tier->value;
                    $finalPrice = $price - $discountAmount;
                } elseif ($promotion->value_type === 'fixed_price') {
                    $finalPrice = $tier->value;
                    $discountAmount = $price - $finalPrice;
                }
            }
            
            return [max($finalPrice, 0), max($discountAmount, 0)];
        }

        // Standard Discount
        if ($promotion->value_type === 'percent') {
            $discountAmount = $price * ($promotion->value / 100);
            $finalPrice = $price - $discountAmount;
        } elseif ($promotion->value_type === 'fixed_amount') {
            $discountAmount = $promotion->value;
            $finalPrice = $price - $discountAmount;
        } elseif ($promotion->value_type === 'fixed_price') {
            $finalPrice = $promotion->value;
            $discountAmount = $price - $finalPrice;
        }

        return [max($finalPrice, 0), max($discountAmount, 0)];
    }
}
