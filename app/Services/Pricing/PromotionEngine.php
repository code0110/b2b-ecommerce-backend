<?php

namespace App\Services\Pricing;

use App\Models\Promotion;
use App\Models\Product;
use App\Models\Cart;
use App\Models\Customer;
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
            });

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
                      $q2->where('customers.id', $customer->id);
                  });
            });

            if ($customer->group_id) {
                $query->where(function ($q) use ($customer) {
                    $q->whereDoesntHave('customerGroups')
                      ->orWhereHas('customerGroups', function ($q2) use ($customer) {
                          $q2->where('customer_groups.id', $customer->group_id);
                      });
                });
            }
        }

        return $query
            ->with(['categories', 'brands', 'products'])
            ->get();
    }

    /**
     * Prețul unui produs cu toate promoțiile aplicate (unitar).
     */
    public function getProductPriceWithPromotions(
        Product $product,
        ?Authenticatable $user = null,
        ?Customer $customer = null
    ): array {
        $basePrice = $this->getBasePrice($product, $customer);

        $promotions = $this->getActivePromotions($user, $customer);

        $applicable = [];
        $finalPrice = $basePrice;

        foreach ($promotions as $promotion) {
            if (! $this->promotionAppliesToProduct($promotion, $product)) {
                continue;
            }

            [$priceAfter, $discountAmount] = $this->applyPromotionToUnit($promotion, $finalPrice);

            if ($discountAmount <= 0) {
                continue;
            }

            $applicable[] = [
                'id'              => $promotion->id,
                'name'            => $promotion->name,
                'slug'            => $promotion->slug,
                'bonus_type'      => $promotion->bonus_type,
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
     * Calculează totalurile din coș, cu promoții la nivel de linie.
     */
    public function enrichCart(
        Cart $cart,
        ?Authenticatable $user = null,
        ?Customer $customer = null
    ): array {
        $items = [];
        $subtotal = 0.0;
        $discountTotal = 0.0;

        // Eager load pentru eficiență
        $cartItems = $cart->items()->with('product')->get();

        foreach ($cartItems as $item) {
            $product = $item->product;
            if (! $product) {
                continue;
            }

            $quantity = $item->quantity;

            $pricing = $this->getProductPriceWithPromotions($product, $user, $customer);

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

        return [
            'items'          => $items,
            'subtotal'       => round($subtotal, 2),
            'discount_total' => round($discountTotal, 2),
            'total'          => round($subtotal - $discountTotal, 2),
        ];
    }

    /* ----------------------- HELPERI INTERI ----------------------- */

    protected function getBasePrice(Product $product, ?Customer $customer = null): float
    {
        // Aici poți introduce logica de prețuri contractuale / liste B2B
        return (float) ($product->price_override ?? $product->list_price ?? 0);
    }

    protected function promotionAppliesToProduct(Promotion $promotion, Product $product): bool
    {
        // Matchează produs / brand / categorie
        $matchProduct  = $promotion->products->contains('id', $product->id);
        $matchBrand    = $product->brand_id && $promotion->brands->contains('id', $product->brand_id);
        $matchCategory = $product->main_category_id && $promotion->categories->contains('id', $product->main_category_id);

        if (! $matchProduct && ! $matchBrand && ! $matchCategory) {
            return false;
        }

        // Aici poți adăuga extra condiții specifice

        return true;
    }

    /**
     * Aplică o promoție pentru un preț unitar curent.
     * Returnează [newPrice, discountAmount].
     *
     * NOTĂ:
     * - presupunem existența coloanelor discount_percent și discount_value în promotions.
     *   Dacă la tine au alt nume, doar ajustezi accesul.
     */
    protected function applyPromotionToUnit(Promotion $promotion, float $currentPrice): array
    {
        if ($currentPrice <= 0) {
            return [$currentPrice, 0.0];
        }

        switch ($promotion->bonus_type) {
            case 'discount_percent':
                $percent = (float) ($promotion->discount_percent ?? 0);
                if ($percent <= 0) {
                    return [$currentPrice, 0.0];
                }

                $discount = round($currentPrice * $percent / 100, 2);
                $newPrice = max($currentPrice - $discount, 0.0);

                return [$newPrice, $discount];

            case 'discount_value':
                $value = (float) ($promotion->discount_value ?? 0);
                if ($value <= 0) {
                    return [$currentPrice, 0.0];
                }

                $newPrice = max($currentPrice - $value, 0.0);
                $discount = $currentPrice - $newPrice;

                return [$newPrice, $discount];

            case 'free_item':
                // aici simplificăm: produs gratis = preț 0;
                // în practică vei implementa "Cumperi X, primești Y"
                return [0.0, $currentPrice];

            default:
                return [$currentPrice, 0.0];
        }
    }
}
