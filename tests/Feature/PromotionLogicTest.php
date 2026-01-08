<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionTier;
use App\Models\DiscountRule;
use App\Models\Cart;
use App\Models\Role;
use App\Models\Category;
use App\Services\Pricing\PromotionEngine;
use App\Services\Pricing\PromotionPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class PromotionLogicTest extends TestCase
{
    use RefreshDatabase;

    protected PromotionEngine $promotionEngine;
    protected PromotionPricingService $pricingService;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure roles exist
        if (!Role::where('name', 'admin')->exists()) Role::create(['name' => 'admin', 'slug' => 'admin', 'code' => 'ADMIN', 'label' => 'Admin']);
        if (!Role::where('name', 'sales_agent')->exists()) Role::create(['name' => 'sales_agent', 'slug' => 'sales_agent', 'code' => 'AGENT', 'label' => 'Agent']);
        
        $this->category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_published' => true
        ]);

        $this->promotionEngine = app(PromotionEngine::class);
        $this->pricingService = app(PromotionPricingService::class);
    }

    /** @test */
    public function it_applies_percentage_promotion_correctly()
    {
        $product = Product::create(['name' => 'P1', 'slug' => 'p1', 'internal_code' => 'P1', 'list_price' => 100, 'main_category_id' => $this->category->id]);
        $promotion = Promotion::create([
            'name' => '10% Off',
            'slug' => '10-off',
            'status' => 'active',
            'type' => 'standard',
            'value_type' => 'percent',
            'value' => 10,
            'customer_type' => 'both',
        ]);
        $promotion->products()->attach($product->id);

        $priceData = $this->promotionEngine->getProductPriceWithPromotions($product);

        $this->assertEquals(90, $priceData['final_price']);
        $this->assertEquals(10, $priceData['applied_promotions'][0]['value']);
    }

    /** @test */
    public function it_applies_fixed_amount_promotion_correctly()
    {
        $product = Product::create(['name' => 'P2', 'slug' => 'p2', 'internal_code' => 'P2', 'list_price' => 100, 'main_category_id' => $this->category->id]);
        $promotion = Promotion::create([
            'name' => '15 RON Off',
            'slug' => '15-ron-off',
            'status' => 'active',
            'type' => 'standard',
            'value_type' => 'fixed_amount',
            'value' => 15,
            'customer_type' => 'both',
        ]);
        $promotion->products()->attach($product->id);

        $priceData = $this->promotionEngine->getProductPriceWithPromotions($product);

        $this->assertEquals(85, $priceData['final_price']);
    }

    /** @test */
    public function it_applies_fixed_price_promotion_correctly()
    {
        $product = Product::create(['name' => 'P3', 'slug' => 'p3', 'internal_code' => 'P3', 'list_price' => 100, 'main_category_id' => $this->category->id]);
        $promotion = Promotion::create([
            'name' => 'Fixed Price 50',
            'slug' => 'fixed-50',
            'status' => 'active',
            'type' => 'standard',
            'value_type' => 'fixed_price',
            'value' => 50,
            'customer_type' => 'both',
        ]);
        $promotion->products()->attach($product->id);

        $priceData = $this->promotionEngine->getProductPriceWithPromotions($product);

        $this->assertEquals(50, $priceData['final_price']);
    }

    /** @test */
    public function it_selects_best_deal_among_multiple_promotions()
    {
        $product = Product::create(['name' => 'P4', 'slug' => 'p4', 'internal_code' => 'P4', 'list_price' => 100, 'main_category_id' => $this->category->id]);
        
        // Promo 1: 10% Off -> 90
        $p1 = Promotion::create([
            'name' => '10% Off',
            'slug' => '10-off',
            'status' => 'active',
            'type' => 'standard',
            'value_type' => 'percent',
            'value' => 10,
            'customer_type' => 'both',
        ]);
        $p1->products()->attach($product->id);

        // Promo 2: 20% Off -> 80 (Winner)
        $p2 = Promotion::create([
            'name' => '20% Off',
            'slug' => '20-off',
            'status' => 'active',
            'type' => 'standard',
            'value_type' => 'percent',
            'value' => 20,
            'customer_type' => 'both',
        ]);
        $p2->products()->attach($product->id);

        $priceData = $this->promotionEngine->getProductPriceWithPromotions($product);

        $this->assertEquals(80, $priceData['final_price']);
        $this->assertEquals($p2->id, $priceData['applied_promotions'][0]['id']);
    }

    /** @test */
    public function it_respects_customer_type_segmentation()
    {
        $product = Product::create(['name' => 'P5', 'slug' => 'p5', 'internal_code' => 'P5', 'list_price' => 100, 'main_category_id' => $this->category->id]);
        
        // B2B Only Promo
        $b2bPromo = Promotion::create([
            'name' => 'B2B Promo',
            'slug' => 'b2b-promo',
            'status' => 'active',
            'type' => 'standard',
            'value_type' => 'percent',
            'value' => 50,
            'customer_type' => 'b2b',
        ]);
        $b2bPromo->products()->attach($product->id);

        // 1. Check for Guest (B2C implicitly or just not B2B)
        $priceGuest = $this->promotionEngine->getProductPriceWithPromotions($product);
        // Should NOT apply
        $this->assertEquals(100, $priceGuest['final_price']);

        // 2. Check for B2C Customer
        $b2cCustomer = Customer::create(['name' => 'B2C Client', 'email' => 'b2c@test.com', 'type' => 'b2c']);
        $b2cUser = User::factory()->create();
        $b2cUser->customer()->associate($b2cCustomer);
        $b2cUser->save();

        $priceB2C = $this->promotionEngine->getProductPriceWithPromotions($product, $b2cUser, $b2cCustomer);
        // Should NOT Apply
        $this->assertEquals(100, $priceB2C['final_price']);

        // 3. Check for B2B Customer
        $b2bCustomer = Customer::create(['name' => 'B2B Client', 'email' => 'b2b@test.com', 'type' => 'b2b']);
        $b2bUser = User::factory()->create();
        $b2bUser->customer()->associate($b2bCustomer);
        $b2bUser->save();

        $priceB2B = $this->promotionEngine->getProductPriceWithPromotions($product, $b2bUser, $b2bCustomer);
        // Should Apply
        $this->assertEquals(50, $priceB2B['final_price']);
    }

    /** @test */
    public function it_respects_global_discount_cap()
    {
        $product = Product::create(['name' => 'P6', 'slug' => 'p6', 'internal_code' => 'P6', 'list_price' => 100, 'main_category_id' => $this->category->id]);
        
        // Huge Promo: 90% Off -> Price 10
        $promo = Promotion::create([
            'name' => 'Huge Promo',
            'slug' => 'huge-promo',
            'status' => 'active',
            'type' => 'standard',
            'value_type' => 'percent',
            'value' => 90,
            'customer_type' => 'both',
        ]);
        $promo->products()->attach($product->id);

        // Global Cap: Max 50% Off
        DiscountRule::create([
            'name' => 'Global Cap',
            'rule_type' => 'max_discount',
            'target_type' => 'global',
            'limit_percent' => 50,
            'apply_to_total' => true,
            'active' => true,
        ]);

        $priceData = $this->promotionEngine->getProductPriceWithPromotions($product);

        // Should be capped at 50% off -> Price 50
        $this->assertEquals(50, $priceData['final_price']);
        
        // Verify we have the "Limită Discount" explanation
        $hasCap = false;
        foreach ($priceData['applied_promotions'] as $applied) {
            if ($applied['slug'] === 'max-discount-cap') $hasCap = true;
        }
        $this->assertTrue($hasCap, 'Should have max-discount-cap applied');
    }

    /** @test */
    public function it_applies_volume_discounts()
    {
        $product = Product::create(['name' => 'P7', 'slug' => 'p7', 'internal_code' => 'P7', 'list_price' => 100, 'main_category_id' => $this->category->id]);
        
        $promo = Promotion::create([
            'name' => 'Volume Tier',
            'slug' => 'volume-tier',
            'status' => 'active',
            'type' => 'volume',
            'value_type' => 'percent', // Placeholder
            'value' => 0,
            'customer_type' => 'both',
        ]);
        $promo->products()->attach($product->id);
        
        // Tier 1: Buy 5+ get 10% off
        PromotionTier::create([
            'promotion_id' => $promo->id,
            'min_qty' => 5,
            'value' => 10, // 10%
        ]);

        // Tier 2: Buy 10+ get 20% off
        PromotionTier::create([
            'promotion_id' => $promo->id,
            'min_qty' => 10,
            'value' => 20, // 20%
        ]);

        // Case 1: Buy 1 -> No discount
        $p1 = $this->promotionEngine->getProductPriceWithPromotions($product, null, null, 1);
        $this->assertEquals(100, $p1['final_price']);

        // Case 2: Buy 5 -> 10% off
        $p5 = $this->promotionEngine->getProductPriceWithPromotions($product, null, null, 5);
        $this->assertEquals(90, $p5['final_price']);

        // Case 3: Buy 10 -> 20% off
        $p10 = $this->promotionEngine->getProductPriceWithPromotions($product, null, null, 10);
        $this->assertEquals(80, $p10['final_price']);
    }

    /** @test */
    public function it_processes_gift_promotions_in_cart()
    {
        $mainProduct = Product::create(['name' => 'Main Product', 'slug' => 'main-p', 'internal_code' => 'MAIN', 'list_price' => 100, 'main_category_id' => $this->category->id]);
        $giftProduct = Product::create(['name' => 'Free Gift', 'slug' => 'gift-p', 'internal_code' => 'GIFT', 'list_price' => 50, 'main_category_id' => $this->category->id]);

        $promo = Promotion::create([
            'name' => 'Buy Main Get Gift',
            'slug' => 'buy-main-get-gift',
            'status' => 'active',
            'type' => 'gift',
            'value_type' => 'fixed_amount',
            'value' => 0,
            'customer_type' => 'both',
            'min_qty_per_product' => 2, // Buy 2 to get gift
            'settings' => [
                'gift_product_id' => $giftProduct->id,
                'gift_qty' => 1,
            ]
        ]);
        $promo->products()->attach($mainProduct->id);

        // Mock Cart Items
        $items = collect([
            (object)[
                'id' => 1,
                'product_id' => $mainProduct->id,
                'product' => $mainProduct,
                'quantity' => 2, // Meets threshold
            ]
        ]);

        $result = $this->promotionEngine->calculateItems($items);

        // Should have original item + gift item
        $this->assertCount(2, $result['items']);
        
        $giftItem = collect($result['items'])->firstWhere('is_gift', true);
        $this->assertNotNull($giftItem);
        $this->assertEquals($giftProduct->id, $giftItem['product_id']);
        $this->assertEquals(0, $giftItem['line_total']);
    }
}
