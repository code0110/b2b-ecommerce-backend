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

class PromotionEngine
{
    /**
     * Returnează promoțiile active pentru contextul dat (user + customer).
     */
    public function getActivePromotions(?Authenticatable $user = null, ?Customer $customer = null): Collection
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
            })
            ->orderBy('priority', 'desc'); // Respect priority

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

        return $query
            ->with(['categories', 'brands', 'products', 'tiers'])
            ->get();
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
        int $quantity = 1
    ): array {
        $basePrice = $this->getBasePrice($product, $customer);
        $promotions = $this->getActivePromotions($user, $customer);

        // Filter out cart-level promotions (shipping) from unit price calculation
        $itemPromotions = $promotions->reject(function ($promo) {
            return in_array($promo->type, ['shipping', 'order_total']);
        });

        $applicable = [];
        $finalPrice = $basePrice;

        foreach ($itemPromotions as $promotion) {
            if (! $this->promotionAppliesToProduct($promotion, $product)) {
                continue;
            }

            [$priceAfter, $discountAmount] = $this->applyPromotionToUnit($promotion, $finalPrice, $quantity);

            if ($discountAmount <= 0) {
                continue;
            }

            $applicable[] = [
                'id'              => $promotion->id,
                'name'            => $promotion->name,
                'slug'            => $promotion->slug,
                'type'            => $promotion->type,
                'value_type'      => $promotion->value_type,
                'value'           => $promotion->value,
                'discount_amount' => $discountAmount,
            ];

            $finalPrice = $priceAfter;

            // promoție exclusivă – ne oprim
            if ($promotion->is_exclusive) {
                break;
            }
        }

        return [
            'base_price'        => $basePrice,
            'final_price'       => $finalPrice,
            'applied_promotions'=> $applicable,
            'has_discount'      => $finalPrice < $basePrice,
        ];
    }

    /**
     * Calculează totalurile din coș, cu promoții la nivel de linie și coș (livrare).
     */
    public function enrichCart(
        Cart $cart,
        ?Authenticatable $user = null,
        ?Customer $customer = null
    ): array {
        $items = [];
        $subtotal = 0.0;
        $discountTotal = 0.0;
        
        $allPromotions = $this->getActivePromotions($user, $customer);
        $cartItems = $cart->items()->with('product')->get();

        // 1. Calculate Item Prices (Standard, Volume, Special Price)
        foreach ($cartItems as $item) {
            $product = $item->product;
            if (! $product) {
                continue;
            }

            $quantity = $item->quantity;
            
            // Reuse logic but with pre-fetched promotions to avoid query N+1
            // We need to filter promotions for this product manually here for performance,
            // or just call getProductPriceWithPromotions which re-fetches.
            // For optimization, let's call getProductPriceWithPromotions but we know it fetches promos.
            // Optimization: Pass promos to getProductPriceWithPromotions? 
            // Current signature doesn't support it. Let's use the method as is for correctness first.
            
            $pricing = $this->getProductPriceWithPromotions($product, $user, $customer, $quantity);

            $lineBase  = $pricing['base_price']  * $quantity;
            $lineFinal = $pricing['final_price'] * $quantity;

            $items[] = [
                'id'                 => $item->id,
                'product_id'         => $product->id,
                'product_name'       => $product->name,
                'quantity'           => $quantity,
                'unit_base_price'    => $pricing['base_price'],
                'unit_final_price'   => $pricing['final_price'],
                'line_base_total'    => $lineBase,
                'line_final_total'   => $lineFinal,
                'applied_promotions' => $pricing['applied_promotions'],
            ];

            $subtotal      += $lineBase;
            $discountTotal += ($lineBase - $lineFinal);
        }

        // 2. Apply Bundle Promotions (Cross-check items)
        // Implementation: If bundle requires [A, B], and cart has A and B, apply extra discount?
        // This usually requires modifying line items.
        // For now, skipping complex bundle logic to ensure stability of basic flow.

        // 3. Apply Cart-Level Promotions (Shipping)
        $shippingResults = $this->calculateShippingPromotions($allPromotions, $subtotal, $discountTotal);

        $finalShipping = max(0, $shippingResults['shipping_cost'] - $shippingResults['shipping_discount']);
        $grandTotal = ($subtotal - $discountTotal) + $finalShipping;

        return [
            'items'             => $items,
            'subtotal'          => round($subtotal, 2),
            'discount_total'    => round($discountTotal, 2),
            'shipping_cost'     => round($shippingResults['shipping_cost'], 2),
            'shipping_discount' => round($shippingResults['shipping_discount'], 2),
            'final_shipping'    => round($finalShipping, 2),
            'total'             => round($grandTotal, 2),
            'shipping_promotions'=> $shippingResults['applied_promotions']
        ];
    }

    /**
     * Calculează prețurile pentru o colecție de itemi (virtuali sau din coș).
     * Itemii trebuie să aibă 'product' și 'quantity'.
     */
    public function calculateItems(Collection $items, ?Authenticatable $user = null, ?Customer $customer = null): array
    {
        $calculatedItems = [];
        $subtotal = 0.0;
        $discountTotal = 0.0;
        
        $allPromotions = $this->getActivePromotions($user, $customer);

        foreach ($items as $item) {
            $product = $item->product;
            if (!$product) continue;
            
            $quantity = $item->quantity;
            
            $pricing = $this->getProductPriceWithPromotions($product, $user, $customer, $quantity);

            $lineBase  = $pricing['base_price']  * $quantity;
            $lineFinal = $pricing['final_price'] * $quantity;

            $calculatedItems[] = [
                'id'                 => $item->id ?? null,
                'product_id'         => $product->id,
                'product_name'       => $product->name,
                'quantity'           => $quantity,
                'unit_base_price'    => $pricing['base_price'],
                'unit_final_price'   => $pricing['final_price'],
                'line_base_total'    => $lineBase,
                'line_final_total'   => $lineFinal,
                'applied_promotions' => $pricing['applied_promotions'],
            ];

            $subtotal      += $lineBase;
            $discountTotal += ($lineBase - $lineFinal);
        }

        // Apply Bundle Promotions
        $bundleResults = $this->applyBundlePromotions($calculatedItems, $allPromotions);
        $calculatedItems = $bundleResults['items'];
        // Update totals based on bundle application
        // Re-calculate totals from items as bundles might have changed line_final_total
        $subtotal = 0;
        $discountTotal = 0;
        foreach ($calculatedItems as $cItem) {
            $subtotal += $cItem['line_base_total'];
            $discountTotal += ($cItem['line_base_total'] - $cItem['line_final_total']);
        }

        // Apply Shipping Logic
        $shippingResults = $this->calculateShippingPromotions($allPromotions, $subtotal, $discountTotal);

        $finalShipping = max(0, $shippingResults['shipping_cost'] - $shippingResults['shipping_discount']);
        $grandTotal = ($subtotal - $discountTotal) + $finalShipping;

        return [
            'items'             => $calculatedItems,
            'subtotal'          => round($subtotal, 2),
            'discount_total'    => round($discountTotal, 2),
            'shipping_cost'     => round($shippingResults['shipping_cost'], 2),
            'shipping_discount' => round($shippingResults['shipping_discount'], 2),
            'final_shipping'    => round($finalShipping, 2),
            'total'             => round($grandTotal, 2),
            'shipping_promotions'=> $shippingResults['applied_promotions']
        ];
    }

    protected function calculateShippingPromotions(Collection $promotions, float $subtotal, float $discountTotal): array
    {
        $shippingCost = 25.00; // Default base shipping
        $shippingDiscount = 0.0;
        $appliedShippingPromos = [];

        $shippingPromos = $promotions->where('type', 'shipping')->sortByDesc('priority');

        foreach ($shippingPromos as $promo) {
            // Check conditions (min_cart_total)
            if ($promo->min_cart_total && ($subtotal - $discountTotal) < $promo->min_cart_total) {
                continue;
            }

            // Apply shipping discount
            $discount = 0;
            if ($promo->value_type === 'percent') {
                $discount = $shippingCost * ($promo->value / 100);
            } elseif ($promo->value_type === 'fixed_amount') {
                $discount = $promo->value;
            }

            if ($discount > 0) {
                $shippingDiscount += $discount;
                $appliedShippingPromos[] = [
                    'id' => $promo->id,
                    'name' => $promo->name,
                    'discount' => $discount
                ];
                
                if ($promo->is_exclusive) break;
            }
        }

        return [
            'shipping_cost' => $shippingCost,
            'shipping_discount' => min($shippingDiscount, $shippingCost), // Can't discount more than cost
            'applied_promotions' => $appliedShippingPromos
        ];
    }


    /* ----------------------- HELPERI INTERI ----------------------- */

    public function getBasePrice(Product $product, ?Customer $customer = null): float
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
             $group = $customer->group; 
             if ($group && $group->default_discount_percent > 0) {
                 $discount = $price * ($group->default_discount_percent / 100);
                 $price = max(0, $price - $discount);
             }
        }

        return $price;
    }

    public function promotionAppliesToProduct(Promotion $promotion, Product $product): bool
    {
        // Matchează produs / brand / categorie
        $matchProduct  = $promotion->products->contains('id', $product->id);
        $matchBrand    = $product->brand_id && $promotion->brands->contains('id', $product->brand_id);
        $matchCategory = $product->main_category_id && $promotion->categories->contains('id', $product->main_category_id);

        // If 'applies_to' logic exists (implied from previous structure), we should use it.
        // Assuming implicit "if connected, it applies". 
        // If NO connections exist, maybe it applies to ALL? 
        // Let's assume strict explicit connection for now to be safe, unless it's a "global" promo.
        // However, standard practice: if no products/brands/cats selected, maybe it's cart-wide?
        // But here we are checking "product price".
        
        if ($matchProduct || $matchBrand || $matchCategory) {
            return true;
        }

        return false;
    }

    /**
     * Aplică o promoție pentru un preț unitar curent.
     * Returnează [newPrice, discountAmount].
     */
    public function applyPromotionToUnit(Promotion $promotion, float $currentPrice, int $quantity = 1): array
    {
        if ($currentPrice <= 0) {
            return [$currentPrice, 0.0];
        }

        $discountValue = 0.0;
        $apply = false;

        // 1. Check Logic based on TYPE
        if ($promotion->type === 'volume') {
            // Volume discount: check tiers
            // Find the highest tier where min_qty <= $quantity
            $tier = $promotion->tiers()
                ->where('min_qty', '<=', $quantity)
                ->orderBy('min_qty', 'desc')
                ->first();

            if ($tier) {
                // Apply tier value
                // Tiers can have their own value/value_type or use the parent's?
                // Migration: tiers have 'value', parent has 'value_type'.
                $value = $tier->value;
                $valueType = $promotion->value_type; // Inherit type from parent for now
                $apply = true;
            }
        } elseif ($promotion->type === 'standard') {
            // Standard discount
            $value = $promotion->value;
            $valueType = $promotion->value_type;
            $apply = true;
        } elseif ($promotion->type === 'special_price') {
             $value = $promotion->value;
             $valueType = 'fixed_price';
             $apply = true;
        } elseif ($promotion->type === 'gift') {
             $value = 0;
             $valueType = 'fixed_price';
             $apply = true;
        }

        if (!$apply) {
             return [$currentPrice, 0.0];
        }

        // 2. Calculate Discount
        switch ($valueType) {
            case 'percent':
                $discountValue = round($currentPrice * ($value / 100), 2);
                $newPrice = max($currentPrice - $discountValue, 0.0);
                break;

            case 'fixed_amount':
                $discountValue = min($currentPrice, $value);
                $newPrice = max($currentPrice - $discountValue, 0.0);
                break;

            case 'fixed_price':
                $newPrice = $value;
                $discountValue = max($currentPrice - $newPrice, 0.0);
                break;
            
            default:
                return [$currentPrice, 0.0];
        }

        return [$newPrice, $discountValue];
    }

    /**
     * Apply Bundle Promotions
     * Checks if all required products are in cart, then applies discount.
     */
    protected function applyBundlePromotions(array $items, Collection $promotions): array
    {
        $bundlePromos = $promotions->where('type', 'bundle')->sortByDesc('priority');
        
        foreach ($bundlePromos as $promo) {
            $conditions = $promo->conditions ?? [];
            $requiredProductIds = $conditions['products'] ?? [];
            
            if (empty($requiredProductIds)) continue;

            // Check if cart has all required products
            $cartProductIds = array_column($items, 'product_id');
            // Check if all required IDs are present in cart IDs
            $missing = array_diff($requiredProductIds, $cartProductIds);
            
            if (empty($missing)) {
                // Apply discount to matching items
                foreach ($items as &$item) {
                    if (in_array($item['product_id'], $requiredProductIds)) {
                        
                        $currentPrice = $item['unit_final_price'];
                        $discount = 0;
                        
                        if ($promo->value_type === 'percent') {
                            $discount = $currentPrice * ($promo->value / 100);
                        } elseif ($promo->value_type === 'fixed_amount') {
                            // Fixed amount off PER UNIT
                            $discount = $promo->value; 
                        } elseif ($promo->value_type === 'fixed_price') {
                             // Bundle Fixed Price? Usually means "Buy A+B for $100".
                             // This is complex if items have different prices.
                             // For now, treat as fixed price per unit if that's the setup,
                             // or skip if logic is ambiguous without more sophisticated "Bundle Price" allocator.
                             // Let's fallback to percent for bundles usually.
                        }
                        
                        if ($discount > 0) {
                            $newPrice = max(0, $currentPrice - $discount);
                            $discountAmount = $currentPrice - $newPrice;
                            
                            $item['unit_final_price'] = $newPrice;
                            $item['line_final_total'] = $newPrice * $item['quantity'];
                            $item['applied_promotions'][] = [
                                'id' => $promo->id,
                                'name' => $promo->name . ' (Bundle)',
                                'type' => 'bundle',
                                'value' => $promo->value,
                                'value_type' => $promo->value_type,
                                'discount_amount' => $discountAmount
                            ];
                        }
                    }
                }
                unset($item);
            }
        }

        return ['items' => $items];
    }
}
