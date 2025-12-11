<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\CustomerGroupController as AdminCustomerGroupController;
use App\Http\Controllers\Admin\PromotionController as AdminPromotionController;
use App\Http\Controllers\Admin\ShippingController as AdminShippingController;

use App\Http\Controllers\Front\CatalogController;
use App\Http\Controllers\Front\CartController;
use App\Http\Controllers\Front\CheckoutController;
use App\Http\Controllers\Front\OrderController;

// Auth
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register-b2c', [AuthController::class, 'registerB2C']);
    Route::post('register-b2b', [AuthController::class, 'registerB2B']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:sanctum');
});

// Front-office public catalog
Route::get('home', [CatalogController::class, 'home']);
Route::get('promotions', [CatalogController::class, 'promotions']);
Route::get('categories', [CatalogController::class, 'categories']);
Route::get('categories/{slug}', [CatalogController::class, 'category']);
Route::get('products/{slug}', [CatalogController::class, 'product']);
Route::get('brands', [CatalogController::class, 'brands']);
Route::get('brands/{slug}', [CatalogController::class, 'brand']);

// Cart & checkout (client logat sau guest cu session token separat)
Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'show']);
    Route::post('items', [CartController::class, 'addItem']);
    Route::put('items/{itemId}', [CartController::class, 'updateItem']);
    Route::delete('items/{itemId}', [CartController::class, 'removeItem']);
    Route::delete('/', [CartController::class, 'clear']);
});

Route::prefix('checkout')->group(function () {
    Route::get('summary', [CheckoutController::class, 'summary'])->middleware('auth:sanctum');
    Route::post('place-order', [CheckoutController::class, 'placeOrder'])->middleware('auth:sanctum');
});

// Orders in client account
Route::middleware('auth:sanctum')->group(function () {
    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{id}', [OrderController::class, 'show']);
    Route::post('orders/{id}/reorder', [OrderController::class, 'reorder']);
});

// Admin area
Route::prefix('admin')
    ->middleware(['auth:sanctum', 'role:admin,operator,marketer,agent,sales_director'])
    ->group(function () {

        Route::get('dashboard', [DashboardController::class, 'index']);

        Route::apiResource('products', AdminProductController::class);
        Route::apiResource('categories', AdminCategoryController::class);
        Route::apiResource('brands', AdminBrandController::class);

        Route::apiResource('customers', AdminCustomerController::class);
        Route::apiResource('customer-groups', AdminCustomerGroupController::class);

        Route::apiResource('promotions', AdminPromotionController::class);
        Route::get('shipping/config', [AdminShippingController::class, 'index']);
        Route::post('shipping/config', [AdminShippingController::class, 'store']);
        Route::put('shipping/config/{id}', [AdminShippingController::class, 'update']);
        Route::delete('shipping/config/{id}', [AdminShippingController::class, 'destroy']);
    });

// Fallback pentru 404 API
Route::fallback(function () {
    return response()->json([
        'message' => 'Endpoint not found.'
    ], 404);
});
