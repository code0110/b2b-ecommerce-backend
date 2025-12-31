<?php

namespace Tests\Unit;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ContractPrice;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Product;
use App\Services\Pricing\PromotionPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PricingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PromotionPricingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PromotionPricingService();
    }

    private function createProduct(array $attributes = []): Product
    {
        $category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
        ]);

        $brand = Brand::create([
            'name' => 'Test Brand',
            'slug' => 'test-brand',
        ]);

        return Product::create(array_merge([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'list_price' => 100.00,
            'internal_code' => 'TEST001',
            'main_category_id' => $category->id,
            'brand_id' => $brand->id,
        ], $attributes));
    }

    public function test_get_base_price_uses_list_price_by_default()
    {
        $product = $this->createProduct();

        $price = $this->service->calculateProductPrice($product)['price'];

        $this->assertEquals(100.00, $price);
    }

    public function test_get_base_price_uses_price_override()
    {
        $product = $this->createProduct([
            'price_override' => 90.00,
        ]);

        $price = $this->service->calculateProductPrice($product)['price'];

        $this->assertEquals(90.00, $price);
    }

    public function test_get_base_price_applies_group_discount()
    {
        $group = CustomerGroup::create([
            'name' => 'VIP',
            'type' => 'b2b',
            'default_discount_percent' => 10,
        ]);

        $customer = Customer::create([
            'name' => 'Test Customer',
            'type' => 'b2b',
            'group_id' => $group->id,
            'email' => 'test@example.com',
        ]);

        $product = $this->createProduct();

        // Should apply 10% discount on 100 = 90
        $price = $this->service->calculateProductPrice($product, $customer)['price'];

        $this->assertEquals(90.00, $price);
    }

    public function test_get_base_price_uses_group_contract_price()
    {
        $group = CustomerGroup::create([
            'name' => 'VIP',
            'type' => 'b2b',
            'default_discount_percent' => 10,
        ]);

        $customer = Customer::create([
            'name' => 'Test Customer',
            'type' => 'b2b',
            'group_id' => $group->id,
            'email' => 'test@example.com',
        ]);

        $product = $this->createProduct();

        // Contract price for group: 80 (better than 10% discount)
        ContractPrice::create([
            'product_id' => $product->id,
            'customer_group_id' => $group->id,
            'price' => 80.00,
            'currency' => 'RON',
        ]);

        $price = $this->service->calculateProductPrice($product, $customer)['price'];

        $this->assertEquals(80.00, $price);
    }

    public function test_get_base_price_uses_customer_contract_price_over_group()
    {
        $group = CustomerGroup::create([
            'name' => 'VIP',
            'type' => 'b2b',
            'default_discount_percent' => 10,
        ]);

        $customer = Customer::create([
            'name' => 'Test Customer',
            'type' => 'b2b',
            'group_id' => $group->id,
            'email' => 'test@example.com',
        ]);

        $product = $this->createProduct();

        // Contract price for group: 80
        ContractPrice::create([
            'product_id' => $product->id,
            'customer_group_id' => $group->id,
            'price' => 80.00,
            'currency' => 'RON',
        ]);

        // Contract price for specific customer: 70
        ContractPrice::create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'price' => 70.00,
            'currency' => 'RON',
        ]);

        $price = $this->service->calculateProductPrice($product, $customer)['price'];

        $this->assertEquals(70.00, $price);
    }
}
