<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Support\Str;

class ProductWithVariantsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Găsește sau creează categorii și branduri necesare
        $category = Category::firstOrCreate(
            ['slug' => 'vopsele'],
            ['name' => 'Vopsele & Amorse', 'is_published' => true]
        );
        
        $brand = Brand::firstOrCreate(
            ['slug' => 'savana'],
            ['name' => 'Savana', 'is_published' => true]
        );

        // 2. Creează Produsul Părinte
        $product = Product::firstOrCreate(
            ['slug' => 'vopsea-lavabila-savana-teflon'],
            [
                'name' => 'Vopsea Lavabilă Savana cu Teflon',
                'internal_code' => 'SAV-TEFLON',
                'brand_id' => $brand->id,
                'main_category_id' => $category->id,
                'list_price' => 150.00, // Preț de bază
                'stock_qty' => 100,
                'status' => 'published',
                'visibility' => 'public',
                'is_promo' => false,
                'tags' => ['vopsea', 'lavabila', 'teflon', 'interior'],
                'unit_of_measure' => 'buc',
                'vat_rate' => 19.00
            ]
        );
        $product->categories()->syncWithoutDetaching([$category->id]);

        // 3. Definește Atributele
        $attrColor = Attribute::firstOrCreate(
            ['slug' => 'culoare'],
            ['name' => 'Culoare', 'type' => 'text', 'is_filterable' => true]
        );

        $attrVolume = Attribute::firstOrCreate(
            ['slug' => 'cantitate'],
            ['name' => 'Cantitate', 'type' => 'text', 'is_filterable' => true]
        );

        // 4. Definește Variantele
        $variantsData = [
            [
                'sku' => 'SAV-TEFLON-ALB-2.5',
                'name' => 'Vopsea Lavabilă Savana cu Teflon, Alb, 2.5L',
                'price' => 45.00,
                'stock' => 50,
                'attrs' => [
                    'culoare' => 'Alb',
                    'cantitate' => '2.5L'
                ]
            ],
            [
                'sku' => 'SAV-TEFLON-ALB-10',
                'name' => 'Vopsea Lavabilă Savana cu Teflon, Alb, 10L',
                'price' => 160.00,
                'stock' => 30,
                'attrs' => [
                    'culoare' => 'Alb',
                    'cantitate' => '10L'
                ]
            ],
            [
                'sku' => 'SAV-TEFLON-ALB-15',
                'name' => 'Vopsea Lavabilă Savana cu Teflon, Alb, 15L',
                'price' => 220.00,
                'stock' => 20,
                'attrs' => [
                    'culoare' => 'Alb',
                    'cantitate' => '15L'
                ]
            ]
        ];

        foreach ($variantsData as $vData) {
            $variant = ProductVariant::firstOrCreate(
                ['sku' => $vData['sku']],
                [
                    'product_id' => $product->id,
                    'name' => $vData['name'],
                    'slug' => Str::slug($vData['name']),
                    'list_price' => $vData['price'],
                    'stock_qty' => $vData['stock'],
                    'stock_status' => 'in_stock'
                ]
            );

            // Adaugă valorile atributelor pentru această variantă
            foreach ($vData['attrs'] as $attrSlug => $value) {
                $attribute = $attrSlug === 'culoare' ? $attrColor : $attrVolume;
                
                AttributeValue::firstOrCreate(
                    [
                        'product_variant_id' => $variant->id,
                        'attribute_id' => $attribute->id
                    ],
                    [
                        'product_id' => $product->id,
                        'value' => $value
                    ]
                );
            }
        }

        $this->command->info("Produs cu variații creat: {$product->name}");
    }
}
