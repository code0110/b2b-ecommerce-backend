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
use App\Http\Controllers\Front\ProductReviewController;
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
use App\Http\Controllers\Admin\PageController as AdminPageController;

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
use App\Http\Controllers\Front\ProductStockAlertController;
use App\Http\Controllers\Front\QuoteController;


// Auth
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register-b2c', [AuthController::class, 'registerB2C']);
    Route::post('register-b2b', [AuthController::class, 'registerB2B']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:sanctum');
});

// Public Config
Route::get('config', [\App\Http\Controllers\Admin\SettingController::class, 'publicConfig']);
Route::get('content-blocks', [\App\Http\Controllers\Api\ContentController::class, 'index']);

// Front-office public catalog
// Route::get('home', [CatalogController::class, 'home']);
Route::get('home', [HomeController::class, 'homepage']);
Route::get('promotions', [CatalogController::class, 'promotions']);
Route::get('categories', [CatalogController::class, 'categories']);
Route::get('categories/{slug}', [CatalogController::class, 'category']);
Route::get('products/{slug}', [CatalogController::class, 'product']);
Route::get('brands', [CatalogController::class, 'brands']);
Route::get('brands/{slug}', [CatalogController::class, 'brand']);
Route::post('products/{product}/reviews', [ProductReviewController::class, 'store']);
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

        // Notificări - moved to general auth group
        // Route::get('notifications', [NotificationController::class, 'index']);
        // Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        // Route::post('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
        // Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);

        // Offers (Agent/Director System - Client Access)
        Route::get('client-offers', [\App\Http\Controllers\Front\OfferController::class, 'index']); 
        Route::get('client-offers/{id}', [\App\Http\Controllers\Front\OfferController::class, 'show']);
        Route::post('client-offers/{id}/status', [\App\Http\Controllers\Front\OfferController::class, 'changeStatus']);
        Route::post('client-offers/{id}/messages', [\App\Http\Controllers\Front\OfferController::class, 'addMessage']);

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
            Route::get('routes', [App\Http\Controllers\Front\AgentDashboardController::class, 'getRoutes']);
        });
        Route::post('company-users', [CompanyUserController::class, 'store']);
        Route::put('company-users/{user}', [CompanyUserController::class, 'update']);
        Route::delete('company-users/{user}', [CompanyUserController::class, 'destroy']);

        // Tickets (Account)
        Route::get('tickets', [FrontTicketController::class, 'index']);
        Route::post('tickets', [FrontTicketController::class, 'store']);
        Route::get('tickets/{id}', [FrontTicketController::class, 'show']);
        Route::post('tickets/{id}/messages', [FrontTicketController::class, 'storeMessage']);

        // Quick Order (Account Access)
        Route::post('quick-order/calculate', [\App\Http\Controllers\Admin\QuickOrderController::class, 'calculate']);
        Route::post('quick-order/create', [\App\Http\Controllers\Admin\QuickOrderController::class, 'createOrder']);
    });

    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/{id}', [OrderController::class, 'show']);
    Route::post('orders/{id}/reorder', [OrderController::class, 'reorder']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::get('notifications/preferences', [NotificationController::class, 'getPreferences']);
    Route::post('notifications/preferences', [NotificationController::class, 'updatePreferences']);

    // Stock Alerts
    Route::post('products/stock-alert', [ProductStockAlertController::class, 'subscribe']);
    Route::get('products/stock-alert/status', [ProductStockAlertController::class, 'checkStatus']);

    // Oferte (front) - pentru client
    Route::get('client-offers', [\App\Http\Controllers\Front\OfferController::class, 'index']);
    Route::get('client-offers/{id}', [\App\Http\Controllers\Front\OfferController::class, 'show']);
    Route::post('client-offers/{id}/status', [\App\Http\Controllers\Front\OfferController::class, 'changeStatus']);
    Route::post('client-offers/{id}/messages', [\App\Http\Controllers\Front\OfferController::class, 'addMessage']);

    Route::get('quotes', [QuoteController::class, 'index']);
    Route::post('quotes', [QuoteController::class, 'store']);
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

    // Content Blocks (Front)
    Route::get('content-blocks', [\App\Http\Controllers\Front\ContentBlockController::class, 'index']);
    Route::get('pages/{slug}', [\App\Http\Controllers\Front\PageController::class, 'show']);

});

// Admin Routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::apiResource('content-blocks', \App\Http\Controllers\Admin\ContentBlockController::class);
    Route::apiResource('pages', \App\Http\Controllers\Admin\PageController::class);
    Route::apiResource('settings', \App\Http\Controllers\Admin\SettingController::class);
    Route::apiResource('products', \App\Http\Controllers\Admin\ProductController::class);
});

// Users Management (Admin & Sales Director)
Route::middleware(['auth:sanctum', 'role:admin,sales_director'])->prefix('admin')->group(function () {
    Route::apiResource('users', AdminUserController::class);
});

// Customer Visits (Agent/Admin/Director) - Shared routes with 'admin' prefix
Route::middleware(['auth:sanctum', 'role:admin,sales_agent,sales_director'])->prefix('admin')->group(function () {
    Route::apiResource('customers', AdminCustomerController::class);
    Route::get('customer-visits', [\App\Http\Controllers\Admin\CustomerVisitController::class, 'index']);
    Route::post('customer-visits/start', [\App\Http\Controllers\Admin\CustomerVisitController::class, 'startVisit']);
    Route::post('customer-visits/{id}/end', [\App\Http\Controllers\Admin\CustomerVisitController::class, 'endVisit']);
    Route::post('customer-visits/{id}/location', [\App\Http\Controllers\Admin\CustomerVisitController::class, 'recordLocation']);

    // Orders (Agent/Admin/Director)
    Route::get('orders', [\App\Http\Controllers\Admin\OrderController::class, 'index']);
    Route::get('orders/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'show']);
    Route::put('orders/{order}', [\App\Http\Controllers\Admin\OrderController::class, 'update']);
    Route::post('orders/{order}/status', [\App\Http\Controllers\Admin\OrderController::class, 'updateStatus']);
    Route::post('orders/{order}/payment-status', [\App\Http\Controllers\Admin\OrderController::class, 'updatePaymentStatus']);

    // Offers (Agent/Admin/Director)
    Route::get('offers', [\App\Http\Controllers\Admin\OfferController::class, 'index']);
    Route::post('offers', [\App\Http\Controllers\Admin\OfferController::class, 'store']);
    Route::get('offers/{id}', [\App\Http\Controllers\Admin\OfferController::class, 'show']);
    Route::put('offers/{id}', [\App\Http\Controllers\Admin\OfferController::class, 'update']);
    Route::post('offers/{id}/status', [\App\Http\Controllers\Admin\OfferController::class, 'changeStatus']);
    Route::post('offers/{id}/convert-to-order', [\App\Http\Controllers\Admin\OfferController::class, 'convertToOrder']);
    Route::post('offers/{id}/messages', [\App\Http\Controllers\Admin\OfferController::class, 'addMessage']);
    Route::post('offers/check-price', [\App\Http\Controllers\Admin\OfferController::class, 'checkPrice']);
    Route::delete('offers/{id}', [\App\Http\Controllers\Admin\OfferController::class, 'destroy']);

    // Quote Requests (Agent/Admin/Director)
    Route::get('quotes', [\App\Http\Controllers\Admin\QuoteController::class, 'index']);
    Route::get('quotes/{quoteRequest}', [\App\Http\Controllers\Admin\QuoteController::class, 'show']);
    Route::put('quotes/{quoteRequest}', [\App\Http\Controllers\Admin\QuoteController::class, 'update']);
    Route::post('quotes/{quoteRequest}/convert-to-order', [\App\Http\Controllers\Admin\QuoteController::class, 'convertToOrder']);

    // Agent Tracking (Shift & GPS)
    Route::get('tracking/status', [\App\Http\Controllers\Admin\AgentTrackingController::class, 'status']);
    Route::post('tracking/start-day', [\App\Http\Controllers\Admin\AgentTrackingController::class, 'startDay']);
    Route::post('tracking/end-day', [\App\Http\Controllers\Admin\AgentTrackingController::class, 'endDay']);
    Route::post('tracking/ping', [\App\Http\Controllers\Admin\AgentTrackingController::class, 'pingLocation']);
    Route::get('tracking/history', [\App\Http\Controllers\Admin\AgentTrackingController::class, 'getHistory']);
    
    // Agent Routes (CRUD)
    Route::get('agent-routes', [\App\Http\Controllers\Admin\AgentRouteController::class, 'index']);
    Route::post('agent-routes', [\App\Http\Controllers\Admin\AgentRouteController::class, 'store']);
    Route::put('agent-routes/{id}', [\App\Http\Controllers\Admin\AgentRouteController::class, 'update']);
    Route::delete('agent-routes/{id}', [\App\Http\Controllers\Admin\AgentRouteController::class, 'destroy']);
    Route::post('agent-routes/generate', [\App\Http\Controllers\Admin\AgentRouteController::class, 'generate']);

    // Sales Targets
    Route::get('sales-targets', [\App\Http\Controllers\Admin\SalesTargetController::class, 'index']);
    Route::post('sales-targets', [\App\Http\Controllers\Admin\SalesTargetController::class, 'store']);
    Route::get('sales-targets/{id}', [\App\Http\Controllers\Admin\SalesTargetController::class, 'show']);
    Route::delete('sales-targets/{id}', [\App\Http\Controllers\Admin\SalesTargetController::class, 'destroy']);

    // Reports
    Route::get('reports/dashboard-stats', [\App\Http\Controllers\Admin\ReportController::class, 'dashboardStats']);
    Route::get('reports/visits-chart', [\App\Http\Controllers\Admin\ReportController::class, 'visitsChart']);
    Route::get('reports/outcomes-chart', [\App\Http\Controllers\Admin\ReportController::class, 'outcomesChart']);
    Route::get('reports/agent-performance', [\App\Http\Controllers\Admin\ReportController::class, 'agentPerformance']);
    Route::get('reports/locations', [\App\Http\Controllers\Admin\ReportController::class, 'locations']);
});

// Director Dashboard
Route::middleware(['auth:sanctum', 'role:admin,sales_director'])->prefix('admin/director')->group(function () {
    Route::get('dashboard/summary', [\App\Http\Controllers\Admin\DirectorDashboardController::class, 'summary']);
    Route::get('dashboard/team-status', [\App\Http\Controllers\Admin\DirectorDashboardController::class, 'teamStatus']);
});
