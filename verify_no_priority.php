<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Category;
use App\Models\DiscountRule;
use App\Models\Promotion;
use App\Models\Product;
use App\Services\DiscountRuleService;
use App\Services\Pricing\PromotionEngine;
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting verification...\n";

// Cleanup
DB::statement('SET FOREIGN_KEY_CHECKS=0;');
DiscountRule::truncate();
Promotion::truncate();
Product::truncate();
Category::truncate();
User::where('email', 'test@example.com')->delete();
Role::where('name', 'test_role')->delete();
CustomerGroup::where('name', 'Test Group')->delete();
DB::statement('SET FOREIGN_KEY_CHECKS=1;');

// Setup
$role = Role::create(['name' => 'test_role', 'guard_name' => 'web', 'slug' => 'test-role', 'code' => 'TEST_ROLE']);

// ...

$group = CustomerGroup::create(['name' => 'Test Group', 'code' => 'TEST_GROUP', 'is_active' => true]);

$customer = Customer::create([
    'name' => 'Test Customer',
    'email' => 'customer@example.com',
    'type' => 'b2b',
    'group_id' => $group->id,
    'is_active' => true
]);

$user = new User();
$user->first_name = 'Test';
$user->last_name = 'User';
$user->email = 'test@example.com';
$user->password = bcrypt('password');
$user->customer_id = $customer->id;
$user->save();
$user->roles()->attach($role->id);
// $user->customer_group_id = $group->id; // Not a valid column
// $user->save();

$category = Category::create(['name' => 'Test Category', 'slug' => 'test-category']);

$product = Product::create([
    'name' => 'Test Product',
    'slug' => 'test-product',
    'internal_code' => 'TEST-001',
    'main_category_id' => $category->id,
    'list_price' => 100.00, // Important for base price
    'status' => 'published',
    'stock_status' => 'in_stock'
]);

$service = app(DiscountRuleService::class);
$engine = app(PromotionEngine::class);

echo "Setup complete.\n";

// Test 1: Specificity Hierarchy
echo "\nTest 1: Specificity Hierarchy (User > Group > Role > Global)\n";

// Global Rule (10%)
DiscountRule::create([
    'name' => 'Global Rule',
    'target_type' => 'global',
    'rule_type' => 'max_discount',
    'limit_percent' => 10.0,
    'active' => true
]);

$rule = $service->getBestApplicableRule($user, null, 'max_discount');
echo "Global only: " . ($rule->limit_percent == 10.0 ? "PASS" : "FAIL ({$rule->limit_percent})") . "\n";

// Role Rule (20%)
DiscountRule::create([
    'name' => 'Role Rule',
    'target_type' => 'role',
    'rule_type' => 'max_discount',
    'target_id' => $role->id,
    'limit_percent' => 20.0,
    'active' => true
]);

$rule = $service->getBestApplicableRule($user, null, 'max_discount');
echo "Role > Global: " . ($rule->limit_percent == 20.0 ? "PASS" : "FAIL ({$rule->limit_percent})") . "\n";

// Group Rule (30%)
DiscountRule::create([
    'name' => 'Group Rule',
    'target_type' => 'customer_group',
    'rule_type' => 'max_discount',
    'target_id' => $group->id,
    'limit_percent' => 30.0,
    'active' => true
]);

$rule = $service->getBestApplicableRule($user, null, 'max_discount');
echo "Group > Role: " . ($rule->limit_percent == 30.0 ? "PASS" : "FAIL ({$rule->limit_percent})") . "\n";

// User Rule (40%)
DiscountRule::create([
    'name' => 'User Rule',
    'target_type' => 'user',
    'rule_type' => 'max_discount',
    'target_id' => $user->id,
    'limit_percent' => 40.0,
    'active' => true
]);

$rule = $service->getBestApplicableRule($user, null, 'max_discount');
echo "User > Group: " . ($rule->limit_percent == 40.0 ? "PASS" : "FAIL ({$rule->limit_percent})") . "\n";


// Test 2: Promotion Best Deal
echo "\nTest 2: Promotion Best Deal\n";

// First, relax the user discount limit to 50% to verify Best Deal (30%) without capping
DiscountRule::where('target_type', 'user')
    ->where('target_id', $user->id)
    ->update(['limit_percent' => 50.0]);

// Promo 1: 10%
Promotion::create([
    'name' => 'Promo 10%',
    'slug' => 'promo-10',
    'type' => 'standard',
    'value_type' => 'percent',
    'value' => 10.0,
    'status' => 'active',
    'start_at' => now()->subDay(),
    'end_at' => now()->addDay(),
]);

// Promo 2: 30%
Promotion::create([
    'name' => 'Promo 30%',
    'slug' => 'promo-30',
    'type' => 'standard',
    'value_type' => 'percent',
    'value' => 30.0,
    'status' => 'active',
    'start_at' => now()->subDay(),
    'end_at' => now()->addDay(),
]);

// Promo 3: 15%
Promotion::create([
    'name' => 'Promo 15%',
    'slug' => 'promo-15',
    'type' => 'standard',
    'value_type' => 'percent',
    'value' => 15.0,
    'status' => 'active',
    'start_at' => now()->subDay(),
    'end_at' => now()->addDay(),
]);

$result = $engine->getProductPriceWithPromotions($product, $user);
// Base price 100, Best discount 30% -> Final Price 70
echo "Best Deal (30%): " . ($result['final_price'] == 70.0 ? "PASS" : "FAIL ({$result['final_price']})") . "\n";


// Test 3: Total Cap Enforcement
echo "\nTest 3: Total Cap Enforcement\n";

// Set User Limit to 15% (Strict Cap)
DiscountRule::where('target_type', 'user')
    ->where('target_id', $user->id)
    ->update(['limit_percent' => 15.0]);

$result = $engine->getProductPriceWithPromotions($product, $user);
// Base 100, Best Promo 30% -> 70. But Cap is 15% -> 85.
echo "Cap Enforcement (15%): " . ($result['final_price'] == 85.0 ? "PASS" : "FAIL ({$result['final_price']})") . "\n";

echo "\nVerification Complete.\n";
