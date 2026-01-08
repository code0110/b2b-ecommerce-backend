<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Order;
use App\Models\Customer;
use App\Observers\AuditLogObserver;
use App\Observers\ProductObserver;
use App\Observers\ProductVariantObserver;
use App\Observers\PromotionObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Promotion;
use App\Policies\PromotionPolicy;

use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use App\Listeners\LogAuditLogin;
use App\Listeners\LogAuditLogout;

use App\Models\Offer;
use App\Models\Ticket;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Setting;
use App\Models\Page;
use App\Models\BlogPost;
use App\Models\Coupon;
use App\Models\DiscountRule;
use App\Models\ShippingMethod;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Promotion::class, PromotionPolicy::class);

        // Event Listeners
        Event::listen(Login::class, LogAuditLogin::class);
        Event::listen(Logout::class, LogAuditLogout::class);

        Product::observe(ProductObserver::class);
        ProductVariant::observe(ProductVariantObserver::class);
        Promotion::observe(PromotionObserver::class);

        // Audit Logging
        User::observe(AuditLogObserver::class);
        Category::observe(AuditLogObserver::class);
        Brand::observe(AuditLogObserver::class);
        Order::observe(AuditLogObserver::class);
        Customer::observe(AuditLogObserver::class);
        Offer::observe(AuditLogObserver::class);
        Ticket::observe(AuditLogObserver::class);
        Role::observe(AuditLogObserver::class);
        Permission::observe(AuditLogObserver::class);
        Setting::observe(AuditLogObserver::class);
        Page::observe(AuditLogObserver::class);
        BlogPost::observe(AuditLogObserver::class);
        Coupon::observe(AuditLogObserver::class);
        DiscountRule::observe(AuditLogObserver::class);
        ShippingMethod::observe(AuditLogObserver::class);
        
        // Append AuditLogObserver to models that already have observers
        Product::observe(AuditLogObserver::class);
        ProductVariant::observe(AuditLogObserver::class);
        Promotion::observe(AuditLogObserver::class);
    }
}
