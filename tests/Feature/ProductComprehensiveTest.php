<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\ProductDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductComprehensiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_full_product_with_all_requirements()
    {
        $category = Category::create(['name' => 'Main Cat', 'slug' => 'main-cat']);
        $brand = Brand::create(['name' => 'Brand X', 'slug' => 'brand-x']);

        $data = [
            'name' => 'Super Product',
            'slug' => 'super-product',
            'internal_code' => 'INT-001',
            'barcode' => '123456789',
            'erp_id' => 'ERP-999',
            'type' => 'simple',
            'status' => 'published',
            'visibility' => 'public',
            'short_description' => 'Short desc',
            'long_description' => '<p>Long desc</p>',
            'main_category_id' => $category->id,
            'brand_id' => $brand->id,
            'tags' => ['nou', 'promo'],
            'key_benefits' => ['Fast', 'Cheap'],
            'technical_specs' => ['Weight' => '1kg', 'Color' => 'Red'],
            'meta_title' => 'SEO Title',
            'meta_description' => 'SEO Desc',
            'meta_keywords' => 'seo, keywords',
            'video_url' => 'http://video.com/1',
            'list_price' => 100.00,
            'rrp_price' => 120.00,
            'vat_rate' => 19.00,
            'vat_included' => false,
            'price_override' => 95.00,
            'currency' => 'RON',
            'stock_status' => 'in_stock',
            'stock_qty' => 50,
            'supplier_stock_qty' => 10,
            'min_stock_limit' => 5,
            'allow_backorder' => true,
            'overstock_policy' => 'warning',
            'lead_time_days' => 2,
            'estimated_delivery_text' => '2-3 days',
            'unit_of_measure' => 'buc',
            'min_order_quantity' => 1,
            'order_quantity_step' => 1,
            'requires_quote' => false,
            'erp_sync_status' => 'synced',
            'erp_last_sync_at' => now(),
        ];

        $product = Product::create($data);

        $this->assertDatabaseHas('products', ['slug' => 'super-product']);
        $this->assertEquals(['nou', 'promo'], $product->tags);
        $this->assertEquals(['Weight' => '1kg', 'Color' => 'Red'], $product->technical_specs);
        $this->assertTrue($product->allow_backorder);
        $this->assertEquals('RON', $product->currency);

        // Test Relationships
        
        // Units
        $product->units()->create([
            'name' => 'box',
            'conversion_factor' => 12,
            'is_default' => false,
            'price_calculation_mode' => 'per_unit'
        ]);
        $this->assertCount(1, $product->units);

        // Documents
        $product->documents()->create([
            'name' => 'Manual',
            'path' => '/docs/manual.pdf',
            'type' => 'manual',
            'version' => '1.0',
            'language' => 'ro',
            'visibility' => 'public'
        ]);
        $this->assertCount(1, $product->documents);
    }

    public function test_attribute_comparable()
    {
        $attr = Attribute::create([
            'name' => 'Color',
            'slug' => 'color',
            'type' => 'text',
            'is_filterable' => true,
            'is_comparable' => true
        ]);
        
        $this->assertTrue($attr->is_comparable);
    }
}
