<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Http\Controllers\Front\CartController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

// 1. Create a dummy cart
$sessionId = 'debug_session_' . time();
$cart = Cart::create([
    'session_id' => $sessionId,
    'status' => 'active',
]);

// 2. Add items to cart (using existing products)
$product1 = Product::where('slug', 'macbook-pro-16')->first(); // Should have 10% promo
$product2 = Product::where('slug', 'usb-c-cable')->first(); // Should have bulk promo

if ($product1) {
    CartItem::create([
        'cart_id' => $cart->id,
        'product_id' => $product1->id,
        'quantity' => 1,
        'unit_price' => $product1->price_override ?? $product1->list_price,
        'total' => $product1->price_override ?? $product1->list_price,
    ]);
}

if ($product2) {
    CartItem::create([
        'cart_id' => $cart->id,
        'product_id' => $product2->id,
        'quantity' => 5, // Trigger bulk if applicable
        'unit_price' => $product2->price_override ?? $product2->list_price,
        'total' => ($product2->price_override ?? $product2->list_price) * 5,
    ]);
}

// 3. Simulate Request
$request = Request::create('/api/cart', 'GET');
$request->setLaravelSession(Session::driver());
$request->session()->setId($sessionId);

// 4. Call Controller
$controller = $app->make(CartController::class);
$response = $controller->show($request);

$data = $response->getData(true);

print_r($data);

echo "Debug: Cart ID Created: " . $cart->id . PHP_EOL;
echo "Debug: Items in DB: " . CartItem::where('cart_id', $cart->id)->count() . PHP_EOL;

// Cleanup
$cart->items()->delete();
$cart->delete();
