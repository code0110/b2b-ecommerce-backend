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


use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\SalesRepresentativeController as AdminSalesRepController;
use App\Http\Controllers\Admin\PartnerRequestController as AdminPartnerRequestController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\BlogController as AdminBlogController;

use App\Http\Controllers\Front\TicketController as FrontTicketController;
use App\Http\Controllers\Front\SalesRepresentativeController as FrontSalesRepController;
use App\Http\Controllers\Front\PartnerController;
use App\Http\Controllers\Front\PaymentController as FrontPaymentController;
use App\Http\Controllers\Front\BlogController as FrontBlogController;

use App\Http\Controllers\Front\SearchController;
use App\Http\Controllers\Front\ProductToolsController;
use App\Http\Controllers\Front\QuickOrderController;
use App\Http\Controllers\Front\OrderTemplateController;

use App\Http\Controllers\Front\NotificationController;

use App\Http\Controllers\Front\QuoteController;
use App\Http\Controllers\Admin\QuoteController as AdminQuoteController;
use App\Http\Controllers\Admin\ErpController;
use App\Http\Controllers\Admin\ShipmentController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\AuditLogController;

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
// Search
Route::get('search', [SearchController::class, 'search']);
Route::get('search/autocomplete', [SearchController::class, 'autocomplete']);
// Recent viewed / Compare
Route::post('products/{product}/track-view', [ProductToolsController::class, 'trackView']);
Route::get('products/recently-viewed', [ProductToolsController::class, 'recentlyViewed']);
Route::get('products/compare', [ProductToolsController::class, 'comparisonList']);
Route::post('products/compare', [ProductToolsController::class, 'addToComparison']);
Route::delete('products/compare/{product}', [ProductToolsController::class, 'removeFromComparison']);
// Quick order
Route::get('quick-order/search', [QuickOrderController::class, 'search']);
Route::post('quick-order/add-to-cart', [QuickOrderController::class, 'addToCart']);

// Order templates
Route::get('order-templates', [OrderTemplateController::class, 'index']);
Route::get('order-templates/{id}', [OrderTemplateController::class, 'show']);
Route::post('order-templates', [OrderTemplateController::class, 'store']);
Route::put('order-templates/{id}', [OrderTemplateController::class, 'update']);
Route::delete('order-templates/{id}', [OrderTemplateController::class, 'destroy']);
Route::post('order-templates/{id}/add-to-cart', [OrderTemplateController::class, 'addToCart']);


// Reprezentanți vânzări
Route::get('sales-representatives', [FrontSalesRepController::class, 'index']);

// Blog & pagini
Route::get('blog', [FrontBlogController::class, 'index']);
Route::get('blog/{slug}', [FrontBlogController::class, 'show']);
Route::get('pages/{slug}', [FrontBlogController::class, 'page']);

// Devino partener
Route::post('partner-requests', [PartnerController::class, 'store']);


// Tickets
Route::get('tickets', [FrontTicketController::class, 'index']);
Route::post('tickets', [FrontTicketController::class, 'store']);
Route::get('tickets/{id}', [FrontTicketController::class, 'show']);
Route::post('tickets/{id}/messages', [FrontTicketController::class, 'addMessage']);

// Payments
Route::get('payments', [FrontPaymentController::class, 'index']);
Route::post('orders/{orderId}/pay', [FrontPaymentController::class, 'payOrder']);


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

    Route::get('notifications', [NotificationController::class, 'index']);
Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);

// Oferte (front)
Route::get('quotes', [QuoteController::class, 'index']);
Route::get('quotes/{id}', [QuoteController::class, 'show']);
Route::post('quotes/from-product', [QuoteController::class, 'fromProduct']);
Route::post('quotes/from-cart', [QuoteController::class, 'fromCart']);

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

        // Oferte (admin/agenți)
        Route::apiResource('quotes', AdminQuoteController::class)->only(['index', 'show', 'update']);
        Route::post('quotes/{quoteRequest}/convert-to-order', [AdminQuoteController::class, 'convertToOrder']);

        // ERP logs & manual sync
        Route::get('erp/logs', [ErpController::class, 'logs']);
        Route::post('erp/orders/{order}/sync', [ErpController::class, 'syncOrder']);

        // Shipments / AWB
        Route::get('shipments', [ShipmentController::class, 'index']);
        Route::post('shipments', [ShipmentController::class, 'store']);
        Route::post('shipments/{id}/status', [ShipmentController::class, 'updateStatus']);
        // Roles & permissions
        Route::apiResource('roles', RoleController::class);
        Route::apiResource('permissions', PermissionController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        // Audit log
        Route::get('audit-logs', [AuditLogController::class, 'index']);
        Route::get('audit-logs/{id}', [AuditLogController::class, 'show']);


    });

    // Tickets
Route::apiResource('tickets', AdminTicketController::class)->only(['index', 'show', 'update']);

// Sales representatives
Route::apiResource('sales-representatives', AdminSalesRepController::class);

// Partner requests
Route::apiResource('partner-requests', AdminPartnerRequestController::class)->only(['index', 'show', 'update']);

// Payments
Route::apiResource('payments', AdminPaymentController::class)->only(['index', 'show', 'store', 'update']);

// Blog
Route::apiResource('blog-posts', AdminBlogController::class);
Route::get('blog-categories', [AdminBlogController::class, 'categories']);
Route::post('blog-categories', [AdminBlogController::class, 'storeCategory']);
Route::put('blog-categories/{id}', [AdminBlogController::class, 'updateCategory']);
Route::delete('blog-categories/{id}', [AdminBlogController::class, 'destroyCategory']);


// Fallback pentru 404 API
Route::fallback(function () {
    return response()->json([
        'message' => 'Endpoint not found.'
    ], 404);
});
