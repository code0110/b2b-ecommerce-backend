<?php

namespace App\Services\Pricing;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ContractPrice;
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

        $items = [];
        $subtotal = 0.0;
        $discountTotal = 0.0;

        /** @var CartItem $item */
        foreach ($cart->items as $item) {
            $product = $item->product;
            if (!$product) {
                continue;
            }

            $basePrice = $this->getBasePrice($product, $customer);
            $lineSubtotal = $basePrice * $item->quantity;
            $subtotal += $lineSubtotal;

            // selectăm promoțiile eligibile pentru acest produs
            $eligiblePromos = $promotions
                ->filter(fn (Promotion $p) => $this->promotionAppliesToProduct($p, $product));

            $bestLineDiscount = 0.0;
            $appliedPromo = null;

            foreach ($eligiblePromos as $promotion) {
                // condiții legate de coș (min_cart_total, min_qty_per_product etc.)
                if ($promotion->min_qty_per_product > 0 && $item->quantity < $promotion->min_qty_per_product) {
                    continue;
                }

                if ($promotion->min_cart_total > 0 && $subtotal < (float) $promotion->min_cart_total) {
                    // atenție: aici folosim subtotal curent; pentru scenarii mai complexe ar trebui calculat separat
                    continue;
                }

                // calculăm discount-ul de linie în funcție de bonus_type
                $lineDiscount = 0.0;

                switch ($promotion->bonus_type) {
                    case 'discount_percent':
                        if ($promotion->discount_percent > 0) {
                            $lineDiscount = $lineSubtotal * ((float) $promotion->discount_percent / 100);
                        }
                        break;

                    case 'discount_value':
                        if ($promotion->discount_value > 0) {
                            // presupunem că discount_value se aplică per produs (unitate)
                            $lineDiscount = min($lineSubtotal, (float) $promotion->discount_value * $item->quantity);
                        }
                        break;

                    case 'free_item':
                        // exemplu simplu: dacă ai cantitatea minimă, una e gratuită
                        if ($promotion->min_qty_per_product > 0 && $item->quantity >= $promotion->min_qty_per_product) {
                            $lineDiscount = $basePrice; // o unitate gratuită
                        }
                        break;
                }

                if ($lineDiscount > $bestLineDiscount) {
                    $bestLineDiscount = $lineDiscount;
                    $appliedPromo = $promotion;
                }
            }

            $discountTotal += $bestLineDiscount;
            $lineTotal = $lineSubtotal - $bestLineDiscount;

            $items[] = [
                'id'                => $item->id,
                'product_id'        => $product->id,
                'product_name'      => $product->name,
                'product_slug'      => $product->slug,
                'quantity'          => $item->quantity,
                'unit_price'        => $basePrice,
                'line_subtotal'     => round($lineSubtotal, 2),
                'line_discount'     => round($bestLineDiscount, 2),
                'line_total'        => round($lineTotal, 2),
                'applied_promotion' => $appliedPromo ? [
                    'id'   => $appliedPromo->id,
                    'name' => $appliedPromo->name,
                    'slug' => $appliedPromo->slug,
                ] : null,
            ];
        }

        return [
            'items'          => $items,
            'subtotal'       => round($subtotal, 2),
            'discount_total' => round($discountTotal, 2),
            'total'          => round($subtotal - $discountTotal, 2),
        ];
    }
}
