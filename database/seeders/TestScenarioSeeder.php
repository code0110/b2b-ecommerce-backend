<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Promotion;
use App\Models\Coupon;
use App\Models\DiscountRule;
use App\Models\User;
use App\Models\Role;
use App\Models\ReceiptBook;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TestScenarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 0. Cleanup (Safe check)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Promotion::truncate();
        Coupon::truncate();
        Product::truncate();
        Category::truncate();
        Brand::truncate();
        CustomerGroup::truncate();
        Customer::truncate();
        
        // Truncate Pivot Tables
        DB::table('category_product')->truncate();
        DB::table('promotion_product')->truncate();
        if (Schema::hasTable('product_promotion')) {
            DB::table('product_promotion')->truncate();
        }
        DB::table('promotion_category')->truncate();
        if (Schema::hasTable('category_promotion')) {
            DB::table('category_promotion')->truncate();
        }
        DB::table('promotion_brand')->truncate();
        if (Schema::hasTable('brand_promotion')) {
            DB::table('brand_promotion')->truncate();
        }
        DB::table('promotion_customer_group')->truncate();
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->command->info('Database cleaned. Starting seed...');

        // 1. Customer Groups
        $groupB2B = CustomerGroup::create([
            'name' => 'Distribuitori B2B',
            // 'code' => 'B2B_DIST', // Removed as column doesn't exist
            'type' => 'b2b',
            'default_discount_percent' => 5.00, // Correct column name
            'is_active' => true
        ]);

        $groupVIP = CustomerGroup::create([
            'name' => 'VIP Retail',
            // 'code' => 'VIP_RET', // Removed
            'type' => 'b2c',
            'default_discount_percent' => 2.00,
            'is_active' => true
        ]);

        // 2. Customers
        Customer::create([
            'name' => 'John Doe Company',
            'type' => 'b2c', // Enum: b2c, b2b
            'email' => 'john@test.com',
            'phone' => '0700000001',
            // 'password' => bcrypt('password'), // Customers might be linked to Users table for login, Customer table is CRM profile
            'group_id' => $groupB2B->id,
            'is_active' => true
        ]);

        Customer::create([
            'name' => 'Jane Smith Ltd',
            'type' => 'b2b', // Enum: b2c, b2b
            'cif' => 'RO123456',
            'reg_com' => 'J40/123/2020',
            'email' => 'jane@test.com',
            'phone' => '0700000002',
            'group_id' => $groupVIP->id,
            'is_active' => true,
            'allow_global_discount' => true,
            'allow_line_discount' => true,
        ]);

        // 2.a. Users: Admin, Director & Agents
        $adminRole    = Role::where('slug', 'admin')->first() ?: Role::where('code', 'admin')->first();
        $directorRole = Role::where('slug', 'sales_director')->first() ?: Role::where('code', 'sales_director')->first();
        $agentRole    = Role::where('slug', 'sales_agent')->first() ?: Role::where('code', 'agent')->first();

        $adminUser = User::firstOrCreate(
            ['email' => 'cod.binar@gmail.com'],
            [
                'first_name' => 'Admin',
                'last_name'  => 'System',
                'phone'      => '0700000000',
                'password'   => bcrypt('password'),
                'is_active'  => true,
            ]
        );
        if ($adminRole) {
            $adminUser->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        $director = User::firstOrCreate(
            ['email' => 'director@example.com'],
            [
                'first_name' => 'Diana',
                'last_name'  => 'Director',
                'phone'      => '0711111111',
                'password'   => bcrypt('password'),
                'is_active'  => true,
            ]
        );
        if ($directorRole) {
            $director->roles()->syncWithoutDetaching([$directorRole->id]);
        }

        $agent1 = User::firstOrCreate(
            ['email' => 'agent1@example.com'],
            [
                'first_name' => 'Alex',
                'last_name'  => 'Agent',
                'phone'      => '0722222222',
                'password'   => bcrypt('password'),
                'is_active'  => true,
            ]
        );
        if ($agentRole) {
            $agent1->roles()->syncWithoutDetaching([$agentRole->id]);
        }

        $agent2 = User::firstOrCreate(
            ['email' => 'agent2@example.com'],
            [
                'first_name' => 'Bianca',
                'last_name'  => 'Agent',
                'phone'      => '0733333333',
                'password'   => bcrypt('password'),
                'is_active'  => true,
            ]
        );
        if ($agentRole) {
            $agent2->roles()->syncWithoutDetaching([$agentRole->id]);
        }

        // Receipt book for Director (numerar)
        ReceiptBook::firstOrCreate(
            ['user_id' => $director->id, 'series' => 'B2B', 'start_number' => 101],
            ['end_number' => 150, 'current_number' => 101, 'is_active' => true]
        );

        // Extra demo customers assigned to agents under this director
        $demoCustomers = [];
        for ($i = 1; $i <= 8; $i++) {
            $demoCustomers[] = [
                'name' => "Demo Customer {$i}",
                'type' => 'b2b',
                'email' => "demo{$i}@test.com",
                'phone' => '07000000' . str_pad((string)$i, 2, '0', STR_PAD_LEFT),
                'group_id' => $groupB2B->id,
                'is_active' => true,
                'payment_terms_days' => 30,
                'credit_limit' => 50000,
                'current_balance' => $i % 3 === 0 ? 2500 + $i * 100 : 0,
                'currency' => 'RON',
                // sales links
                'sales_director_user_id' => $director->id,
                'agent_user_id' => $i % 2 === 0 ? $agent1->id : $agent2->id,
            ];
        }
        foreach ($demoCustomers as $c) {
            Customer::create($c);
        }

        // 3. Brands (construcții)
        $brandMetalRom = Brand::create(['name' => 'MetalRom', 'slug' => 'metalrom', 'is_published' => true]);
        $brandSteelPro  = Brand::create(['name' => 'SteelPro', 'slug' => 'steelpro', 'is_published' => true]);
        $brandKnauf    = Brand::create(['name' => 'Knauf', 'slug' => 'knauf', 'is_published' => true]);
        $brandHQS      = Brand::create(['name' => 'HQS', 'slug' => 'hqs', 'is_published' => true]);

        // 4. Categorii (aliniate cu tematica construcțiilor Metal-ROM)
        $catConstructii      = Category::create(['name' => 'Construcții', 'slug' => 'constructii', 'is_published' => true]);
        $catGipsCarton       = Category::create(['name' => 'Gips Carton', 'slug' => 'gips-carton', 'parent_id' => $catConstructii->id, 'is_published' => true]);
        $catProfileMetalice  = Category::create(['name' => 'Profile Metalice', 'slug' => 'profile-metalice', 'parent_id' => $catConstructii->id, 'is_published' => true]);
        $catArmaturi         = Category::create(['name' => 'Armături & Oțel', 'slug' => 'armaturi-otel', 'parent_id' => $catConstructii->id, 'is_published' => true]);
        $catPlaseSudate      = Category::create(['name' => 'Plase Sudate', 'slug' => 'plase-sudate', 'parent_id' => $catConstructii->id, 'is_published' => true]);
        $catSudura           = Category::create(['name' => 'Sudură', 'slug' => 'sudura', 'parent_id' => $catConstructii->id, 'is_published' => true]);
        $catFeronerie        = Category::create(['name' => 'Feronerie', 'slug' => 'feronerie', 'parent_id' => $catConstructii->id, 'is_published' => true]);

        // 5. Produse (construcții)
        $prodGipsCarton = Product::create([
            'name' => 'Placă gips-carton 12.5mm',
            'slug' => 'placa-gips-carton-12-5',
            'internal_code' => 'PGC-12.5',
            'brand_id' => $brandKnauf->id,
            'main_category_id' => $catGipsCarton->id,
            'list_price' => 25.50,
            'stock_qty' => 240,
            'status' => 'published',
            'visibility' => 'public',
            'is_promo' => true,
            'is_new' => false,
            'tags' => ['gips-carton', 'interior'],
            'unit_of_measure' => 'buc',
            'vat_rate' => 19.00
        ]);
        $prodGipsCarton->categories()->attach([$catConstructii->id, $catGipsCarton->id]);

        $prodProfilUW50 = Product::create([
            'name' => 'Profil metalic UW 50',
            'slug' => 'profil-metalic-uw-50',
            'internal_code' => 'UW-50',
            'brand_id' => $brandSteelPro->id,
            'main_category_id' => $catProfileMetalice->id,
            'list_price' => 18.00,
            'stock_qty' => 150,
            'status' => 'published',
            'visibility' => 'public',
            'is_promo' => false,
            'tags' => ['structuri', 'pereți'],
            'unit_of_measure' => 'ml',
            'vat_rate' => 19.00
        ]);
        $prodProfilUW50->categories()->attach([$catConstructii->id, $catProfileMetalice->id]);

        $prodPlasaSudata4mm = Product::create([
            'name' => 'Plasă sudată 4mm',
            'slug' => 'plasa-sudata-4mm',
            'internal_code' => 'PLS-4',
            'brand_id' => $brandMetalRom->id,
            'main_category_id' => $catPlaseSudate->id,
            'list_price' => 120.00,
            'stock_qty' => 500,
            'status' => 'published',
            'visibility' => 'public',
            'tags' => ['armare', 'beton'],
            'unit_of_measure' => 'mp',
            'vat_rate' => 19.00
        ]);
        $prodPlasaSudata4mm->categories()->attach([$catConstructii->id, $catPlaseSudate->id]);

        $prodTeavaRect = Product::create([
            'name' => 'Țeavă rectangulară 40x20',
            'slug' => 'teava-rectangulara-40x20',
            'internal_code' => 'TR-40x20',
            'brand_id' => $brandSteelPro->id,
            'main_category_id' => $catArmaturi->id,
            'list_price' => 35.00,
            'stock_qty' => 800,
            'status' => 'published',
            'visibility' => 'public',
            'tags' => ['structural', 'metal'],
            'unit_of_measure' => 'ml',
            'vat_rate' => 19.00
        ]);
        $prodTeavaRect->categories()->attach([$catConstructii->id, $catArmaturi->id]);

        $prodSuruburi25 = Product::create([
            'name' => 'Șuruburi gips-carton 25mm (cutie)',
            'slug' => 'suruburi-gips-carton-25mm',
            'internal_code' => 'SUR-25',
            'brand_id' => $brandHQS->id,
            'main_category_id' => $catFeronerie->id,
            'list_price' => 55.50,
            'stock_qty' => 1200,
            'status' => 'published',
            'visibility' => 'public',
            'tags' => ['feronerie', 'gips-carton'],
            'unit_of_measure' => 'cutie',
            'vat_rate' => 19.00
        ]);
        $prodSuruburi25->categories()->attach([$catConstructii->id, $catFeronerie->id]);

        // 6. Promoții - ACOPERĂ TOATE TIPURILE (tematică construcții)

        // Tip 1: Discount procentual pe categorie (10% Gips Carton)
        Promotion::create([
            'name' => '10% Reducere Gips Carton',
            'slug' => 'promo-gips-carton-10',
            'status' => 'active',
            'start_at' => now()->subDays(1),
            'end_at' => now()->addDays(30),
            'applies_to' => 'categories',
            'type' => 'standard',
            'value_type' => 'percent',
            'value' => 10.00,
            'customer_type' => 'both',
        ])->categories()->attach($catGipsCarton->id);

        // Tip 2: Discount valoric fix pe produs (150 RON Țeavă rectangulară)
        Promotion::create([
            'name' => '150 RON Discount Țeavă 40x20',
            'slug' => 'promo-teava-150',
            'status' => 'active',
            'start_at' => now()->subDays(1),
            'end_at' => now()->addDays(30),
            'applies_to' => 'products',
            'type' => 'standard',
            'value_type' => 'fixed_amount',
            'value' => 150.00,
            'customer_type' => 'both',
        ])->products()->attach($prodTeavaRect->id);

        // Tip 3: Discount pe brand (5% SteelPro)
        Promotion::create([
            'name' => '5% Reducere SteelPro',
            'slug' => 'promo-steelpro-5',
            'status' => 'active',
            'start_at' => now()->subDays(1),
            'end_at' => now()->addDays(30),
            'applies_to' => 'brands',
            'type' => 'standard',
            'value_type' => 'percent',
            'value' => 5.00,
            'customer_type' => 'both',
        ])->brands()->attach($brandSteelPro->id);

        // Tip 4: Prag coș (100 RON reducere > 5000 RON)
        Promotion::create([
            'name' => '100 RON Off Orders > 5000',
            'slug' => 'promo-cart-5000',
            'status' => 'active',
            'start_at' => now()->subDays(1),
            'end_at' => now()->addDays(30),
            'applies_to' => 'all', // Cart level usually implies applies to all or checked via logic
            'min_cart_total' => 5000.00,
            'type' => 'standard',
            'value_type' => 'fixed_amount',
            'value' => 100.00,
            'customer_type' => 'both',
        ]);

        // Tip 5: Discount volum (cumpără minim 20 mp plasă, 15% reducere)
        Promotion::create([
            'name' => 'Volum: Plasă sudată -15% la cantitate',
            'slug' => 'promo-plasa-volum-15',
            'status' => 'active',
            'start_at' => now()->subDays(1),
            'end_at' => now()->addDays(30),
            'applies_to' => 'products',
            'min_qty_per_product' => 20,
            'type' => 'volume',
            'value_type' => 'percent',
            'value' => 15.00,
            'customer_type' => 'both',
        ])->products()->attach($prodPlasaSudata4mm->id);

        // Tip 6: Produs cadou (cumperi Profile UW, primești șuruburi cadou)
        Promotion::create([
            'name' => 'Cadou: Șuruburi la Profile UW',
            'slug' => 'promo-cadou-suruburi',
            'status' => 'active',
            'start_at' => now()->subDays(1),
            'end_at' => now()->addDays(30),
            'applies_to' => 'products',
            'min_qty_per_product' => 20,
            'type' => 'gift',
            'value_type' => 'percent',
            'value' => 100,
            'settings' => ['gift_product_id' => $prodSuruburi25->id, 'gift_qty' => 1],
            'customer_type' => 'both',
        ])->products()->attach($prodProfilUW50->id);

        // 7. Coupons
        Coupon::create([
            'code' => 'WELCOME10',
            'discount_type' => 'percent',
            'discount_value' => 10.00,
            'is_active' => true,
            'is_stackable' => false, // Can't combine with promos
            'start_at' => now()->subDay(),
            'end_at' => now()->addMonth(),
        ]);

        Coupon::create([
            'code' => 'EXTRA50',
            'discount_type' => 'fixed_cart',
            'discount_value' => 50.00,
            'min_cart_value' => 500.00,
            'is_active' => true,
            'is_stackable' => true, // Can combine
            'start_at' => now()->subDay(),
            'end_at' => now()->addMonth(),
        ]);

        $this->command->info('Test scenario seeded successfully!');

        // 8. Discount Rules (acoperire: global, grup, rol, categorie, produs)
        DiscountRule::truncate();
        DiscountRule::create([
            'name' => 'Limită globală discount',
            'rule_type' => 'max_percent',
            'target_type' => 'global',
            'target_id' => null,
            'limit_percent' => 20.00,
            'apply_to_total' => true,
            'active' => true,
        ]);
        DiscountRule::create([
            'name' => 'Distribuitori B2B până la 25%',
            'rule_type' => 'max_percent',
            'target_type' => 'customer_group',
            'target_id' => $groupB2B->id,
            'limit_percent' => 25.00,
            'apply_to_total' => true,
            'active' => true,
        ]);
        DiscountRule::create([
            'name' => 'Agent max 10%',
            'rule_type' => 'max_percent',
            'target_type' => 'role',
            'target_id' => $agentRole?->id,
            'limit_percent' => 10.00,
            'apply_to_total' => false,
            'active' => true,
        ]);
        DiscountRule::create([
            'name' => 'Director max 30%',
            'rule_type' => 'max_percent',
            'target_type' => 'role',
            'target_id' => $directorRole?->id,
            'limit_percent' => 30.00,
            'apply_to_total' => true,
            'active' => true,
        ]);
        DiscountRule::create([
            'name' => 'Gips Carton max 20%',
            'rule_type' => 'max_percent',
            'target_type' => 'category',
            'target_id' => $catGipsCarton->id,
            'limit_percent' => 20.00,
            'apply_to_total' => false,
            'active' => true,
        ]);
        DiscountRule::create([
            'name' => 'Profil UW max 15%',
            'rule_type' => 'max_percent',
            'target_type' => 'product',
            'target_id' => $prodProfilUW50->id,
            'limit_percent' => 15.00,
            'apply_to_total' => false,
            'active' => true,
        ]);
    }
}
