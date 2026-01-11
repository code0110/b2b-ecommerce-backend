<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class QuickOrderVariantsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Customer $customer;
    protected Product $product;
    protected ProductVariant $variantRed;
    protected ProductVariant $variantBlue;

    protected function setUp(): void
    {
        parent::setUp();

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

        $category = \App\Models\Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category'
        ]);

        // Product (Parent)
        $this->product = Product::create([
            'name' => 'T-Shirt',
            'slug' => 't-shirt',
            'internal_code' => 'TSHIRT',
            'list_price' => 100,
            'stock_qty' => 0, // Parent stock usually ignored if variants exist
            'main_category_id' => $category->id,
            'status' => 'published',
        ]);

        // Variant 1
        $this->variantRed = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'T-Shirt Red',
            'sku' => 'TSHIRT-RED',
            'list_price' => 110,
            'stock_qty' => 50,
        ]);

        // Variant 2
        $this->variantBlue = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'T-Shirt Blue',
            'sku' => 'TSHIRT-BLUE',
            'list_price' => 110,
            'stock_qty' => 30,
        ]);
    }

    /** @test */
    public function it_expands_variants_when_searching_for_parent_name()
    {
        Sanctum::actingAs($this->user);

        // Search for "T-Shirt"
        $response = $this->getJson('/api/quick-order/search?q=T-Shirt');

        $response->assertStatus(200);

        // The response is now paginated, so results are in 'data'
        $results = $response->json('data');

        // Should have 2 results (Red and Blue), not 3 (Parent + Red + Blue)
        $this->assertCount(2, $results);

        // Verify names
        $names = collect($results)->pluck('name')->sort()->values();
        $this->assertEquals(['T-Shirt Blue', 'T-Shirt Red'], $names->all());
        
        // Verify IDs
        $variantIds = collect($results)->pluck('variant_id')->sort()->values();
        $expectedIds = collect([$this->variantRed->id, $this->variantBlue->id])->sort()->values()->all();
        $this->assertEquals($expectedIds, $variantIds->all());
    }

    /** @test */
    public function it_finds_specific_variant_by_name()
    {
        Sanctum::actingAs($this->user);

        // Search for "Red"
        $response = $this->getJson('/api/quick-order/search?q=Red');

        $response->assertStatus(200);
        $results = $response->json('data');

        $this->assertCount(1, $results);
        $this->assertEquals('T-Shirt Red', $results[0]['name']);
        $this->assertEquals($this->variantRed->id, $results[0]['variant_id']);
    }

    /** @test */
    public function it_finds_specific_variant_by_sku()
    {
        Sanctum::actingAs($this->user);

        // Search for "TSHIRT-BLUE"
        $response = $this->getJson('/api/quick-order/search?q=TSHIRT-BLUE');

        $response->assertStatus(200);
        $results = $response->json('data');

        $this->assertCount(1, $results);
        $this->assertEquals('T-Shirt Blue', $results[0]['name']);
        $this->assertEquals($this->variantBlue->id, $results[0]['variant_id']);
    }

    /** @test */
    public function it_supports_search_alias_and_pagination_structure()
    {
        Sanctum::actingAs($this->user);

        // Use 'search' instead of 'q'
        $response = $this->getJson('/api/quick-order/search?search=T-Shirt');

        $response->assertStatus(200);
        
        $response->assertJsonStructure([
            'data',
            'current_page',
            'last_page',
            'per_page',
            'total'
        ]);

        $results = $response->json('data');
        $this->assertCount(2, $results);
    }

    /** @test */
    public function it_returns_default_products_when_search_is_empty()
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/quick-order/search');

        $response->assertStatus(200);
        $results = $response->json('data');

        // Should return at least the products we created
        // We have 1 product with 2 variants -> expands to 2 items
        $this->assertGreaterThanOrEqual(2, count($results));
    }
}
