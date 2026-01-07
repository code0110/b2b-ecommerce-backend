<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Promotion;
use App\Models\Product;
use App\Models\Category;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class PromotionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Clean existing promotions and pivot tables
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('promotions')->truncate();
        DB::table('promotion_product')->truncate();
        DB::table('promotion_category')->truncate();
        DB::table('promotion_customer')->truncate();
        DB::table('promotion_brand')->truncate();
        // Handle potential naming variations from migrations
        if (Schema::hasTable('customer_group_promotion')) {
            DB::table('customer_group_promotion')->truncate();
        }
        if (Schema::hasTable('promotion_customer_group')) {
            DB::table('promotion_customer_group')->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $now = Carbon::now();
        $endNextMonth = Carbon::now()->addMonth();

        // 2. Global Discount (10%)
        Promotion::create([
            'name' => 'Discount Global Vara',
            'slug' => 'global-summer-10',
            'description' => '10% reducere la toate produsele.',
            'start_at' => $now->copy()->subDay(),
            'end_at' => $endNextMonth,
            'status' => 'active',
            'is_exclusive' => false,
            'bonus_type' => 'discount_percent',
            'benefit_type' => 'discount_percent',
            'discount_percent' => 10,
            'applies_to' => 'all',
            'customer_type' => 'both',
        ]);

        // 3. Category Discount (15% for Category 1)
        $catPromo = Promotion::create([
            'name' => 'Reducere Categorie Materiale',
            'slug' => 'cat-materials-15',
            'description' => '15% reducere la categoria Materiale de Construcții.',
            'start_at' => $now->copy()->subDay(),
            'end_at' => $endNextMonth,
            'status' => 'active',
            'is_exclusive' => false,
            'bonus_type' => 'discount_percent',
            'benefit_type' => 'discount_percent',
            'discount_percent' => 15,
            'applies_to' => 'categories',
            'customer_type' => 'both',
        ]);
        $catPromo->categories()->attach([1]);

        // 4. Product Discount (20% for Product 1)
        $prodPromo = Promotion::create([
            'name' => 'Super Ofertă Produs Top',
            'slug' => 'prod-top-20',
            'description' => '20% reducere la cel mai vândut produs.',
            'start_at' => $now->copy()->subDay(),
            'end_at' => $endNextMonth,
            'status' => 'active',
            'is_exclusive' => false,
            'bonus_type' => 'discount_percent',
            'benefit_type' => 'discount_percent',
            'discount_percent' => 20,
            'applies_to' => 'products',
            'customer_type' => 'both',
        ]);
        $prodPromo->products()->attach([1]);

        // 5. Value Discount (50 RON off for orders > 500 RON)
        Promotion::create([
            'name' => 'Voucher 50 RON',
            'slug' => 'voucher-50',
            'description' => '50 RON reducere la comenzi de peste 500 RON.',
            'start_at' => $now->copy()->subDay(),
            'end_at' => $endNextMonth,
            'status' => 'active',
            'is_exclusive' => false,
            'bonus_type' => 'discount_value',
            'benefit_type' => 'discount_fixed',
            'discount_value' => 50,
            'min_cart_total' => 500,
            'applies_to' => 'all',
            'customer_type' => 'both',
        ]);

        // 6. B2B Exclusive (5% extra)
        Promotion::create([
            'name' => 'Extra Discount B2B',
            'slug' => 'b2b-extra-5',
            'description' => '5% reducere suplimentară pentru parteneri B2B.',
            'start_at' => $now->copy()->subDay(),
            'end_at' => $endNextMonth,
            'status' => 'active',
            'is_exclusive' => false,
            'bonus_type' => 'discount_percent',
            'benefit_type' => 'discount_percent',
            'discount_percent' => 5,
            'applies_to' => 'all',
            'customer_type' => 'b2b',
        ]);

        // 7. Customer Specific (Jane Smith Ltd - Customer 1)
        // Note: Relation is via User pivot usually, but we have customer_promotion pivot too.
        // Let's attach to Customer ID 1.
        $custPromo = Promotion::create([
            'name' => 'Ofertă Specială Jane Smith',
            'slug' => 'jane-smith-special',
            'description' => 'Prețuri speciale pentru clientul Jane Smith.',
            'start_at' => $now->copy()->subDay(),
            'end_at' => $endNextMonth,
            'status' => 'active',
            'is_exclusive' => true,
            'bonus_type' => 'discount_percent',
            'benefit_type' => 'discount_percent',
            'discount_percent' => 25,
            'applies_to' => 'all',
            'customer_type' => 'b2b',
        ]);
        // Attaching to customer pivot directly if model supports it, or via user.
        // Model has `customers()` relation to `User` via `promotion_customer`.
        // Wait, the migration `customer_promotion` has `customer_id`.
        // The Model `Promotion` has `public function customers(): BelongsToMany { return $this->belongsToMany(User::class, 'promotion_customer'); }`
        // But `promotion_customer` table has `customer_id`. User table has `id`.
        // If `customer_promotion` links to `customers` table, then the model relation is wrong (it links to User).
        // Let's assume the seeder should use DB insert for safety or fix model later.
        // For now I'll use DB facade for this pivot to be sure.
        DB::table('customer_promotion')->insert([
            'promotion_id' => $custPromo->id,
            'customer_id' => 1,
        ]);
    }
}
