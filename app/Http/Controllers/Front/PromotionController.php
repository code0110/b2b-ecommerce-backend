<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Promotion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{
    /**
     * GET /api/promotions
     * Listă promoții pentru front (active / viitoare),
     * cu filtrare opțională după tip client.
     */
    public function index(Request $request)
    {
        $now = Carbon::now();

        $scope = $request->query('scope', 'current'); // current | upcoming | all
        $customerType = $request->query('customer_type'); // b2b | b2c | null

        $query = Promotion::query()
            ->where('status', 'active');

        if ($scope === 'current') {
            $query
                ->where(function ($q) use ($now) {
                    $q->whereNull('start_at')
                      ->orWhere('start_at', '<=', $now);
                })
                ->where(function ($q) use ($now) {
                    $q->whereNull('end_at')
                      ->orWhere('end_at', '>=', $now);
                });
        } elseif ($scope === 'upcoming') {
            $query->whereNotNull('start_at')
                  ->where('start_at', '>', $now);
        }

        if (in_array($customerType, ['b2b', 'b2c'])) {
            $query->where(function ($q) use ($customerType) {
                $q->where('customer_type', $customerType)
                  ->orWhere('customer_type', 'both');
            });
        }

        $paginator = $query
            ->orderByDesc('start_at')
            ->orderBy('name')
            ->paginate(12);

        $data = $paginator->getCollection()->map(function (Promotion $promo) use ($now) {
            return [
                'id'              => $promo->id,
                'slug'            => $promo->slug,
                'name'            => $promo->name,
                'short_description' => $promo->short_description,
                'status'          => $promo->status,
                'hero_image'      => $promo->hero_image,
                'banner_image'    => $promo->banner_image,
                'start_at'        => optional($promo->start_at)->toDateString(),
                'end_at'          => optional($promo->end_at)->toDateString(),
                'badge'           => $promo->is_exclusive ? 'Exclusiv' : 'Promoție',
                'period'          => $this->formatPeriod($promo),
                'segmentLabel'    => $this->segmentLabel($promo),
            ];
        });

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    /**
     * GET /api/promotions/{slug}
     * Landing promoție + produse asociate.
     */
    public function show(string $slug)
    {
        $now = Carbon::now();

        $promotion = Promotion::with(['categories', 'brands', 'customerGroups'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Produse asociate promoției prin pivot product_promotion
        $productIds = DB::table('product_promotion')
            ->where('promotion_id', $promotion->id)
            ->pluck('product_id');

        $productsQuery = Product::query()
            ->where('status', 'published')
            ->leftJoin('categories', 'products.main_category_id', '=', 'categories.id')
            ->select([
                'products.*',
                'categories.name as category_name',
            ]);

        // Logică filtrare în funcție de applies_to
        if ($promotion->applies_to === 'products') {
            $productsQuery->whereIn('products.id', $productIds);
        } elseif ($promotion->applies_to === 'categories') {
            $categoryIds = DB::table('promotion_category')
                ->where('promotion_id', $promotion->id)
                ->pluck('category_id');
            $productsQuery->whereIn('products.main_category_id', $categoryIds);
        } elseif ($promotion->applies_to === 'brands') {
            $brandIds = DB::table('promotion_brand')
                ->where('promotion_id', $promotion->id)
                ->pluck('brand_id');
            $productsQuery->whereIn('products.brand_id', $brandIds);
        } else {
            // applies_to = all sau altceva
            // Dacă e 'all', nu listăm toate produsele (ar fi prea multe).
            // Putem returna o listă goală sau logică specifică.
            // Pentru moment, dacă nu e 'products', 'categories' sau 'brands', lăsăm gol
            // doar dacă nu avem produse specificate explicit (fallback)
            if ($productIds->isNotEmpty()) {
                $productsQuery->whereIn('products.id', $productIds);
            } else {
                // Hack: forțează rezultat gol dacă nu știm ce să afișăm
                $productsQuery->whereRaw('1 = 0');
            }
        }

        $products = $productsQuery
            ->orderBy('products.name')
            ->limit(60)
            ->get()
            ->map(function ($p) {
                $price = $p->price_override ?? $p->list_price;

                return [
                    'id'           => $p->id,
                    'slug'         => $p->slug,
                    'name'         => $p->name,
                    'code'         => $p->internal_code,
                    'category'     => $p->category_name,
                    'price'        => (float) $price,
                    'is_new'       => (bool) $p->is_new,
                    'is_on_sale'   => (bool) $p->is_on_sale,
                    'is_promo'     => (bool) $p->is_promo,
                ];
            });

        return response()->json([
            'promotion' => [
                'id'                => $promotion->id,
                'slug'              => $promotion->slug,
                'name'              => $promotion->name,
                'short_description' => $promotion->short_description,
                'description'       => $promotion->description,
                'hero_image'        => $promotion->hero_image,
                'banner_image'      => $promotion->banner_image,
                'mobile_image'      => $promotion->mobile_image,
                'status'            => $promotion->status,
                'applies_to'        => $promotion->applies_to,
                'period'            => $this->formatPeriod($promotion),
                'segmentLabel'      => $this->segmentLabel($promotion),
            ],
            'products'  => $products,
        ]);
    }

    private function formatPeriod(Promotion $promotion): ?string
    {
        if (!$promotion->start_at && !$promotion->end_at) {
            return null;
        }

        if ($promotion->start_at && $promotion->end_at) {
            return $promotion->start_at->format('d.m.Y') . ' – ' . $promotion->end_at->format('d.m.Y');
        }

        if ($promotion->start_at && !$promotion->end_at) {
            return 'De la ' . $promotion->start_at->format('d.m.Y');
        }

        if (!$promotion->start_at && $promotion->end_at) {
            return 'Până la ' . $promotion->end_at->format('d.m.Y');
        }

        return null;
    }

    private function segmentLabel(Promotion $promotion): string
    {
        return match ($promotion->customer_type) {
            'b2b'  => 'Clienți B2B',
            'b2c'  => 'Clienți B2C',
            'both' => 'B2B & B2C',
            default => 'Toți clienții',
        };
    }
}
