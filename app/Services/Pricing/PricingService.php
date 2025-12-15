<?php

namespace App\Services\Pricing;

use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use App\Models\Cart;
use Illuminate\Support\Collection;

class PricingService
{
    /**
     * Calculează prețul unui produs pentru un anumit client,
     * incluzând promoția „cea mai bună” găsită.
     */
    public function priceForProduct(Product $product, ?User $user = null): array
    {
        $basePrice = $product->price_override ?? $product->list_price ?? 0;

        $promotions = Promotion::active()->get()
            ->filter(fn (Promotion $p) =>
                $p->appliesToCustomer($user) && $p->appliesToProduct($product)
            );

        if ($promotions->isEmpty()) {
            return [
                'base_price'       => $basePrice,
                'final_price'      => $basePrice,
                'applied_promotions' => [],
                'discount_amount'  => 0.0,
            ];
        }

        $bestPromotion = null;
        $bestFinalUnitPrice = $basePrice;
        $bestDiscountAmount = 0.0;

        foreach ($promotions as $promotion) {
            $res = $promotion->calculateLineDiscount($basePrice, 1);
            if ($res['final_unit_price'] < $bestFinalUnitPrice) {
                $bestFinalUnitPrice = $res['final_unit_price'];
                $bestDiscountAmount = $res['discount_amount'];
                $bestPromotion = $promotion;
            }
        }

        return [
            'base_price'       => $basePrice,
            'final_price'      => $bestFinalUnitPrice,
            'discount_amount'  => $bestDiscountAmount,
            'applied_promotions' => $bestPromotion ? [
                [
                    'id'    => $bestPromotion->id,
                    'name'  => $bestPromotion->name,
                    'slug'  => $bestPromotion->slug,
                    'type'  => $bestPromotion->discount_type,
                    'value' => $bestPromotion->discount_value,
                ],
            ] : [],
        ];
    }

    /**
     * Calculează prețurile pentru întregul coș:
     * - preț pe linie,
     * - discount pe linie,
     * - total coș.
     */
    public function priceCart(Cart $cart, ?User $user = null): array
    {
        $cart->loadMissing([
            'items.product',
        ]);

        $lineItems = [];
        $subtotal = 0.0;
        $totalDiscount = 0.0;
        $total = 0.0;

        foreach ($cart->items as $item) {
            $product = $item->product;
            if (!$product) {
                continue;
            }

            $basePrice = $product->price_override ?? $product->list_price ?? 0;
            $qty = $item->quantity ?? 1;

            $promotions = Promotion::active()->get()
                ->filter(fn (Promotion $p) =>
                    $p->appliesToCustomer($user) && $p->appliesToProduct($product)
                );

            $finalUnitPrice = $basePrice;
            $discountAmount = 0.0;
            $appliedPromotion = null;

            if ($promotions->isNotEmpty()) {
                $bestFinal = $basePrice;
                $bestDiscount = 0.0;
                $bestPromotion = null;

                foreach ($promotions as $promotion) {
                    $res = $promotion->calculateLineDiscount($basePrice, $qty);
                    $candidateFinalUnit = $res['final_unit_price'];
                    if ($candidateFinalUnit < $bestFinal) {
                        $bestFinal = $candidateFinalUnit;
                        $bestDiscount = $res['discount_amount'];
                        $bestPromotion = $promotion;
                    }
                }

                $finalUnitPrice = $bestFinal;
                $discountAmount = $bestDiscount;
                $appliedPromotion = $bestPromotion;
            }

            $lineSubtotal = $basePrice * $qty;
            $lineTotal = $finalUnitPrice * $qty;

            $subtotal += $lineSubtotal;
            $totalDiscount += $discountAmount;
            $total += $lineTotal;

            $lineItems[] = [
                'id'                  => $item->id,
                'product_id'          => $product->id,
                'name'                => $product->name,
                'slug'                => $product->slug,
                'quantity'            => $qty,
                'base_unit_price'     => $basePrice,
                'final_unit_price'    => $finalUnitPrice,
                'line_subtotal'       => $lineSubtotal,
                'line_total'          => $lineTotal,
                'discount_amount'     => $discountAmount,
                'applied_promotion'   => $appliedPromotion ? [
                    'id'    => $appliedPromotion->id,
                    'name'  => $appliedPromotion->name,
                    'slug'  => $appliedPromotion->slug,
                    'type'  => $appliedPromotion->discount_type,
                    'value' => $appliedPromotion->discount_value,
                ] : null,
            ];
        }

        return [
            'items'          => $lineItems,
            'subtotal'       => $subtotal,
            'total_discount' => $totalDiscount,
            'total'          => $total,
        ];
    }
}
