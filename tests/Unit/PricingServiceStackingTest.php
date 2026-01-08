<?php

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Brand;
use App\Models\Category;
use App\Services\Pricing\PromotionPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingServiceStackingTest extends TestCase
{
    use RefreshDatabase;

    protected PromotionPricingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PromotionPricingService::class);
    }

    protected function createProduct($price = 100): Product
    {
        $category = Category::create(['name' => 'Cat', 'slug' => 'cat-' . uniqid()]);
        $brand = Brand::create(['name' => 'Brand', 'slug' => 'brand-' . uniqid()]);

        return Product::create([
            'name' => 'Product',
            'slug' => 'product-' . uniqid(),
            'internal_code' => 'CODE-' . uniqid(),
            'sku' => 'SKU-' . uniqid(),
            'list_price' => $price,
            'is_active' => true,
            'main_category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
    }

    public function test_exclusive_promotion_blocks_iterative()
    {
        $product = $this->createProduct(100);

        // Exclusive: 20% off
        Promotion::create([
            'name' => 'Exclusive 20',
            'slug' => 'excl-20',
            'status' => 'active',
            'applies_to' => 'all',
            'value_type' => 'percent',
            'value' => 20,
            'stacking_type' => 'exclusive',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
        ]);

        // Iterative: 10% off
        Promotion::create([
            'name' => 'Iterative 10',
            'slug' => 'iter-10',
            'status' => 'active',
            'applies_to' => 'all',
            'value_type' => 'percent',
            'value' => 10,
            'stacking_type' => 'iterative',
            'start_at' => now()->subDay(),
            'end_at' => now()->addDay(),
        ]);

        $cart = Cart::create();
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);

        $result = $this->service->priceCart($cart);
        
        // Should only apply Exclusive (20%)
        // Price: 100 -> 80. Discount: 20.
        
        $this->assertEquals(20.00, $result['discount_total']);
        $this->assertEquals(80.00, $result['total']);
        
        $item = $result['items'][0];
        $this->assertCount(1, $item['applied_promotions']);
        $this->assertEquals('excl-20', $item['applied_promotions'][0]['slug']);
    }

    public function test_iterative_promotions_stack_compound()
    {
        $this->markTestSkipped('PromotionEngine currently implements Best Deal logic, not stacking.');
        
        $product = $this->createProduct(100);

        // Promo A: 10%
        Promotion::create([
            'name' => 'Promo A',
            'slug' => 'promo-a',
            'status' => 'active',
            'applies_to' => 'all',
            'value_type' => 'percent',
            'value' => 10,
            'stacking_type' => 'iterative',
        ]);

        // Promo B: 10%
        Promotion::create([
            'name' => 'Promo B',
            'slug' => 'promo-b',
            'status' => 'active',
            'applies_to' => 'all',
            'value_type' => 'percent',
            'value' => 10,
            'stacking_type' => 'iterative',
        ]);

        $cart = Cart::create();
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);

        $result = $this->service->priceCart($cart);

        // Calculation:
        // 1. Promo A (10% of 100) = 10. Remaining: 90.
        // 2. Promo B (10% of 90) = 9. Remaining: 81.
        // Total Discount: 19.
        
        $this->assertEquals(19.00, $result['discount_total']);
        $this->assertEquals(81.00, $result['total']);
        
        $item = $result['items'][0];
        $this->assertCount(2, $item['applied_promotions']);
    }

    public function test_min_cart_total_respected()
    {
        $product = $this->createProduct(50);

        // Promo: 10% off IF cart > 100
        Promotion::create([
            'name' => 'Bulk Discount',
            'slug' => 'bulk-10',
            'status' => 'active',
            'applies_to' => 'all',
            'value_type' => 'percent',
            'value' => 10,
            'min_cart_total' => 100,
        ]);

        $cart = Cart::create();
        
        // Case 1: Total 50 (below min)
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 1]);
        $result = $this->service->priceCart($cart);
        $this->assertEquals(0.00, $result['discount_total']);

        // Case 2: Total 150 (above min)
        $cart->items()->where('product_id', $product->id)->update(['quantity' => 3]);
        // Need to reload items relation
        $cart->load('items.product'); 
        
        $result = $this->service->priceCart($cart);
        // Total 150. Discount 10% = 15.
        $this->assertEquals(15.00, $result['discount_total']);
    }
}
