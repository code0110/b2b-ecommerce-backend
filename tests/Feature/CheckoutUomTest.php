<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductUnit;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ShippingMethod;
use App\Models\Address;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class CheckoutUomTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Customer $customer;
    protected Product $product;
    protected ProductVariant $variant;
    protected ShippingMethod $shippingMethod;
    protected Address $address;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles
        if (!Role::where('name', 'admin')->exists()) Role::create(['name' => 'admin', 'slug' => 'admin', 'code' => 'ADMIN', 'label' => 'Admin']);
        if (!Role::where('name', 'customer')->exists()) Role::create(['name' => 'customer', 'slug' => 'customer', 'code' => 'CUST', 'label' => 'Customer']);

        // Customer
        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'type' => 'b2b',
            'fiscal_code' => 'RO123456',
        ]);

        // User
        $this->user = User::factory()->create([
            'customer_id' => $this->customer->id,
        ]);
        $this->user->roles()->attach(Role::where('name', 'customer')->first());

        // Address
        $this->address = Address::create([
            'customer_id' => $this->customer->id,
            'type' => 'billing', // acts as both if needed
            'contact_name' => 'John Doe',
            'phone' => '0700000000',
            'country' => 'Romania',
            'city' => 'Bucharest',
            'street' => 'Test St',
        ]);

        // Shipping Method
        $this->shippingMethod = ShippingMethod::create([
            'name' => 'Standard Shipping',
            'code' => 'std_shipping', // Added code
            'price' => 15.00,
            'is_active' => true,
        ]);

        // Category
        $category = \App\Models\Category::create(['name' => 'Test Cat', 'slug' => 'test-cat']);

        // Product
        $this->product = Product::create([
            'name' => 'Base Product',
            'slug' => 'base-product',
            'internal_code' => 'BASE01',
            'list_price' => 10,
            'unit_of_measure' => 'buc',
            'is_active' => true,
            'main_category_id' => $category->id,
        ]);

        // Variant
        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'Variant Red',
            'sku' => 'BASE01-RED',
            'list_price' => 12,
            'stock_qty' => 100
        ]);

        // Units
        ProductUnit::create([
            'product_id' => $this->product->id,
            'name' => 'Box 10',
            'unit' => 'box',
            'conversion_factor' => 10,
        ]);

        ProductUnit::create([
            'product_id' => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'name' => 'Pallet 100',
            'unit' => 'pallet',
            'conversion_factor' => 100,
        ]);
    }

    /** @test */
    public function it_persists_uom_info_to_order_items()
    {
        Sanctum::actingAs($this->user);

        // 1. Create Cart
        $cart = Cart::create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'status' => 'active',
        ]);

        // 2. Add Items
        // Item 1: Product (Box) -> Factor 10. Price 10*10 = 100.
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'unit' => 'box',
            'unit_price' => 100,
            'total' => 200,
        ]);

        // Item 2: Variant (Pallet) -> Factor 100. Price 12*100 = 1200.
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'quantity' => 1,
            'unit' => 'pallet',
            'unit_price' => 1200,
            'total' => 1200,
        ]);

        // 3. Place Order
        $payload = [
            'shipping_method_id' => $this->shippingMethod->id,
            'payment_method' => 'op',
            'billing_address_id' => $this->address->id,
            'shipping_address_id' => $this->address->id,
        ];

        $response = $this->postJson('/api/checkout/place-order', $payload);

        $response->assertStatus(201); // Created
        // If approval needed, it might be 200.

        // 4. Verify Database
        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->customer->id,
            'total_items' => 3, // 2 boxes + 1 pallet
        ]);

        $orderId = $response->json('id') ?? \App\Models\Order::latest()->first()->id;

        // Verify Item 1 (Box)
        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'product_id' => $this->product->id,
            'product_variant_id' => null,
            'unit' => 'box',
            'unit_conversion_factor' => 10,
            'quantity' => 2,
        ]);

        // Verify Item 2 (Pallet)
        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'product_id' => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'unit' => 'pallet',
            'unit_conversion_factor' => 100,
            'quantity' => 1,
        ]);
    }
}
