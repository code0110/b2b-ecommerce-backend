<?php

use App\Models\User;
use App\Models\Customer;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Promotion;
use App\Models\Product;
use App\Http\Controllers\Front\CartController;
use App\Services\Pricing\PromotionPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

// Set global request to avoid Auth errors
$globalRequest = Request::create('/');
$app->instance('request', $globalRequest);
\Illuminate\Support\Facades\Facade::clearResolvedInstance('request');

// 1. Setup Data
$customer = Customer::firstOrCreate(['email' => 'test_shared_cart@example.com'], [
    'name' => 'Shared Cart Company',
    'type' => 'b2b'
]);

$adminUser = User::firstOrCreate(['email' => 'admin_cart_test@example.com'], [
    'first_name' => 'Admin',
    'last_name' => 'Test',
    'password' => bcrypt('password'),
    'role' => 'admin'
]);

$customerUser = User::firstOrCreate(['email' => 'customer_cart_test@example.com'], [
    'first_name' => 'Customer',
    'last_name' => 'User',
    'password' => bcrypt('password'),
    'customer_id' => $customer->id
]);

// Ensure clean state
Cart::where('customer_id', $customer->id)->delete();

// 2. Test Shared Cart Logic
echo "Testing Shared Cart Logic...\n";

// Simulate Admin Request (impersonating customer)
$adminRequest = Request::create('/api/cart', 'GET');
$adminRequest->setUserResolver(fn() => $adminUser);
$adminRequest->headers->set('X-Customer-ID', $customer->id);

$controller = app(CartController::class);

// Use reflection to access protected resolveCart
$reflection = new ReflectionClass($controller);
$resolveCart = $reflection->getMethod('resolveCart');
$resolveCart->setAccessible(true); // Still required for PHP < 8.1; for 8.1+ this call is ignored and the method remains accessible

$adminCart = $resolveCart->invoke($controller, $adminRequest);
echo "Admin Cart ID: " . $adminCart->id . "\n";

// Simulate Customer Request (logged in as themselves)
$customerRequest = Request::create('/api/cart', 'GET');
$customerRequest->setUserResolver(fn() => $customerUser);

$customerCart = $resolveCart->invoke($controller, $customerRequest);
echo "Customer Cart ID: " . $customerCart->id . "\n";

if ($adminCart->id === $customerCart->id) {
    echo "[PASS] Carts are identical (Shared by Customer ID).\n";
} else {
    echo "[FAIL] Carts are different! Admin: {$adminCart->id}, Customer: {$customerCart->id}\n";
    exit(1);
}

// 3. Test Promotion Persistence
echo "\nTesting Promotion Persistence...\n";

// Create a rule-based promotion (no products)
$promotion = Promotion::create([
    'name' => 'Manual 10% Off',
    'slug' => 'manual-10-off-' . uniqid(),
    'type' => 'standard',
    'value_type' => 'percent',
    'value' => 10,
    'status' => 'active',
    'start_at' => now()->subDay(),
    'end_at' => now()->addDay(),
]);

// Manually attach to cart (Simulating CartController::addPromotion)
$adminCart->promotions()->syncWithoutDetaching([$promotion->id]);

// Check if attached
$adminCart->refresh();
if ($adminCart->promotions->contains($promotion->id)) {
    echo "[PASS] Promotion attached to cart.\n";
} else {
    echo "[FAIL] Promotion NOT attached to cart.\n";
    exit(1);
}

// 4. Test Engine uses it
echo "\nTesting Engine with Attached Promotion...\n";

$category = \App\Models\Category::firstOrCreate(['slug' => 'test-cat'], ['name' => 'Test Category']);

// Create a product
$product = Product::create([
    'name' => 'Test Product ' . uniqid(),
    'slug' => 'test-product-' . uniqid(),
    'price' => 100, 
    'list_price' => 100,
    'internal_code' => 'TEST-' . uniqid(),
    'active' => true,
    'main_category_id' => $category->id,
]);

// Manually add item to cart
CartItem::create([
    'cart_id' => $adminCart->id,
    'product_id' => $product->id,
    'quantity' => 1,
    'price' => 100 // initial price
]);

// Refresh cart relations
$adminCart->load('items.product', 'promotions');

// Call PromotionPricingService directly
// We need to mock Auth::user() for the service if it uses it.
// PromotionPricingService uses Auth::user() inside.
// We can use Auth::login($adminUser) to set the user for the session guard mock?
// Or just let it be null/guest if the logic supports it.
// The logic uses Auth::user() to check for 'logged_in_only' promotions.
// Our promotion is standard.

// To be safe, we can try actingAs.
Auth::login($adminUser);

$pricingService = app(PromotionPricingService::class);
$pricingResult = $pricingService->priceCart($adminCart, $customer);

$item = $pricingResult['items'][0];
echo "Item Price: " . $item['unit_final_price'] . " (Expected 90)\n";

if ($item['unit_final_price'] == 90) {
    echo "[PASS] Promotion applied correctly (10% off 100).\n";
} else {
    echo "[FAIL] Promotion not applied. Price is " . $item['unit_final_price'] . "\n";
    // Check applied promotions
    print_r($item['applied_promotions']);
}

// Cleanup
$product->delete();
$promotion->delete();
$adminCart->delete();
$customer->delete();
$adminUser->delete();
$customerUser->delete();

echo "\nDone.\n";
