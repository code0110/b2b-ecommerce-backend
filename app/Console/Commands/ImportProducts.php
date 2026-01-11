<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportProducts extends Command
{
    protected $signature = 'app:import-products {file=products_feed_v3.xlsx} {--inspect} {--fresh}';
    protected $description = 'Import products from Excel file with specific unit conversions. Use --fresh to truncate tables.';

    public function handle()
    {
        if ($this->option('fresh')) {
            if ($this->confirm('Are you sure you want to delete ALL products and import from scratch?')) {
                $this->info('Truncating tables...');
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                ProductImage::truncate();
                ProductVariant::truncate();
                Product::truncate();
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                $this->info('Tables truncated.');
            }
        }

        $file = $this->argument('file');
        $filePath = base_path($file);
        $csvPath = base_path('temp_import.csv');

        if (!file_exists($filePath)) {
            $this->error("File not found: $filePath");
            $this->info("Please place the file '$file' in the project root.");
            return 1;
        }

        $this->info("Converting Excel to CSV...");
        $pyScript = base_path('convert_xlsx_to_csv.py');
        $cmd = "python \"$pyScript\" \"$filePath\" \"$csvPath\"";
        
        exec($cmd, $output, $returnVar);
        
        if ($returnVar !== 0) {
            $this->error("Conversion failed.");
            $this->error(implode("\n", $output));
            return 1;
        }

        if (!file_exists($csvPath)) {
            $this->error("CSV file not created.");
            return 1;
        }

        $this->info("Reading CSV...");
        $handle = fopen($csvPath, 'r');
        $headers = fgetcsv($handle);

        if ($this->option('inspect')) {
            $this->info("Found headers:");
            foreach ($headers as $i => $h) {
                $this->line("$i: $h");
            }
            fclose($handle);
            unlink($csvPath);
            return 0;
        }

        // Map headers to indices
        $headerMap = array_flip(array_map('strtolower', array_map('trim', $headers)));
        
        // Check for required columns (adjust these based on actual file)
        // I'll list what I expect, but won't fail yet if missing, just warn
        $expected = ['cod_produs', 'titlu', 'stoc_cantitate', 'cantitate/um'];
        $missing = [];
        foreach ($expected as $col) {
            if (!isset($headerMap[$col])) {
                $missing[] = $col;
            }
        }

        // Find all indices for 'brand' since it might appear multiple times
        $brandIndices = [];
        foreach ($headers as $i => $h) {
            if (strtolower(trim($h)) === 'brand') {
                $brandIndices[] = $i;
            }
        }
        $this->info("Found Brand columns at indices: " . implode(', ', $brandIndices));

        if (!empty($missing)) {
            $this->warn("Potentially missing columns: " . implode(', ', $missing));
            if (!$this->confirm('Do you want to continue with best-guess mapping?')) {
                fclose($handle);
                unlink($csvPath);
                return 1;
            }
        }

        $this->info("Importing products...");
        
        $defaultCategory = Category::first();
        $defaultCategoryId = $defaultCategory ? $defaultCategory->id : 1;

        $count = 0;
        $updated = 0;
        
        // Map to store item_id -> product_id for parent resolution
        $parentIdMap = [];
        
        // We need to process the CSV in two passes:
        // Pass 1: Products (Parents & Simple)
        // Pass 2: Variations (Children)
        
        // Pass 1: Products
        $this->info("Pass 1: Importing Products...");
        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                // Helper to get value by name
                $get = function($name) use ($headerMap, $row, $headers) {
                    $key = strtolower($name);
                    if (isset($headerMap[$key])) return $row[$headerMap[$key]];
                    foreach ($headerMap as $h => $i) {
                        if (str_contains($h, $name)) return $row[$i];
                    }
                    return null;
                };

                $itemId = $row[$headerMap['item_id']] ?? null;
                $tip = strtolower($get('Tip') ?? 'simple');
                
                // Skip variations in Pass 1
                if ($tip === 'variation' || $tip === 'variable-subscription' || ($get('parent_id') && $tip !== 'variable')) {
                    // Note: 'variable' usually means parent. 'variation' means child.
                    // If parent_id is set, it's likely a child, UNLESS it's a top-level grouping?
                    // Let's rely on 'Tip'.
                    if ($tip === 'variation') continue;
                }

                $sku = $row[$headerMap['cod_produs']] ?? null;
                if (!$sku) continue;

                $product = Product::where('internal_code', $sku)->first();
                if (!$product) {
                    $product = new Product();
                    $product->internal_code = $sku;
                    $product->main_category_id = $defaultCategoryId;
                }

                $name = $row[$headerMap['titlu']] ?? null;
                if ($name) {
                    $product->name = $name;
                    if (!$product->slug) {
                        $product->slug = Str::slug($name) . '-' . strtolower($sku);
                    }
                }

                // Description
                $longDesc = $row[$headerMap['descriere']] ?? null;
                if ($longDesc) $product->long_description = $longDesc;

                $shortDesc = $row[$headerMap['descriere_scurta']] ?? null;
                if ($shortDesc) $product->short_description = $shortDesc;

                // Stock & Units
                $stock = $row[$headerMap['stoc_cantitate']] ?? 0;
                $stepOrUnit = $row[$headerMap['cantitate/um']] ?? 1;
                $packUnit = $row[$headerMap['ambalare']] ?? null; 

                $step = 1;
                $unit = 'buc';

                if (is_numeric($stepOrUnit)) {
                    $step = (float)$stepOrUnit;
                } else {
                    if (preg_match('/([\d\.,]+)\s*([a-zA-Z]+)/', $stepOrUnit, $matches)) {
                        $step = (float)str_replace(',', '.', $matches[1]);
                        $unit = $matches[2];
                    }
                }
                if ($step <= 0) $step = 1;

                if ($unit && $unit !== 'buc') {
                    $product->unit_of_measure = $unit;
                }
                
                if ($packUnit) {
                    $product->packaging_unit = $packUnit;
                }

                $product->stock_qty = (float)$stock;
                $product->order_quantity_step = $step;

                // Price
                $price = $row[$headerMap['pret_regular']] ?? 0;
                $product->price = (float)$price;
                $product->list_price = (float)$price;

                $promoPrice = $row[$headerMap['pret_promo']] ?? null;
                if ($promoPrice && $promoPrice > 0) {
                    $product->price = (float)$promoPrice;
                    $product->is_promo = true;
                }

                // Status
                $inStock = $row[$headerMap['in_stoc']] ?? null;
                if ($inStock !== null) {
                    $product->stock_status = ((float)$stock > 0) ? 'in_stock' : 'out_of_stock';
                }

                // Brand
                $brandName = null;
                foreach ($brandIndices as $idx) {
                    if (!empty($row[$idx])) {
                        $brandName = trim($row[$idx]);
                        break;
                    }
                }

                // Fallback: Check Title for known brands if column is empty
                if (!$brandName && $name) {
                    $knownBrands = [
                        'Soudal' => ['Soudal', 'Fix ALL'],
                        'Bilka' => ['Bilka'],
                        'Devorex' => ['Devorex'],
                        'Decora' => ['Decora', 'Vidella'], // Vidella is often Decora
                        'Alcadrain' => ['Alcadrain'],
                        'Valrom' => ['Valrom'],
                        'Teraplast' => ['Teraplast'],
                        'Wavin' => ['Wavin'],
                        'Tytan' => ['Tytan'],
                        'Ceresit' => ['Ceresit'],
                        'Baumit' => ['Baumit'],
                        'Knauf' => ['Knauf'],
                        'Mapei' => ['Mapei'],
                        'Rigips' => ['Rigips'],
                        'Isover' => ['Isover'],
                        'Rockwool' => ['Rockwool'],
                        'Ursa' => ['Ursa'],
                        'Austrotherm' => ['Austrotherm'],
                        'Swisspor' => ['Swisspor'],
                        'Weber' => ['Weber'],
                        'Hasit' => ['Hasit'],
                        'Caparol' => ['Caparol'],
                        'Kober' => ['Kober'],
                        'Savana' => ['Savana'],
                        'Apla' => ['Apla'],
                        'Danke' => ['Danke'],
                        'Oskar' => ['Oskar'],
                        'Sticky' => ['Sticky'],
                        'Policolor' => ['Policolor'],
                        'Duraziv' => ['Duraziv'],
                        'Adeplast' => ['Adeplast'],
                        'Bison' => ['Bison'],
                        'Moment' => ['Moment'],
                        'Loctite' => ['Loctite'],
                        'Poxipol' => ['Poxipol'],
                        'Sika' => ['Sika'],
                        'Den Braven' => ['Den Braven'],
                        'Penosil' => ['Penosil'],
                        'Cemacon' => ['Cemacon'],
                        'Wienerberger' => ['Wienerberger', 'Porotherm'],
                        'Ytong' => ['Ytong'],
                        'Macon' => ['Macon'],
                        'Holcim' => ['Holcim'],
                        'Romcim' => ['Romcim'],
                        'Heidelberg' => ['Heidelberg'],
                        'Carpatcement' => ['Carpatcement'],
                        'Ferroli' => ['Ferroli'],
                        'Ariston' => ['Ariston'],
                        'Vaillant' => ['Vaillant'],
                        'Viessmann' => ['Viessmann'],
                        'Motan' => ['Motan'],
                        'Immergas' => ['Immergas'],
                        'Buderus' => ['Buderus'],
                        'Bosch' => ['Bosch'],
                        'Makita' => ['Makita'],
                        'DeWalt' => ['DeWalt'],
                        'Milwaukee' => ['Milwaukee'],
                        'Hikoki' => ['Hikoki'],
                        'Hitachi' => ['Hitachi'],
                        'Stanley' => ['Stanley'],
                        'Unior' => ['Unior'],
                        'Yato' => ['Yato'],
                        'Topmaster' => ['Topmaster'],
                        'Raider' => ['Raider'],
                        'Total' => ['Total'],
                        'Ingco' => ['Ingco'],
                        'Stern' => ['Stern'],
                        'Panther' => ['Panther'],
                        'Dedra' => ['Dedra'],
                        'Vorel' => ['Vorel'],
                        'Flo' => ['Flo'],
                        'Lund' => ['Lund'],
                        'Ferm' => ['Ferm'],
                        'Einhell' => ['Einhell'],
                        'Black+Decker' => ['Black+Decker', 'Black & Decker'],
                        'Skil' => ['Skil'],
                        'Metabo' => ['Metabo'],
                        'Dremel' => ['Dremel'],
                        'Karcher' => ['Karcher'],
                        'Nilfisk' => ['Nilfisk'],
                        'Stihl' => ['Stihl'],
                        'Husqvarna' => ['Husqvarna'],
                        'Ruris' => ['Ruris'],
                        'O-Mac' => ['O-Mac'],
                        'Agropro' => ['Agropro'],
                        'Micul Fermier' => ['Micul Fermier'],
                        'Alpin' => ['Alpin'],
                        'Blade' => ['Blade'],
                        'Campion' => ['Campion'],
                        'Elefant' => ['Elefant'],
                        'Pandora' => ['Pandora'],
                        'Procraft' => ['Procraft'],
                        'Worcraft' => ['Worcraft'],
                        'Detoolz' => ['Detoolz'],
                        'Tolsen' => ['Tolsen'],
                        'Heco' => ['Heco'],
                        'Fischer' => ['Fischer'],
                        'Wurth' => ['Wurth'],
                        'Spax' => ['Spax'],
                        'Holzsurub' => ['Holzsurub'], // Generic but maybe useful? No.
                    ];

                    foreach ($knownBrands as $brand => $keywords) {
                        foreach ($keywords as $keyword) {
                            if (stripos($name, $keyword) !== false) {
                                $brandName = $brand;
                                break 2;
                            }
                        }
                    }
                }

                if ($brandName) {
                    $brand = \App\Models\Brand::firstOrCreate(
                        ['name' => $brandName],
                        ['slug' => Str::slug($brandName), 'is_published' => true]
                    );
                    $product->brand_id = $brand->id;
                }
                
                // Type
                if ($tip === 'variable') {
                    $product->type = 'variable';
                } else {
                    $product->type = 'simple';
                }

                $product->save();
                
                if ($itemId) {
                    $parentIdMap[$itemId] = $product->id;
                }

                // Images for Product
                $this->processImages($product, $get('Imagine_principala'), $get('Galerie_imagini'));

                $updated++;
                $count++;
            }
            
            // Pass 2: Variations
            $this->info("Pass 2: Importing Variations...");
            rewind($handle);
            fgetcsv($handle); // skip header
            
            while (($row = fgetcsv($handle)) !== false) {
                 // Helper to get value by name
                $get = function($name) use ($headerMap, $row, $headers) {
                    $key = strtolower($name);
                    if (isset($headerMap[$key])) return $row[$headerMap[$key]];
                    foreach ($headerMap as $h => $i) {
                        if (str_contains($h, $name)) return $row[$i];
                    }
                    return null;
                };

                $tip = strtolower($get('Tip') ?? 'simple');
                if ($tip !== 'variation') continue;

                $parentId = $get('parent_id');
                if (!$parentId || !isset($parentIdMap[$parentId])) {
                    // Try cod_parinte if parent_id missing?
                    // Assuming feed is consistent.
                    continue;
                }

                $parentProductId = $parentIdMap[$parentId];
                $sku = $row[$headerMap['cod_produs']] ?? null;
                
                // Create Variation
                // Check if exists
                $variant = ProductVariant::where('sku', $sku)->first();
                if (!$variant) {
                    $variant = new ProductVariant();
                    $variant->sku = $sku;
                    $variant->product_id = $parentProductId;
                }
                
                $name = $row[$headerMap['titlu']] ?? null;
                if ($name) $variant->name = $name;
                
                // Variant Price
                $price = $row[$headerMap['pret_regular']] ?? 0;
                $variant->list_price = (float)$price;
                
                $promoPrice = $row[$headerMap['pret_promo']] ?? null;
                if ($promoPrice && $promoPrice > 0) {
                    $variant->price_override = (float)$promoPrice;
                }
                
                $stock = $row[$headerMap['stoc_cantitate']] ?? 0;
                $variant->stock_qty = (float)$stock;
                $variant->stock_status = ((float)$stock > 0) ? 'in_stock' : 'out_of_stock';
                
                $variant->save();
                
                // Images for Variation -> Add to Parent's Gallery?
                // Or if user insists on "Variation Image", maybe just add to parent for now.
                // We'll add it to parent's gallery but maybe we can't link it to variant directly.
                $varImg = $get('Imagine_variatie') ?? $get('Imagine_principala');
                if ($varImg) {
                    $this->downloadAndAttachImage($parentProductId, $varImg, false);
                }
            }

            DB::commit();
            $this->info("Imported/Updated $updated products and variants.");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error during import: " . $e->getMessage());
            $this->error("Row: " . json_encode($row ?? 'N/A'));
        } finally {
            fclose($handle);
            unlink($csvPath);
        }

        return 0;
    }

    private function processImages($product, $mainImg, $gallery)
    {
        if ($mainImg) {
            $this->downloadAndAttachImage($product->id, $mainImg, true);
        }

        if ($gallery) {
            $urls = preg_split('/[,|]/', $gallery);
            foreach ($urls as $idx => $url) {
                $url = trim($url);
                if (!$url) continue;
                if ($url === $mainImg) continue;

                $this->downloadAndAttachImage($product->id, $url, false, $idx + 1);
            }
        }
    }

    private function downloadAndAttachImage($productId, $url, $isMain, $sortOrder = 0)
    {
        try {
            // 1. Download Image
            $contents = file_get_contents($url);
            if (!$contents) return;

            // 2. Generate Filename
            $info = pathinfo($url);
            $ext = $info['extension'] ?? 'jpg';
            // Clean filename
            $filename = Str::slug($info['filename']) . '.' . $ext;
            $path = 'products/' . $filename;
            
            // Avoid overwriting if different content? 
            // For now, just overwrite or skip if exists.
            // User said "download to us".
            Storage::disk('public')->put($path, $contents);

            // 3. Update DB
            // Local path relative to storage/app/public
            // Frontend usually expects full URL or path relative to storage root.
            // If using `Storage::url($path)`, it returns `/storage/products/...`
            
            // Let's store the relative path for flexibility, or the full public URL?
            // Existing code used URL.
            // If we store `products/foo.jpg`, we need an accessor.
            // Let's store the accessible URL path: `/storage/products/foo.jpg`
            
            $publicPath = '/storage/' . $path;

            ProductImage::updateOrCreate(
                [
                    'product_id' => $productId,
                    'path' => $publicPath // check if this matches what we want
                ],
                [
                    'is_main' => $isMain,
                    'sort_order' => $sortOrder
                ]
            );

        } catch (\Exception $e) {
            $this->warn("Failed to download image $url: " . $e->getMessage());
        }
    }
}
