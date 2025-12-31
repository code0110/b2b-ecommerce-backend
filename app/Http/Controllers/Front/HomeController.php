<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Promotion;
use App\Services\Pricing\PromotionPricingService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Homepage – date pentru:
     * - secțiunea Promoții (carduri promo)
     * - Produse noi
     * - Produse recomandate / promo
     *
     * Frontend-ul așteaptă cheile:
     *  - promotions
     *  - new_products
     *  - recommended_products
     */
    public function homepage(Request $request, PromotionPricingService $pricing)
    {
        $user = $request->user('sanctum');
        $customer = optional($user)->customer;

        // 1) PROMOȚII ACTIVE
        $promoCollection = $pricing
            ->getActivePromotionsForCustomer($customer)
            ->sortByDesc('start_at')
            ->take(6);

        $homePromotions = $promoCollection->map(function (Promotion $promo) {
            $start = $promo->start_at ? Carbon::parse($promo->start_at) : null;
            $end   = $promo->end_at ? Carbon::parse($promo->end_at) : null;

            $period = 'Permanent';
            if ($start && $end) {
                $period = $start->format('d.m.Y') . ' - ' . $end->format('d.m.Y');
            } elseif ($start && !$end) {
                $period = 'De la ' . $start->format('d.m.Y');
            } elseif (!$start && $end) {
                $period = 'Până la ' . $end->format('d.m.Y');
            }

            $segmentLabel = match ($promo->customer_type) {
                'b2b'  => 'Clienți B2B',
                'b2c'  => 'Clienți B2C',
                default => 'B2B & B2C',
            };

            return [
                'id'           => $promo->id,
                'slug'         => $promo->slug,
                'title'        => $promo->name,
                'badge'        => $this->badgeForPromotion($promo),
                'teaser'       => $promo->short_description ?: 'Campanie promoțională activă.',
                'period'       => $period,
                'segmentLabel' => $segmentLabel,
                'heroImage'    => $promo->hero_image,
                'bannerImage'  => $promo->banner_image,
                'mobileImage'  => $promo->mobile_image,
            ];
        })->values();

        // 2) PRODUSE NOI – max 8 produse
        $newProductsQuery = Product::query()
    ->with(['brand', 'mainCategory'])
    ->where('status', 'published')
    ->where('is_new', true)
            ->orderByDesc('created_at')
            ->limit(8);

        $newProducts = $newProductsQuery->get()->map(function (Product $product) use ($pricing, $customer) {
            return $pricing->formatProductForFrontend($product, $customer);
        })->values();

        // 3) PRODUSE RECOMANDATE / ÎN PROMOȚIE – max 8
        $recommendedQuery = Product::query()
            ->with(['brand', 'mainCategory'])
            ->where('status', 'published')
            ->where(function ($q) {
                $q->where('is_recommended', true)
                  ->orWhere('is_on_sale', true)
                  ->orWhere('is_promo', true);
            })
            ->orderByDesc('updated_at')
            ->limit(8);

        $recommendedProducts = $recommendedQuery->get()->map(function (Product $product) use ($pricing, $customer) {
            return $pricing->formatProductForFrontend($product, $customer);
        })->values();

        return response()->json([
            'promotions'           => $homePromotions,
            'new_products'         => $newProducts,
            'recommended_products' => $recommendedProducts,
        ]);
    }

    /**
     * Listă paginată de produse noi – /api/products/new
     * Folosită de pagina /noutati.
     */
    public function newProducts(Request $request, PromotionPricingService $pricing)
    {
        $customer = optional($request->user())->customer;

        $query = Product::query()
            ->with(['brand', 'category'])
            ->where('status', 'published')
            ->where('is_new', true)
            ->orderByDesc('created_at');

        $products = $query->paginate(24);

        $products->getCollection()->transform(function (Product $product) use ($pricing, $customer) {
            return $pricing->formatProductForFrontend($product, $customer);
        });

        return response()->json($products);
    }

    /**
     * Listă paginată de produse în promoție / reduceri – /api/products/discounted
     * Folosită de pagina /reduceri.
     */
    public function discountedProducts(Request $request, PromotionPricingService $pricing)
    {
        $customer = optional($request->user())->customer;

        $query = Product::query()
            ->with(['brand', 'category'])
            ->where('status', 'published')
            ->where(function ($q) {
                // Flag-urile din DB (is_on_sale, is_promo) + eventual price_override < list_price
                $q->where('is_on_sale', true)
                  ->orWhere('is_promo', true);
            })
            ->orderByDesc('updated_at');

        $products = $query->paginate(24);

        $products->getCollection()->transform(function (Product $product) use ($pricing, $customer) {
            return $pricing->formatProductForFrontend($product, $customer);
        });

        return response()->json($products);
    }

    /**
     * Determină badge-ul afișat pe cardul promoției (ex. „B2B Exclusive”, „-10%” etc.).
     */
    protected function badgeForPromotion(Promotion $promo): string
    {
        if ($promo->discount_percent) {
            return '-' . rtrim(rtrim(number_format($promo->discount_percent, 2, '.', ''), '0'), '.') . '%';
        }

        if ($promo->bonus_type === 'free_item') {
            return 'Produs gratuit';
        }

        if ($promo->customer_type === 'b2b') {
            return 'B2B Exclusive';
        }

        if ($promo->customer_type === 'b2c') {
            return 'Promo Retail';
        }

        return 'Promoție';
    }
}
