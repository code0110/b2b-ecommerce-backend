<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class CartStockTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Customer $customer;
    protected Product $product;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        // Category
        $category = Category::create(['name' => 'Test Cat', 'slug' => 'test-cat']);

        // Customer
        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'type' => 'b2b',
        ]);

        // User
        $this->user = User::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        // Product
        $this->product = Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'internal_code' => 'TEST01',
            'main_category_id' => $category->id,
            'stock_qty' => 50,
            'stock_status' => 'in_stock',
            'list_price' => 100,
        ]);

        // Variant
        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'Test Variant',
            'sku' => 'TEST01-VAR',
            'stock_qty' => 20,
            'stock_status' => 'low_stock',
            'list_price' => 100,
        ]);
    }

    /** @test */
    public function it_returns_variant_stock_in_cart_response()
    {
        Sanctum::actingAs($this->user);

        // Create Cart with Variant Item
        $cart = Cart::create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'status' => 'active',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 1,
        ]);

        $response = $this->getJson('/api/cart');

        $response->assertStatus(200);

        $item = $response->json('items.0');
        
        // Ensure product.stock_qty reflects the variant stock (20) not the parent product stock (50)
        $this->assertEquals(20, $item['product']['stock_qty']);
        $this->assertEquals('low_stock', $item['product']['stock_status']);
        
        // Ensure product_variant object is also present
        $this->assertEquals(20, $item['product_variant']['stock_qty']);
    }

    /** @test */
    public function it_returns_product_stock_in_cart_response_when_no_variant()
    {
        Sanctum::actingAs($this->user);

        // Create Cart with Product Item (no variant)
        $cart = Cart::create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'status' => 'active',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'product_variant_id' => null,
            'quantity' => 1,
        ]);

        $response = $this->getJson('/api/cart');

        $response->assertStatus(200);

        $item = $response->json('items.0');
        
        // Ensure product.stock_qty reflects the product stock (50)
        $this->assertEquals(50, $item['product']['stock_qty']);
        $this->assertEquals('in_stock', $item['product']['stock_status']);
        $this->assertNull($item['product_variant']);
    }
}
