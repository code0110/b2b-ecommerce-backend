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

        // 3. Brands
        $brandApple = Brand::create(['name' => 'Apple', 'slug' => 'apple', 'is_published' => true]);
        $brandSamsung = Brand::create(['name' => 'Samsung', 'slug' => 'samsung', 'is_published' => true]);
        $brandLogitech = Brand::create(['name' => 'Logitech', 'slug' => 'logitech', 'is_published' => true]);

        // 4. Categories
        $catElectronics = Category::create(['name' => 'Electronics', 'slug' => 'electronics', 'is_published' => true]);
        $catLaptops = Category::create(['name' => 'Laptops', 'slug' => 'laptops', 'parent_id' => $catElectronics->id, 'is_published' => true]);
        $catAccessories = Category::create(['name' => 'Accessories', 'slug' => 'accessories', 'parent_id' => $catElectronics->id, 'is_published' => true]);

        // 5. Products
        
        // Product A: Expensive Laptop (Apple)
        $prodMacbook = Product::create([
            'name' => 'MacBook Pro 16',
            'slug' => 'macbook-pro-16',
            'internal_code' => 'MBP16',
            // 'sku' => 'MBP16-2024', // removed
            'brand_id' => $brandApple->id,
            'main_category_id' => $catLaptops->id,
            'list_price' => 12000.00, // Base Price
            'stock_qty' => 50,
            'status' => 'published',
            'visibility' => 'public',
            'is_promo' => false,
            'is_new' => true,
            'tags' => ['premium', 'new'],
            'unit_of_measure' => 'buc',
            'vat_rate' => 19.00
        ]);
        $prodMacbook->categories()->attach([$catElectronics->id, $catLaptops->id]);

        // Product B: Mid-range Phone (Samsung)
        $prodGalaxy = Product::create([
            'name' => 'Samsung Galaxy S24',
            'slug' => 'samsung-galaxy-s24',
            'internal_code' => 'SGS24',
            // 'sku' => 'SGS24-BLK', // removed
            'brand_id' => $brandSamsung->id,
            'main_category_id' => $catElectronics->id,
            'list_price' => 4000.00,
            'stock_qty' => 100,
            'status' => 'published',
            'visibility' => 'public',
            'is_promo' => true,
            'tags' => ['promo', 'bestseller'],
            'vat_rate' => 19.00
        ]);
        
        // Product C: Accessory (Logitech Mouse) - Good for Free Item promo
        $prodMouse = Product::create([
            'name' => 'Logitech MX Master 3S',
            'slug' => 'logitech-mx-master-3s',
            'internal_code' => 'MXM3S',
            // 'sku' => 'MXM3S-GRY', // removed
            'brand_id' => $brandLogitech->id,
            'main_category_id' => $catAccessories->id,
            'list_price' => 500.00,
            'stock_qty' => 200,
            'status' => 'published',
            'visibility' => 'public',
            'vat_rate' => 19.00
        ]);
        
        // Product D: Bulk Item (Cable)
        $prodCable = Product::create([
            'name' => 'USB-C Cable',
            'slug' => 'usb-c-cable',
            'internal_code' => 'CABLE1',
            'brand_id' => $brandSamsung->id,
            'main_category_id' => $catAccessories->id,
            'list_price' => 50.00,
            'stock_qty' => 1000,
            'status' => 'published',
            'visibility' => 'public',
            'vat_rate' => 19.00
        ]);

        // 6. Promotions - COVERING ALL TYPES

        // Type 1: Simple Discount Percent on Product (10% off MacBook)
        Promotion::create([
            'name' => '10% Discount MacBook',
            'slug' => 'promo-macbook-10',
            'status' => 'active',
            'start_at' => now()->subDays(1),
            'end_at' => now()->addDays(30),
            // 'priority' => 10,
            'applies_to' => 'products',
            'type' => 'standard',
            'value_type' => 'percent',
            'value' => 10.00,
            'customer_type' => 'both',
        ])->products()->attach($prodMacbook->id);

        // Type 2: Fixed Discount Value on Product (200 RON off Galaxy)
        Promotion::create([
            'name' => '200 RON Discount Galaxy',
            'slug' => 'promo-galaxy-200',
            'status' => 'active',
            'start_at' => now()->subDays(1),
            'end_at' => now()->addDays(30),
            // 'priority' => 20,
            'applies_to' => 'products',
            'type' => 'standard',
            'value_type' => 'fixed_amount',
            'value' => 200.00,
            'customer_type' => 'both',
        ])->products()->attach($prodGalaxy->id);

        // Type 3: Category Discount (15% off Accessories)
        Promotion::create([
            'name' => '15% Off Accessories',
            'slug' => 'promo-acc-15',
            'status' => 'active',
            'start_at' => now()->subDays(1),
            'end_at' => now()->addDays(30),
            // 'priority' => 5, // Lower priority than specific product promos
            'applies_to' => 'categories',
            'type' => 'standard',
            'value_type' => 'percent',
            'value' => 15.00,
            'customer_type' => 'both',
        ])->categories()->attach($catAccessories->id);

        // Type 4: Brand Discount (5% off Apple - overlaps with MacBook promo, priority matters)
        Promotion::create([
            'name' => '5% Off Apple Brand',
            'slug' => 'promo-apple-5',
            'status' => 'active',
            'start_at' => now()->subDays(1),
            'end_at' => now()->addDays(30),
            // 'priority' => 1, // Lowest priority
            'applies_to' => 'brands',
            'type' => 'standard',
            'value_type' => 'percent',
            'value' => 5.00,
            'customer_type' => 'both',
        ])->brands()->attach($brandApple->id);

        // Type 5: Cart Threshold (Min Cart Total) -> 100 RON Discount
        Promotion::create([
            'name' => '100 RON Off Orders > 5000',
            'slug' => 'promo-cart-5000',
            'status' => 'active',
            'start_at' => now()->subDays(1),
            'end_at' => now()->addDays(30),
            // 'priority' => 50,
            'applies_to' => 'all', // Cart level usually implies applies to all or checked via logic
            'min_cart_total' => 5000.00,
            'type' => 'standard',
            'value_type' => 'fixed_amount',
            'value' => 100.00,
            'customer_type' => 'both',
        ]);

        // Type 6: Quantity Trigger (Buy 5 Cables, get 20% off)
        Promotion::create([
            'name' => 'Buy 5 Cables Get 20% Off',
            'slug' => 'promo-cables-bulk',
            'status' => 'active',
            'start_at' => now()->subDays(1),
            'end_at' => now()->addDays(30),
            // 'priority' => 30,
            'applies_to' => 'products',
            'min_qty_per_product' => 5,
            'type' => 'volume',
            'value_type' => 'percent',
            'value' => 20.00,
            'customer_type' => 'both',
        ])->products()->attach($prodCable->id);

        // Type 7: Free Item (Buy Laptop, Get Mouse Free) - Implementation depends on service logic
        // Assuming 'free_item' bonus type logic exists or we simulate it
        Promotion::create([
            'name' => 'Free Mouse with Laptop',
            'slug' => 'promo-free-mouse',
            'status' => 'active',
            'start_at' => now()->subDays(1),
            'end_at' => now()->addDays(30),
            // 'priority' => 40,
            'applies_to' => 'products',
            'min_qty_per_product' => 1, // Buy 1 Laptop
            'type' => 'gift',
            'value_type' => 'fixed_amount',
            'value' => 0,
            'settings' => ['gift_product_id' => $prodMouse->id, 'gift_qty' => 1],
            'customer_type' => 'both',
        ])->products()->attach($prodMacbook->id);

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
    }
}
