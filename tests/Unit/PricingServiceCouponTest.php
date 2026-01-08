<?php

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Promotion;
use App\Services\Pricing\PromotionPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingServiceCouponTest extends TestCase
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
        $category = Category::create(['name' => 'Test Cat', 'slug' => 'test-cat-' . uniqid()]);
        $brand = Brand::create(['name' => 'Test Brand', 'slug' => 'test-brand-' . uniqid()]);

        return Product::create([
            'name' => 'Product',
            'slug' => 'product-' . uniqid(),
            'internal_code' => 'CODE-' . uniqid(),
            'sku' => 'SKU-' . uniqid(),
            'list_price' => $price,
            'price_override' => null,
            'stock' => 10,
            'is_active' => true,
            'main_category_id' => $category->id,
            'brand_id' => $brand->id,
        ]);
    }

    public function test_applies_percent_coupon()
    {
        $product = $this->createProduct(100);
        $coupon = Coupon::create([
            'code' => 'TEST10',
            'discount_type' => 'percent',
            'discount_value' => 10, // 10%
            'is_active' => true,
            'is_stackable' => true,
        ]);

        $cart = Cart::create();
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        $cart->update(['coupon_id' => $coupon->id]);

        $result = $this->service->priceCart($cart);

        // Subtotal: 100
        // Coupon: 10% of 100 = 10
        // Total: 90
        $this->assertEquals(100.00, $result['subtotal']);
        $this->assertEquals(10.00, $result['coupon_discount']);
        $this->assertEquals(90.00, $result['total']);
        $this->assertNotNull($result['applied_coupon']);
        $this->assertEquals('TEST10', $result['applied_coupon']['code']);
    }

    public function test_applies_fixed_cart_coupon()
    {
        $product = $this->createProduct(100);
        $coupon = Coupon::create([
            'code' => 'TEST20',
            'discount_type' => 'fixed_cart',
            'discount_value' => 20, 
            'is_active' => true,
            'is_stackable' => true,
        ]);

        $cart = Cart::create();
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        $cart->update(['coupon_id' => $coupon->id]);

        $result = $this->service->priceCart($cart);

        $this->assertEquals(100.00, $result['subtotal']);
        $this->assertEquals(20.00, $result['coupon_discount']);
        $this->assertEquals(80.00, $result['total']);
    }

    public function test_coupon_validity_check()
    {
        $product = $this->createProduct(100);
        $coupon = Coupon::create([
            'code' => 'EXPIRED',
            'discount_type' => 'percent',
            'discount_value' => 10,
            'is_active' => true,
            'end_at' => now()->subDay(), // Expired
        ]);

        $cart = Cart::create();
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        $cart->update(['coupon_id' => $coupon->id]);

        $result = $this->service->priceCart($cart);

        $this->assertEquals(0.00, $result['coupon_discount']);
        $this->assertEquals(100.00, $result['total']);
        $this->assertNull($result['applied_coupon']);
    }

    public function test_coupon_min_cart_value()
    {
        $product = $this->createProduct(50);
        $coupon = Coupon::create([
            'code' => 'MIN100',
            'discount_type' => 'fixed_cart',
            'discount_value' => 10,
            'min_cart_value' => 100,
            'is_active' => true,
        ]);

        $cart = Cart::create();
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1, // Total 50
        ]);
        $cart->update(['coupon_id' => $coupon->id]);

        $result = $this->service->priceCart($cart);

        $this->assertEquals(0.00, $result['coupon_discount']);
        $this->assertEquals(50.00, $result['total']);
    }

    public function test_non_stackable_coupon_with_promo()
    {
        $product = $this->createProduct(100);
        
        // Create a promotion that gives 10% off
        $promotion = Promotion::create([
             'name' => 'Promo 10%',
             'slug' => 'promo-10',
             'status' => 'active',
             'applies_to' => 'all',
             'value_type' => 'percent',
             'value' => 10,
             'start_at' => now()->subDay(),
             'end_at' => now()->addDay(),
             'customer_type' => 'both',
        ]);

        $coupon = Coupon::create([
            'code' => 'NOSTACK',
            'discount_type' => 'fixed_cart',
            'discount_value' => 20,
            'is_stackable' => false,
            'is_active' => true,
        ]);

        $cart = Cart::create();
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        $cart->update(['coupon_id' => $coupon->id]);

        // Expected:
        // Base: 100
        // Promo: 10% -> 90 (Discount 10)
        // Coupon: Non-stackable. Since discount_total (10) > 0, coupon should NOT apply.
        // Total: 90
        
        $result = $this->service->priceCart($cart);

        $this->assertEquals(10.00, $result['discount_total']); // Only promo discount
        $this->assertEquals(0.00, $result['coupon_discount']);
        $this->assertEquals(90.00, $result['total']);
    }
}
