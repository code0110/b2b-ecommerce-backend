<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ProductStockAlert;
use App\Notifications\ProductBackInStockNotification;

class ProductObserver
{
    public function updated(Product $product): void
    {
        // Check if stock status changed from 'out_of_stock' to 'in_stock'
        // OR if stock_qty increased from 0 to > 0
        
        $wasOutOfStock = $product->getOriginal('stock_qty') <= 0;
        $isNowInStock = $product->stock_qty > 0;
        
        // Or check status explicitly if you use stock_status field
        // $wasOutOfStock = $product->getOriginal('stock_status') !== 'in_stock';
        // $isNowInStock = $product->stock_status === 'in_stock';

        if ($wasOutOfStock && $isNowInStock) {
            $this->notifySubscribers($product);
        }
    }

    protected function notifySubscribers(Product $product)
    {
        // Get all pending alerts for this product (with no variant selected)
        $alerts = ProductStockAlert::where('product_id', $product->id)
            ->whereNull('product_variant_id')
            ->whereNull('notified_at')
            ->with('user')
            ->get();

        foreach ($alerts as $alert) {
            if ($alert->user) {
                try {
                    $alert->user->notify(new ProductBackInStockNotification($product));
                    $alert->update(['notified_at' => now()]);
                } catch (\Exception $e) {
                    // Log error
                }
            }
        }
    }
}
