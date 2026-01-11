<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductUnit;
use App\Models\Promotion;
use App\Models\PromotionTier;
use App\Models\Category;
use App\Models\Role;
use App\Services\Pricing\PromotionEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class UomIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected PromotionEngine $promotionEngine;
    protected Category $category;
    protected Product $product;
    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Setup Roles
        if (!Role::where('name', 'admin')->exists()) Role::create(['name' => 'admin', 'slug' => 'admin', 'code' => 'ADMIN', 'label' => 'Admin']);
        if (!Role::where('name', 'sales_agent')->exists()) Role::create(['name' => 'sales_agent', 'slug' => 'sales_agent', 'code' => 'AGENT', 'label' => 'Agent']);

        $this->promotionEngine = app(PromotionEngine::class);

        $this->category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_published' => true
        ]);

        // Create Product with base price 10
        $this->product = Product::create([
            'name' => 'Base Product',
            'slug' => 'base-product',
            'internal_code' => 'BASE01',
            'list_price' => 10,
            'main_category_id' => $this->category->id,
            'unit_of_measure' => 'buc'
        ]);

        // Create Variant
        $this->variant = ProductVariant::create([
            'product_id' => $this->product->id,
            'name' => 'Variant Red',
            'sku' => 'BASE01-RED',
            'list_price' => 12, // Slightly more expensive
            'stock_qty' => 100
        ]);

        // Create Product Unit: Box = 10 buc
        ProductUnit::create([
            'product_id' => $this->product->id,
            'name' => 'Box 10',
            'unit' => 'box',
            'conversion_factor' => 10,
            'is_base' => false,
        ]);

        // Create Variant Specific Unit: Pallet = 100 buc (variant only)
        ProductUnit::create([
            'product_id' => $this->product->id,
            'product_variant_id' => $this->variant->id,
            'name' => 'Pallet 100',
            'unit' => 'pallet',
            'conversion_factor' => 100,
            'is_base' => false,
        ]);
    }

    /** @test */
    public function it_calculates_price_using_uom_conversion_factor()
    {
        // Buy 1 Box of Base Product
        // Base Price = 10. Box Factor = 10.
        // Expected Unit Price = 100.
        
        $item = new \stdClass();
        $item->product = $this->product;
        $item->quantity = 1;
        $item->unit = 'box';
        $item->product_variant_id = null;
        $item->id = 1;

        $items = collect([$item]);
        $result = $this->promotionEngine->calculateItems($items);
        $calculatedItem = $result['items'][0];

        $this->assertEquals(10, $calculatedItem['conversion_factor']);
        $this->assertEquals(100, $calculatedItem['unit_base_price']);
        $this->assertEquals(100, $calculatedItem['line_total']);
    }

    /** @test */
    public function it_calculates_variant_price_using_variant_specific_uom()
    {
        // Buy 1 Pallet of Variant Red
        // Variant Price = 12. Pallet Factor = 100.
        // Expected Unit Price = 1200.

        $item = new \stdClass();
        $item->product = $this->product; // Engine usually needs product loaded
        $item->quantity = 1;
        $item->unit = 'pallet';
        $item->product_variant_id = $this->variant->id;
        $item->id = 1;

        $items = collect([$item]);
        
        // Mock finding variant logic inside engine? 
        // Engine uses $item->product_variant_id to find unit, but it calculates price based on product list_price usually if not adjusted.
        // Wait, PromotionEngine::getProductPriceWithPromotions uses product->list_price by default unless passed.
        // But PromotionEngine::calculateItems doesn't explicitely fetch variant price override unless we handle it.
        // Let's check calculateItems logic again. 
        // It calls getProductPriceWithPromotions. 
        // If we want variant price, we need to ensure getBasePrice handles it or calculateItems adjusts it.
        // Currently getBasePrice checks contract prices.
        
        // ISSUE: calculateItems uses $product->list_price (via getBasePrice) if no contract.
        // It does NOT seem to switch to variant price automatically in the current code I saw.
        // Let's verify this behavior. If it fails, I found a bug/missing feature.
        
        // Actually, in `CartController`, we calculate `unit_price` using `$variant->list_price`.
        // In `QuickOrderController`, we use `calculateItems`.
        // If `calculateItems` doesn't look at variant price, we have a problem.
        
        // Let's run this test and see. 
        // If it uses Product Price (10) * 100 = 1000.
        // If it uses Variant Price (12) * 100 = 1200.
        
        $result = $this->promotionEngine->calculateItems($items);
        $calculatedItem = $result['items'][0];

        $this->assertEquals(100, $calculatedItem['conversion_factor']);
        $this->assertEquals(1200, $calculatedItem['unit_base_price']); 
    }

    /** @test */
    public function it_applies_volume_discount_based_on_base_units()
    {
        // Promo: Buy 20+ pieces get 10% off.
        $promotion = Promotion::create([
            'name' => 'Volume 20+',
            'slug' => 'vol-20',
            'status' => 'active',
            'type' => 'volume',
            'value_type' => 'percent',
            'customer_type' => 'both',
        ]);
        $promotion->products()->attach($this->product->id);
        
        PromotionTier::create([
            'promotion_id' => $promotion->id,
            'min_qty' => 20,
            'value' => 10, // 10% off
        ]);

        // Scenario 1: Buy 1 Box (10 pcs). Total Qty = 10. No Discount.
        $item1 = new \stdClass();
        $item1->product = $this->product;
        $item1->quantity = 1;
        $item1->unit = 'box'; // 10 pcs
        $item1->product_variant_id = null;
        $item1->id = 1;

        $items1 = collect([$item1]);
        $result1 = $this->promotionEngine->calculateItems($items1);
        $this->assertEquals(100, $result1['items'][0]['unit_final_price']); // 10 * 10 = 100
        $this->assertEmpty($result1['items'][0]['applied_promotions']);

        // Scenario 2: Buy 3 Boxes (30 pcs). Total Qty = 30. Discount Applies.
        $item2 = clone $item1;
        $item2->quantity = 3;

        $items2 = collect([$item2]);
        $result2 = $this->promotionEngine->calculateItems($items2);
        
        // Price = 10 * 10 = 100. Discount 10% -> 90.
        $this->assertEquals(90, $result2['items'][0]['unit_final_price']);
        $this->assertNotEmpty($result2['items'][0]['applied_promotions']);
    }
}
