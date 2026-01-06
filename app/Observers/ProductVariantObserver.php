<?php

namespace App\Observers;

use App\Models\ProductVariant;
use App\Models\ProductStockAlert;
use App\Notifications\ProductBackInStockNotification;

class ProductVariantObserver
{
    /**
     * Handle the ProductVariant "updated" event.
     */
    public function updated(ProductVariant $variant): void
    {
        // Check if stock changed from <= 0 to > 0
        $wasOutOfStock = $variant->getOriginal('stock_qty') <= 0;
        $isNowInStock = $variant->stock_qty > 0;

        if ($wasOutOfStock && $isNowInStock) {
            $this->notifySubscribers($variant);
        }
    }

    protected function notifySubscribers(ProductVariant $variant)
    {
        // Find alerts for this specific variant that haven't been notified yet
        $alerts = ProductStockAlert::where('product_variant_id', $variant->id)
            ->whereNull('notified_at')
            ->with(['user', 'product'])
            ->get();

        foreach ($alerts as $alert) {
            if ($alert->user) {
                try {
                    // We pass the parent product and the variant name
                    $alert->user->notify(new ProductBackInStockNotification($alert->product, $variant->name));
                    
                    // Mark as notified
                    $alert->update(['notified_at' => now()]);
                } catch (\Exception $e) {
                    // Log error or ignore
                }
            }
        }
    }
}
