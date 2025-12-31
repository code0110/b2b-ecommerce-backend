<?php

namespace App\Services\Pricing;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ContractPrice;
use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Promotion;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PromotionPricingService
{
    /**
     * Promoții active pentru un anumit client (B2B/B2C + logat / nelogat).
     */
    public function getActivePromotionsForCustomer(?Customer $customer = null): Collection
    {
        $now = Carbon::now();
        $customerType = $customer?->type ?? 'b2c';

        $query = Promotion::query()
            ->where('status', 'active')
            ->where(function ($q) use ($now) {
                $q->whereNull('start_at')
                  ->orWhere('start_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('end_at')
                  ->orWhere('end_at', '>=', $now);
            })
            ->where(function ($q) use ($customerType) {
                $q->where('customer_type', 'both')
                  ->orWhere('customer_type', $customerType);
            });

        // dacă promoția este doar pentru utilizatori logați
        if (!$customer) {
            $query->where('logged_in_only', false);
        }

        return $query
            ->with(['categories:id', 'brands:id', 'products:id'])
            ->get();
    }

    /**
     * Verifică dacă promoția se aplică pentru un anumit produs.
     */
    public function promotionAppliesToProduct(Promotion $promotion, Product $product): bool
    {
        if ($promotion->applies_to === 'all') {
            return true;
        }

        if ($promotion->applies_to === 'products') {
            return $promotion->products->contains('id', $product->id);
        }

        if ($promotion->applies_to === 'brands') {
            return $promotion->brands->contains('id', $product->brand_id);
        }

        if ($promotion->applies_to === 'categories') {
            // folosim main_category_id + pivot category_product, dacă există relația
            if ($promotion->categories->contains('id', $product->main_category_id)) {
                return true;
            }

            if (method_exists($product, 'categories')) {
                $productCategoryIds = $product->categories->pluck('id')->all();
                return $promotion->categories->pluck('id')->intersect($productCategoryIds)->isNotEmpty();
            }
        }

        return false;
    }

    /**
     * Calculează prețul efectiv al unui produs (preț listă + promoții).
     */
    public function calculateProductPrice(Product $product, ?Customer $customer = null): array
    {
        $basePrice = $this->getBasePrice($product, $customer);
        $promotions = $this->getActivePromotionsForCustomer($customer)
            ->filter(fn (Promotion $p) => $this->promotionAppliesToProduct($p, $product));

        if ($promotions->isEmpty()) {
            return [
                'has_discount'     => false,
                'price'            => $basePrice,
                'promo_price'      => $basePrice,
                'discount_percent' => 0,
                'applied_promotion'=> null,
            ];
        }

        // Simplu: alegem promoția care dă prețul final cel mai mic (best deal)
        $best = null;
        $bestPrice = $basePrice;

        foreach ($promotions as $promotion) {
            [$promoPrice, $discountPercent] = $this->applyPromotionOnPrice($promotion, $basePrice);

            if ($promoPrice < $bestPrice) {
                $bestPrice = $promoPrice;
                $best = [
                    'promotion'        => $promotion,
                    'promo_price'      => $promoPrice,
                    'discount_percent' => $discountPercent,
                ];
            }
        }

        if (!$best) {
            return [
                'has_discount'     => false,
                'price'            => $basePrice,
                'promo_price'      => $basePrice,
                'discount_percent' => 0,
                'applied_promotion'=> null,
            ];
        }

        return [
            'has_discount'      => true,
            'price'             => $basePrice,
            'promo_price'       => $best['promo_price'],
            'discount_percent'  => $best['discount_percent'],
            'applied_promotion' => [
                'id'   => $best['promotion']->id,
                'name' => $best['promotion']->name,
                'slug' => $best['promotion']->slug,
            ],
        ];
    }

    /**
     * Aplică o promoție pe un preț brut și returnează [promoPrice, discountPercent].
     */
    protected function applyPromotionOnPrice(Promotion $promotion, float $basePrice): array
    {
        $promoPrice = $basePrice;
        $discountPercent = 0.0;

        switch ($promotion->bonus_type) {
            case 'discount_percent':
                if ($promotion->discount_percent > 0) {
                    $discountPercent = (float) $promotion->discount_percent;
                    $promoPrice = max(0, $basePrice * (1 - $discountPercent / 100));
                }
                break;

            case 'discount_value':
                if ($promotion->discount_value > 0) {
                    $discountAmount = min($basePrice, (float) $promotion->discount_value);
                    $promoPrice = max(0, $basePrice - $discountAmount);
                    $discountPercent = $basePrice > 0
                        ? round($discountAmount / $basePrice * 100, 2)
                        : 0;
                }
                break;

            case 'free_item':
                // Pentru preț unitar, tratăm ca 100% discount (folositor mai ales pe coș)
                $promoPrice = 0;
                $discountPercent = 100;
                break;

            default:
                // fallback – fără modificare
                $promoPrice = $basePrice;
                $discountPercent = 0;
        }

        return [$promoPrice, $discountPercent];
    }

    protected function calculateLineDiscount(Promotion $promotion, float $currentSubtotal, float $unitBasePrice, int $quantity): float
    {
        $discount = 0.0;

        switch ($promotion->bonus_type) {
            case 'discount_percent':
                if ($promotion->discount_percent > 0) {
                    $discount = $currentSubtotal * ((float) $promotion->discount_percent / 100);
                }
                break;

            case 'discount_value':
                if ($promotion->discount_value > 0) {
                    // Assuming fixed value applies PER UNIT?
                    // "discount_value" usually means "Amount off".
                    // If it's 10 RON off, is it 10 RON off the ITEM LINE or PER UNIT?
                    // Usually "Fixed Discount" on a product promotion is per unit.
                    $discount = min($currentSubtotal, (float) $promotion->discount_value * $quantity);
                }
                break;

            case 'free_item':
                if ($promotion->min_qty_per_product > 0 && $quantity >= $promotion->min_qty_per_product) {
                    // One unit free
                    $discount = $unitBasePrice; 
                }
                break;
        }

        return $discount;
    }

    /**
     * Prețul de bază al produsului: 
     * 1. Contract Price (Client)
     * 2. Contract Price (Grup Client)
     * 3. List Price (sau override) - Discount Grup
     */
    protected function getBasePrice(Product $product, ?Customer $customer = null): float
    {
        // 1. Contract Price (Client specific)
        if ($customer) {
            $customerContractPrice = ContractPrice::where('product_id', $product->id)
                ->where('customer_id', $customer->id)
                ->value('price');

            if ($customerContractPrice !== null) {
                return (float) $customerContractPrice;
            }

            // 2. Contract Price (Grup Client)
            if ($customer->group_id) {
                $groupContractPrice = ContractPrice::where('product_id', $product->id)
                    ->where('customer_group_id', $customer->group_id)
                    ->value('price');

                if ($groupContractPrice !== null) {
                    return (float) $groupContractPrice;
                }
            }
        }

        // 3. Preț de listă (sau override)
        $price = !is_null($product->price_override) 
            ? (float) $product->price_override 
            : (float) $product->list_price;

        // 4. Discount standard Grup (aplicat la prețul de listă)
        if ($customer && $customer->group_id) {
             // Încărcăm grupul dacă nu e deja încărcat
             $group = $customer->group; 
             if ($group && $group->default_discount_percent > 0) {
                 $discount = $price * ($group->default_discount_percent / 100);
                 $price = max(0, $price - $discount);
             }
        }

        return $price;
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
        // folosim mainCategory, nu category
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
     * Calculează prețurile pe coș (per linie + totaluri), incluzând promoții.
     *
     * Returnează un array:
     * [
     *   'items' => [...],
     *   'subtotal' => ...,
     *   'discount_total' => ...,
     *   'total' => ...
     * ]
     */
    public function priceCart(Cart $cart, ?Customer $customer = null): array
    {
        $promotions = $this->getActivePromotionsForCustomer($customer);

        // 1. Calculate base subtotal first to support cart-level rules
        $tempItems = [];
        $cartSubtotal = 0.0;

        foreach ($cart->items as $item) {
            $product = $item->product;
            if (!$product) continue;

            $basePrice = $this->getBasePrice($product, $customer);
            $lineSubtotal = $basePrice * $item->quantity;
            $cartSubtotal += $lineSubtotal;

            $tempItems[] = [
                'item' => $item,
                'product' => $product,
                'base_price' => $basePrice,
                'line_subtotal' => $lineSubtotal,
            ];
        }

        $items = [];
        $discountTotal = 0.0;

        // 2. Apply promotions
        foreach ($tempItems as $data) {
            $item = $data['item'];
            $product = $data['product'];
            $basePrice = $data['base_price'];
            $lineSubtotal = $data['line_subtotal'];
            
            // Filter eligible promotions
            $eligiblePromos = $promotions->filter(function (Promotion $p) use ($product, $item, $cartSubtotal) {
                // Product scope
                if (!$this->promotionAppliesToProduct($p, $product)) {
                    return false;
                }
                // Min Quantity
                if ($p->min_qty_per_product > 0 && $item->quantity < $p->min_qty_per_product) {
                    return false;
                }
                // Min Cart Total
                if ($p->min_cart_total > 0 && $cartSubtotal < (float) $p->min_cart_total) {
                    return false;
                }
                return true;
            });

            // Sort by priority (descending)
            $eligiblePromos = $eligiblePromos->sortByDesc('priority');

            $lineDiscountTotal = 0.0;
            $appliedPromosList = [];

            // Strategy:
            // 1. Check for Exclusive promotions. If any exists, pick the HIGHEST PRIORITY one and stop.
            // 2. If no exclusive, apply all Iterative promotions in Priority order.

            $exclusivePromo = $eligiblePromos->firstWhere('stacking_type', 'exclusive');

            if ($exclusivePromo) {
                $discount = $this->calculateLineDiscount($exclusivePromo, $lineSubtotal, $basePrice, $item->quantity);
                if ($discount > 0) {
                    $lineDiscountTotal = $discount;
                    $appliedPromosList[] = [
                        'promotion' => $exclusivePromo,
                        'amount'    => $discount,
                    ];
                }
            } else {
                // Apply Iterative promos
                $currentSubtotal = $lineSubtotal;

                foreach ($eligiblePromos as $promotion) {
                    // Skip if accidentally exclusive (shouldn't happen if logic above is correct, but safe)
                    if ($promotion->stacking_type === 'exclusive') continue;

                    $discount = $this->calculateLineDiscount($promotion, $currentSubtotal, $basePrice, $item->quantity);

                    if ($discount > 0) {
                        $lineDiscountTotal += $discount;
                        $currentSubtotal -= $discount; // Reduce base for next promo if needed?
                        // Usually iterative means "10% off", then "5% off the remaining".
                        // If discount is fixed value, it just subtracts.
                        
                        $appliedPromosList[] = [
                            'promotion' => $promotion,
                            'amount'    => $discount,
                        ];
                    }
                }
            }
            
            // Ensure we don't discount more than the subtotal
            $lineDiscountTotal = min($lineDiscountTotal, $lineSubtotal);

            $discountTotal += $lineDiscountTotal;
            $lineTotal = $lineSubtotal - $lineDiscountTotal;

            $items[] = [
                'id'                => $item->id,
                'product_id'        => $product->id,
                'product_name'      => $product->name,
                'product_slug'      => $product->slug,
                'quantity'          => $item->quantity,
                'unit_price'        => $basePrice, // Base price
                'line_subtotal'     => round($lineSubtotal, 2),
                'line_discount'     => round($lineDiscountTotal, 2),
                'line_total'        => round($lineTotal, 2),
                
                // Aliases & Extras for Frontend (Cart.vue / Checkout.vue compatibility)
                'unit_base_price'   => $basePrice,
                'unit_final_price'  => $item->quantity > 0 ? round($lineTotal / $item->quantity, 2) : 0,
                'line_base_total'   => round($lineSubtotal, 2),
                'line_final_total'  => round($lineTotal, 2),
                
                'product'           => [
                    'id'            => $product->id,
                    'name'          => $product->name,
                    'slug'          => $product->slug,
                    'internal_code' => $product->internal_code,
                    'sku'           => $product->internal_code,
                    'thumbnail'     => null, // TODO: add if needed
                ],

                'applied_promotions'=> collect($appliedPromosList)->map(fn($item) => [
                    'id' => $item['promotion']->id,
                    'name' => $item['promotion']->name,
                    'slug' => $item['promotion']->slug,
                    'discount_amount' => round($item['amount'], 2),
                ]),
                // Backward compatibility: use the first applied promo
                'applied_promotion' => count($appliedPromosList) > 0 ? [
                    'id'   => $appliedPromosList[0]['promotion']->id,
                    'name' => $appliedPromosList[0]['promotion']->name,
                    'slug' => $appliedPromosList[0]['promotion']->slug,
                ] : null,
            ];
        }

        $subtotal = $cartSubtotal; // restore correct subtotal


        $totalAfterLineDiscounts = $subtotal - $discountTotal;
        $couponDiscount = 0.0;
        $appliedCoupon = null;

        if ($cart->coupon_id) {
            $coupon = $cart->coupon;
            
            if ($coupon && $coupon->isValid()) {
                // Check min_cart_value
                if (!$coupon->min_cart_value || $totalAfterLineDiscounts >= $coupon->min_cart_value) {
                    // Check stackable
                    // If !is_stackable and $discountTotal > 0, we might skip coupon.
                    // For now, let's assume strict interpretation: invalid if other promos applied.
                    if ($coupon->is_stackable || $discountTotal <= 0) {
                        // Calculate discount
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
        }

        $finalTotal = max(0, $totalAfterLineDiscounts - $couponDiscount);
        $totalDiscount = $discountTotal + $couponDiscount;

        return [
            'items'          => $items,
            'subtotal'       => round($subtotal, 2),
            'discount_total' => round($totalDiscount, 2),
            'coupon_discount' => round($couponDiscount, 2),
            'total'          => round($finalTotal, 2),
            'applied_coupon' => $appliedCoupon,
        ];
    }
}
