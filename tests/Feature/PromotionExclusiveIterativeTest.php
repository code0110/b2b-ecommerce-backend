<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionTier;
use App\Models\Category;
use App\Services\Pricing\PromotionEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PromotionExclusiveIterativeTest extends TestCase
{
    use RefreshDatabase;

    protected PromotionEngine $promotionEngine;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->category = Category::create([
            'name' => 'Test Category',
            'slug' => 'test-category',
            'is_published' => true
        ]);

        $this->promotionEngine = app(PromotionEngine::class);
    }

    /** @test */
    public function it_prioritizes_exclusive_promotions_over_better_standard_ones()
    {
        $product = Product::create([
            'name' => 'P_Ex', 
            'slug' => 'p-ex', 
            'list_price' => 100, 
            'main_category_id' => $this->category->id,
            'internal_code' => 'P_EX_001'
        ]);
        
        // Standard Promo: 50% Off (Best Deal mathematically)
        $standard = Promotion::create([
            'name' => 'Standard 50%',
            'slug' => 'std-50',
            'type' => 'standard',
            'value_type' => 'percent',
            'value' => 50,
            'status' => 'active',
            'customer_type' => 'both',
        ]);
        $standard->products()->attach($product->id);

        // Exclusive Promo: 10% Off (Worse Deal, but Exclusive)
        $exclusive = Promotion::create([
            'name' => 'Exclusive 10%',
            'slug' => 'excl-10',
            'type' => 'standard',
            'value_type' => 'percent',
            'value' => 10,
            'status' => 'active',
            'is_exclusive' => true,
            'customer_type' => 'both',
        ]);
        $exclusive->products()->attach($product->id);

        // Act
        $priceData = $this->promotionEngine->getProductPriceWithPromotions($product);

        // Assert
        // If exclusive logic works, it should pick the 10% off (90 RON), ignoring the 50% off (50 RON).
        // Current implementation likely picks 50 RON (fails).
        $this->assertEquals(90, $priceData['final_price'], 'Exclusive promotion did not take precedence over better standard promotion.');
        $this->assertEquals($exclusive->id, $priceData['applied_promotions'][0]['id']);
    }

    /** @test */
    public function it_applies_iterative_volume_discounts_correctly()
    {
        $product = Product::create([
            'name' => 'P_Vol', 
            'slug' => 'p-vol', 
            'list_price' => 100, 
            'main_category_id' => $this->category->id,
            'internal_code' => 'P_VOL_001'
        ]);
        
        $promo = Promotion::create([
            'name' => 'Iterative Volume',
            'slug' => 'iter-vol',
            'type' => 'volume', // Using existing volume type
            'value_type' => 'percent',
            'value' => 0,
            'status' => 'active',
            'is_iterative' => true, // Flagging as iterative
            'customer_type' => 'both',
        ]);
        $promo->products()->attach($product->id);
        
        // Tier 1: 10+ items -> 10%
        PromotionTier::create(['promotion_id' => $promo->id, 'min_qty' => 10, 'value' => 10]);
        // Tier 2: 20+ items -> 20%
        PromotionTier::create(['promotion_id' => $promo->id, 'min_qty' => 20, 'value' => 20]);

        // Case 1: 15 items -> should match Tier 1 (10%)
        $p15 = $this->promotionEngine->getProductPriceWithPromotions($product, null, null, 15);
        $this->assertEquals(90, $p15['final_price']);

        // Case 2: 25 items -> should match Tier 2 (20%)
        $p25 = $this->promotionEngine->getProductPriceWithPromotions($product, null, null, 25);
        $this->assertEquals(80, $p25['final_price']);
    }
}
