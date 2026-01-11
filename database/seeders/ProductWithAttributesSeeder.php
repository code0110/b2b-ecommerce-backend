<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\ProductUnit;
use Illuminate\Support\Str;

class ProductWithAttributesSeeder extends Seeder
{
    public function run()
    {
        // 1. Creăm atributul Capacitate
        $attrCapacitate = Attribute::firstOrCreate(
            ['slug' => 'capacitate'],
            ['name' => 'Capacitate', 'type' => 'text', 'is_filterable' => true]
        );

        // 2. Creăm produsul părinte
        $product = Product::create([
            'name' => 'Vopsea Superioară Interior',
            'slug' => 'vopsea-superioara-interior',
            'internal_code' => 'VOP-SUP',
            'status' => 'published',
            'main_category_id' => 1, // Presupunem că există categoria 1
            'list_price' => 0, // Preț variabil
            'unit_of_measure' => 'buc',
            'stock_status' => 'in_stock',
            'long_description' => '<p>Vopsea de înaltă calitate pentru interior.</p>',
        ]);

        // 3. Creăm variantele
        $variants = [
            ['val' => '2.5L', 'price' => 45.00, 'sku' => 'VOP-SUP-2.5'],
            ['val' => '10L',  'price' => 150.00, 'sku' => 'VOP-SUP-10'],
            ['val' => '15L',  'price' => 210.00, 'sku' => 'VOP-SUP-15'],
        ];

        foreach ($variants as $vData) {
            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'name' => 'Vopsea Superioară Interior ' . $vData['val'],
                'slug' => 'vopsea-superioara-interior-' . Str::slug($vData['val']),
                'sku' => $vData['sku'],
                'list_price' => $vData['price'],
                'stock_qty' => 100,
                'stock_status' => 'in_stock',
            ]);

            // 4. Asociem atributul
            AttributeValue::create([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'attribute_id' => $attrCapacitate->id,
                'value' => $vData['val'],
            ]);
            
            // 5. Adăugăm unități de măsură specifice (ex: Palet)
            // Pentru 2.5L, un palet are 100 buc
            // Pentru 10L, un palet are 40 buc
            // Pentru 15L, un palet are 24 buc
            
            $paletQty = match($vData['val']) {
                '2.5L' => 100,
                '10L' => 40,
                '15L' => 24,
                default => 10
            };

            ProductUnit::create([
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'name' => 'Palet',
                'unit' => 'palet',
                'conversion_factor' => $paletQty,
                'is_base' => false,
                'is_default' => false,
            ]);
        }
        
        $this->command->info('Produs creat: Vopsea Superioară Interior (cu atribute și variante)');
    }
}
