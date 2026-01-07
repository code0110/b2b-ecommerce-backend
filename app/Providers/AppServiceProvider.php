<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Observers\ProductObserver;
use App\Observers\ProductVariantObserver;
use App\Observers\PromotionObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Promotion;
use App\Policies\PromotionPolicy;

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

        Product::observe(ProductObserver::class);
        ProductVariant::observe(ProductVariantObserver::class);
        Promotion::observe(PromotionObserver::class);
    }
}
