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


use App\Http\Controllers\Admin\OrderController as AdminOrderController;

use App\Http\Controllers\Front\CatalogController;
use App\Http\Controllers\Front\CartController;
use App\Http\Controllers\Front\CheckoutController;
use App\Http\Controllers\Front\OrderController;

use App\Http\Controllers\Front\PageController;

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

use App\Http\Controllers\Admin\QuoteController as AdminQuoteController;
use App\Http\Controllers\Admin\ErpController;
use App\Http\Controllers\Admin\ShipmentController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\RoleController as AdminRoleController;
use App\Http\Controllers\Admin\PermissionController as AdminPermissionController;

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\InvoiceController as AdminInvoiceController;
use App\Http\Controllers\Front\HomeController;

use App\Http\Controllers\Front\CategoryTreeController;
use App\Http\Controllers\Front\CatalogCategoryController;
use App\Http\Controllers\Front\PromotionController as FrontPromotionController;
use App\Http\Controllers\Front\CatalogHighlightController;
use App\Http\Controllers\Front\AccountOrderController;


use App\Http\Controllers\Front\AccountDashboardController;
use App\Http\Controllers\Front\AddressController as AccountAddressController;
use App\Http\Controllers\Front\OrderTemplateController;
use App\Http\Controllers\Front\CompanyUserController;
use App\Http\Controllers\Front\OrderApprovalController;
use App\Http\Controllers\Front\ShipmentController as FrontShipmentController;
use App\Http\Controllers\Front\InvoiceController as FrontInvoiceController;
use App\Http\Controllers\Front\NotificationController;
use App\Http\Controllers\Front\QuoteController;


// Auth
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register-b2c', [AuthController::class, 'registerB2C']);
    Route::post('register-b2b', [AuthController::class, 'registerB2B']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:sanctum');
});

// Front-office public catalog
// Route::get('home', [CatalogController::class, 'home']);
Route::get('home', [HomeController::class, 'homepage']);
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


Route::get('pages/{slug}', [PageController::class, 'show']);
// Devino partener
Route::post('partner-requests', [PartnerController::class, 'store']);


// Tickets - moved to account group


// Payments
Route::get('payments', [FrontPaymentController::class, 'index']);
Route::post('orders/{orderId}/pay', [FrontPaymentController::class, 'payOrder']);


// Cart & checkout (client logat sau guest cu session token separat)
$sessionMiddleware = [
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
];

Route::middleware($sessionMiddleware)->prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'show']);
    Route::post('items', [CartController::class, 'addItem']);
    Route::put('items/{itemId}', [CartController::class, 'updateItem']);
    Route::delete('items/{itemId}', [CartController::class, 'removeItem']);
    Route::post('promotions/{id}', [CartController::class, 'addPromotion']);
    Route::delete('/', [CartController::class, 'clear']);
});
// Arbore de categorii pentru front (overlay catalog)
Route::get('catalog/categories-tree', CategoryTreeController::class);
// Pagina de categorie (front)
Route::get('catalog/category/{slug}', [CatalogCategoryController::class, 'show']);

// Promoții (front)
Route::get('promotions', [FrontPromotionController::class, 'index']);
Route::get('promotions/{slug}', [FrontPromotionController::class, 'show']);

// Produse noi / reduse (front)
Route::get('catalog/new-products', [CatalogHighlightController::class, 'newProducts']);
Route::get('catalog/discounted-products', [CatalogHighlightController::class, 'discountedProducts']);


Route::middleware($sessionMiddleware)->prefix('checkout')->group(function () {
    Route::get('summary', [CheckoutController::class, 'summary']);
    Route::post('place-order', [CheckoutController::class, 'placeOrder']);
});

// Orders in client account
Route::middleware(['auth:sanctum', 'impersonate'])->group(function () {

    Route::prefix('account')->group(function () {
        // Dashboard cont
        Route::get('dashboard', [AccountDashboardController::class, 'overview']);

        // Comenzi (cont client)
        Route::get('orders', [AccountOrderController::class, 'index']);
        Route::get('orders/{order}', [AccountOrderController::class, 'show']);

        // Adrese
        Route::get('addresses', [AccountAddressController::class, 'index']);
        Route::post('addresses', [AccountAddressController::class, 'store']);
        Route::put('addresses/{address}', [AccountAddressController::class, 'update']);
        Route::delete('addresses/{address}', [AccountAddressController::class, 'destroy']);

        // Facturi (alias pentru FrontInvoiceController)
        Route::get('invoices', [FrontInvoiceController::class, 'index']);
        Route::get('invoices/{invoice}', [FrontInvoiceController::class, 'show']);

        // Notificări
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);

        // Oferte (quotes) – tab-ul de “Oferte” din cont
        Route::get('offers', [QuoteController::class, 'index']);
        Route::get('offers/{quoteRequest}', [QuoteController::class, 'show']);

        // Comenzi recurente / template-uri de comenzi
        Route::get('order-templates', [OrderTemplateController::class, 'index']);
        Route::get('order-templates/{orderTemplate}', [OrderTemplateController::class, 'show']);
        Route::post('order-templates', [OrderTemplateController::class, 'store']);
        Route::put('order-templates/{orderTemplate}', [OrderTemplateController::class, 'update']);
        Route::delete('order-templates/{orderTemplate}', [OrderTemplateController::class, 'destroy']);
        Route::post('order-templates/{orderTemplate}/add-to-cart', [OrderTemplateController::class, 'addToCart']);

        // Utilizatori companie (multi-user B2B)
        Route::get('company-users', [CompanyUserController::class, 'index']);

        // Agent & Director Dashboard
        Route::prefix('agent')->group(function () {
            Route::get('clients', [App\Http\Controllers\Front\AgentDashboardController::class, 'getClients']);
            Route::get('agents', [App\Http\Controllers\Front\AgentDashboardController::class, 'getAgents']);
            Route::get('clients/{id}/invoices', [App\Http\Controllers\Front\AgentDashboardController::class, 'getClientInvoices']);
            Route::get('receipt-book/active', [App\Http\Controllers\Front\AgentDashboardController::class, 'getActiveReceiptBook']);
            Route::post('payments', [App\Http\Controllers\Front\AgentDashboardController::class, 'storePayment']);
            Route::post('payments/cancel-receipt', [App\Http\Controllers\Front\AgentDashboardController::class, 'cancelReceipt']);
        });
        Route::post('company-users', [CompanyUserController::class, 'store']);
        Route::put('company-users/{user}', [CompanyUserController::class, 'update']);
        Route::delete('company-users/{user}', [CompanyUserController::class, 'destroy']);

        // Tickets (Account)
        Route::get('tickets', [FrontTicketController::class, 'index']);
        Route::post('tickets', [FrontTicketController::class, 'store']);
        Route::get('tickets/{id}', [FrontTicketController::class, 'show']);
        Route::post('tickets/{id}/messages', [FrontTicketController::class, 'storeMessage']);
    });

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
// Documente financiare (cont client)
Route::get('invoices', [FrontInvoiceController::class, 'index']);
Route::get('invoices/{id}', [FrontInvoiceController::class, 'show']);

// Multi-user B2B – administrare utilizatori companie (doar owner)
Route::get('company/users', [CompanyUserController::class, 'index']);
Route::post('company/users', [CompanyUserController::class, 'store']);
Route::put('company/users/{id}', [CompanyUserController::class, 'update']);
Route::delete('company/users/{id}', [CompanyUserController::class, 'destroy']);

// Workflow aprobare comenzi B2B
Route::get('company/orders/pending-approval', [OrderApprovalController::class, 'pending']);
Route::post('company/orders/{order}/approve', [OrderApprovalController::class, 'approve']);
Route::post('company/orders/{order}/reject', [OrderApprovalController::class, 'reject']);

Route::get('shipments', [FrontShipmentController::class, 'index']);
Route::get('shipments/{id}', [FrontShipmentController::class, 'show']);

// Homepage data - Moved to public area
// Route::get('home', [HomeController::class, 'homepage']);

// Produse noi
Route::get('products/new', [HomeController::class, 'newProducts']);

// Produse în promoție / reduceri
Route::get('products/discounted', [HomeController::class, 'discountedProducts']);

});

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'role:admin,operator'])
    ->group(function () {
        // Dashboard
        Route::get('dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index']);
        Route::get('dashboard/overview', [\App\Http\Controllers\Admin\DashboardController::class, 'overview']);

        // Products
        Route::apiResource('products', \App\Http\Controllers\Admin\ProductController::class);

        // Categories
        Route::apiResource('categories', \App\Http\Controllers\Admin\CategoryController::class);

        // Brands
        Route::apiResource('brands', \App\Http\Controllers\Admin\BrandController::class);

        // Customers
        Route::apiResource('customers', \App\Http\Controllers\Admin\CustomerController::class);

        // Customer Groups
        Route::apiResource('customer-groups', \App\Http\Controllers\Admin\CustomerGroupController::class);

        // Promotions
        Route::apiResource('promotions', \App\Http\Controllers\Admin\PromotionController::class);


// Users (admin)
Route::apiResource('users', AdminUserController::class);

// Roles
Route::apiResource('roles', AdminRoleController::class);

// Permissions
Route::apiResource('permissions', AdminPermissionController::class);


        // Invoices
        Route::apiResource('invoices', \App\Http\Controllers\Admin\InvoiceController::class);

        // Tickets
Route::apiResource('tickets', AdminTicketController::class)->only(['index', 'show', 'update']);

// Sales representatives
Route::apiResource('sales-representatives', AdminSalesRepController::class);

// Partner requests
Route::apiResource('partner-requests', AdminPartnerRequestController::class)->only(['index', 'show', 'update']);

// Payments
        Route::apiResource('payments', AdminPaymentController::class)->only(['index', 'show', 'store', 'update']);

        // Receipt Books
        Route::apiResource('receipt-books', \App\Http\Controllers\Admin\ReceiptBookController::class);
        Route::get('receipt-books-agents', [\App\Http\Controllers\Admin\ReceiptBookController::class, 'getAgents']);

        // Quotes / oferte
        Route::apiResource('quotes', \App\Http\Controllers\Admin\QuoteController::class)->only(['index', 'show', 'update']);
        Route::post('quotes/{quoteRequest}/convert-to-order', [\App\Http\Controllers\Admin\QuoteController::class, 'convertToOrder']);

        // Shipping config
        Route::get('shipping/config', [\App\Http\Controllers\Admin\ShippingController::class, 'index']);
        Route::post('shipping/config', [\App\Http\Controllers\Admin\ShippingController::class, 'store']);
        Route::put('shipping/config/{id}', [\App\Http\Controllers\Admin\ShippingController::class, 'update']);
        Route::delete('shipping/config/{id}', [\App\Http\Controllers\Admin\ShippingController::class, 'destroy']);

        // Shipments
        Route::get('shipments', [\App\Http\Controllers\Admin\ShipmentController::class, 'index']);
        Route::post('shipments', [\App\Http\Controllers\Admin\ShipmentController::class, 'store']);
        Route::post('shipments/{id}/status', [\App\Http\Controllers\Admin\ShipmentController::class, 'updateStatus']);

        // ERP helper
        Route::get('erp/logs', [\App\Http\Controllers\Admin\ErpController::class, 'logs']);
        Route::post('erp/orders/{order}/sync', [\App\Http\Controllers\Admin\ErpController::class, 'syncOrder']);

        // Audit logs
        Route::get('audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index']);
        Route::get('audit-logs/{id}', [\App\Http\Controllers\Admin\AuditLogController::class, 'show']);

        // Notifications
        Route::get('notifications', [\App\Http\Controllers\Front\NotificationController::class, 'index']);
        Route::get('notifications/unread-count', [\App\Http\Controllers\Front\NotificationController::class, 'unreadCount']);
        Route::post('notifications/{id}/read', [\App\Http\Controllers\Front\NotificationController::class, 'markAsRead']);
        Route::post('notifications/read-all', [\App\Http\Controllers\Front\NotificationController::class, 'markAllAsRead']);

        // ORDERS (NOU) – foarte important să fie în ACEST grup
        Route::get('orders', [AdminOrderController::class, 'index']);
        Route::get('orders/{order}', [AdminOrderController::class, 'show']);
        Route::put('orders/{order}', [AdminOrderController::class, 'update']);
        Route::post('orders/{order}/status', [AdminOrderController::class, 'updateStatus']);
        Route::post('orders/{order}/payment-status', [AdminOrderController::class, 'updatePaymentStatus']);


    });


    

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
