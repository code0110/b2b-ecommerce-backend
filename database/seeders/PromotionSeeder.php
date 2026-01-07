<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Promotion;
use App\Models\PromotionTier;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\CustomerGroup;
use Carbon\Carbon;
use Illuminate\Support\Str;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Cleanup old promotions
        Promotion::query()->delete();
        PromotionTier::query()->delete();

        $now = Carbon::now();
        $endOfYear = Carbon::now()->endOfYear();

        // 2. Fetch some existing data
        $products = Product::inRandomOrder()->limit(10)->get();
        $categories = Category::inRandomOrder()->limit(3)->get();
        $brands = Brand::inRandomOrder()->limit(2)->get();
        $groups = CustomerGroup::inRandomOrder()->limit(2)->get();

        if ($products->isEmpty()) {
            $this->command->info('No products found. Skipping promotion seeding.');
            return;
        }

        // ==========================================
        // 1. Standard Discount (Percentage)
        // ==========================================
        $promo1 = Promotion::create([
            'name' => 'Reduceri de Vară - Toate Categoriile',
            'slug' => 'summer-sale-2026',
            'description' => '20% reducere la categoriile selectate pentru toți clienții.',
            'status' => 'active',
            'start_at' => $now,
            'end_at' => $endOfYear,
            'type' => 'standard',
            'value_type' => 'percent',
            'value' => 20,
            'customer_type' => 'both',
            'priority' => 0,
            'is_iterative' => true,
        ]);
        
        if ($categories->isNotEmpty()) {
            $promo1->categories()->attach($categories->pluck('id'));
        }

        // ==========================================
        // 2. Standard Discount (Fixed Amount)
        // ==========================================
        $product = $products->first();
        $promo2 = Promotion::create([
            'name' => 'Discount 100 RON - ' . Str::limit($product->name, 20),
            'slug' => 'fixed-discount-100',
            'description' => 'Reducere fixă de 100 RON la acest produs specific.',
            'status' => 'active',
            'start_at' => $now,
            'end_at' => $endOfYear,
            'type' => 'standard',
            'value_type' => 'fixed_amount',
            'value' => 100,
            'customer_type' => 'both',
            'priority' => 10,
        ]);
        $promo2->products()->attach($product->id);

        // ==========================================
        // 3. Volume Discount (Tiers)
        // ==========================================
        $volumeProduct = $products->get(1) ?? $products->first();
        $promo3 = Promotion::create([
            'name' => 'Volum: Cumpără mai mult, plătește mai puțin',
            'slug' => 'volume-discount-tiers',
            'description' => 'Discount progresiv în funcție de cantitate.',
            'status' => 'active',
            'start_at' => $now,
            'end_at' => $endOfYear,
            'type' => 'volume',
            'value_type' => 'percent', // Base type, overridden by tiers usually or used as fallback
            'value' => 0,
            'customer_type' => 'b2b', // Only B2B usually buys in bulk
            'priority' => 50,
        ]);
        $promo3->products()->attach($volumeProduct->id);

        // Add Tiers
        PromotionTier::create([
            'promotion_id' => $promo3->id,
            'min_qty' => 5,
            'value' => 5, // 5%
        ]);
        PromotionTier::create([
            'promotion_id' => $promo3->id,
            'min_qty' => 10,
            'value' => 10, // 10%
        ]);
        PromotionTier::create([
            'promotion_id' => $promo3->id,
            'min_qty' => 50,
            'value' => 20, // 20%
        ]);

        // ==========================================
        // 4. Free Shipping
        // ==========================================
        $promo4 = Promotion::create([
            'name' => 'Livrare Gratuită peste 500 RON',
            'slug' => 'free-shipping-500',
            'description' => 'Livrare gratuită pentru comenzi mai mari de 500 RON.',
            'status' => 'active',
            'start_at' => $now,
            'end_at' => $endOfYear,
            'type' => 'shipping',
            'value_type' => 'fixed_amount', // 0 shipping cost
            'value' => 0,
            'min_cart_total' => 500,
            'customer_type' => 'both',
            'priority' => 100,
        ]);

        // ==========================================
        // 5. Fixed Price (Special Price)
        // ==========================================
        $fixedPriceProduct = $products->get(2) ?? $products->first();
        $promo5 = Promotion::create([
            'name' => 'Super Preț Fix: ' . Str::limit($fixedPriceProduct->name, 20),
            'slug' => 'fixed-price-promo',
            'description' => 'Acest produs are un preț special fix de 999 RON.',
            'status' => 'active',
            'start_at' => $now,
            'end_at' => $endOfYear,
            'type' => 'special_price',
            'value_type' => 'fixed_price',
            'value' => 999,
            'customer_type' => 'both',
            'priority' => 80,
        ]);
        $promo5->products()->attach($fixedPriceProduct->id);

        // ==========================================
        // 6. Gift Product
        // ==========================================
        $giftProduct = $products->last(); // The item given as gift
        $targetProduct = $products->first(); // Buy this to get the gift

        $promo6 = Promotion::create([
            'name' => 'Cumperi X primești Y Cadou',
            'slug' => 'gift-promo-x-y',
            'description' => 'La achiziția produsului principal, primești un accesoriu cadou.',
            'status' => 'active',
            'start_at' => $now,
            'end_at' => $endOfYear,
            'type' => 'gift',
            'value_type' => 'percent', // 100% discount on the gift
            'value' => 100,
            'customer_type' => 'both',
            'priority' => 60,
            // Store gift product ID in settings json as per convention (or logic)
            'settings' => ['gift_product_id' => $giftProduct->id], 
        ]);
        
        // The promotion applies when buying the target product
        $promo6->products()->attach($targetProduct->id);

        $this->command->info('Promotions seeded successfully!');
    }
}
