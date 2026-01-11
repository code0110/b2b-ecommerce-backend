<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\RelatedProduct;
use App\Models\ProductDocument;
use App\Services\SeoGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Listă produse cu filtre pentru grid-ul din admin.
     */
    public function index(Request $request)
    {
        $query = Product::query()
            ->with([
                'mainCategory:id,name,slug',
                'brand:id,name,slug',
                'images'
            ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        if ($user && $user->hasRole(['customer_b2b', 'customer_b2c'])) {
             $query->where('status', 'published');
        }

        // Căutare text (denumire, cod, barcode, ERP)
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('internal_code', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('erp_id', 'like', "%{$search}%");
            });
        }

        // Filtru categorie principală
        if ($mainCategoryId = $request->query('main_category_id')) {
            $query->where('main_category_id', $mainCategoryId);
        }

        // Filtru categorie (via pivot)
        if ($categoryId = $request->query('category_id')) {
            $ids = is_array($categoryId) ? $categoryId : [$categoryId];
            
            $query->where(function ($q) use ($ids) {
                $q->whereIn('main_category_id', $ids)
                    ->orWhereHas('categories', function ($sub) use ($ids) {
                        $sub->whereIn('id', $ids);
                    });
            });
        }

        // Filtru brand
        if ($brandId = $request->query('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        // Status publicare
        if ($status = $request->query('status')) {
            $query->where('status', $status); // ex: published / draft / hidden
        }

        // Stoc
        if ($stockStatus = $request->query('stock_status')) {
            $query->where('stock_status', $stockStatus); // in_stock / out_of_stock / limited / on_order
        }

        // Flag-uri
        if ($request->has('is_new') && $request->query('is_new') !== '') {
            $query->where('is_new', (bool) $request->query('is_new'));
        }

        if ($request->has('is_promo') && $request->query('is_promo') !== '') {
            $query->where('is_promo', (bool) $request->query('is_promo'));
        }

        if ($request->has('is_best_seller') && $request->query('is_best_seller') !== '') {
            $query->where('is_best_seller', (bool) $request->query('is_best_seller'));
        }

        // Interval preț
        if ($minPrice = $request->query('min_price')) {
            $query->where('list_price', '>=', (float) $minPrice);
        }

        if ($maxPrice = $request->query('max_price')) {
            $query->where('list_price', '<=', (float) $maxPrice);
        }

        // Sortare
        $sortBy  = $request->query('sort_by', 'created_at');
        $sortDir = $request->query('sort_dir', 'desc');

        if (!in_array($sortBy, ['name', 'created_at', 'list_price', 'stock_qty', 'sort_order'], true)) {
            $sortBy = 'created_at';
        }

        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $query->orderBy($sortBy, $sortDir);

        $perPage   = (int) $request->query('per_page', 20);
        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function (Product $product) {
            return $this->transformListProduct($product);
        });

        return response()->json($paginator);
    }

    /**
     * Generate SEO content (Title, Meta, Descriptions) based on product data.
     */
    public function generateSeo(Request $request, SeoGeneratorService $seoService)
    {
        // Validate minimal required data
        $data = $request->validate([
            'name' => 'required|string',
            'brand' => 'nullable|array',
            'main_category' => 'nullable|array',
            'attribute_values' => 'nullable|array',
            'list_price' => 'nullable|numeric',
            // Allow other fields to pass through if needed by the service
            'short_description' => 'nullable|string',
            'long_description' => 'nullable|string',
        ]);

        // We pass the raw request data (after validation) or just $request->all() 
        // if we want to be more flexible, but validation is safer.
        // However, the service expects array structure matching the frontend form.
        
        $result = $seoService->generate($request->all());

        return response()->json($result);
    }

    /**
     * Creare produs – cu categorii secundare, imagini, variante etc.
     */
    public function store(Request $request)
    {
        $data = $this->validateProduct($request);

        return DB::transaction(function () use ($data) {
            $product = new Product();

            $this->fillProduct($product, $data);
            $product->save();

            // Categorii secundare
            $this->syncCategories($product, $data);

            // Atribute (valori)
            $this->syncAttributeValues($product, $data);

            // Unități
            $this->syncUnits($product, $data);

            // Imagini
            $this->syncImages($product, $data);

            // Variante
            $this->syncVariants($product, $data);

            // Produse asociate
            $this->syncRelatedProducts($product, $data);

            // Documente
            $this->syncDocuments($product, $data);

            $product->load([
                'mainCategory:id,name,slug',
                'brand:id,name,slug',
                'categories:id,name,slug',
                'attributeValues.attribute', // Load attribute values with attribute definition
                'images',
                'variants',
                'related.related',
                'documents',
            ]);

            return response()->json($this->transformDetailProduct($product), 201);
        });
    }

    /**
     * Detaliu produs pentru formular (edit).
     */
    public function show(Product $product)
    {
        $product->load([
            'mainCategory:id,name,slug',
            'brand:id,name,slug',
            'categories:id,name,slug',
            'attributeValues.attribute', // Load attribute values with attribute definition
            'images'   => function ($q) {
                $q->orderBy('sort_order');
            },
            'variants' => function ($q) {
                $q->orderBy('id');
            },
            'related.related',
            'documents',
        ]);

        return response()->json($this->transformDetailProduct($product));
    }

    /**
     * Update produs + relații.
     */
    public function update(Request $request, Product $product)
    {
        $data = $this->validateProduct($request, $product->id);

        return DB::transaction(function () use ($data, $product) {
            $this->fillProduct($product, $data);
            $product->save();

            $this->syncCategories($product, $data);
            $this->syncAttributeValues($product, $data);
            $this->syncUnits($product, $data);
            $this->syncImages($product, $data);
            $this->syncVariants($product, $data);
            $this->syncRelatedProducts($product, $data);
            $this->syncDocuments($product, $data);

            $product->load([
                'mainCategory:id,name,slug',
                'brand:id,name,slug',
                'categories:id,name,slug',
                'attributeValues.attribute', // Load attribute values with attribute definition
                'images',
                'variants',
                'related.related',
                'documents',
            ]);

            return response()->json($this->transformDetailProduct($product));
        });
    }

    /**
     * Ștergere produs – soft sau hard; aici facem un delete simplu,
     * dar blocăm dacă există linii de comandă (dacă ai model OrderItem).
     */
    public function destroy(Product $product)
    {
        if ($product->orderItems()->exists()) {
            return response()->json([
                'message' => 'Produsul are comenzi asociate și nu poate fi șters.',
            ], 422);
        }

        $product->categories()->detach();
        $product->attributeValues()->delete();
        $product->images()->delete();
        $product->variants()->delete();
        $product->related()->delete();
        $product->documents()->delete();

        $product->delete();

        return response()->json(null, 204);
    }

    // ---------------------------------------------------------------------
    //  Helpers – validare, mapare, sync relații
    // ---------------------------------------------------------------------

    protected function validateProduct(Request $request, ?int $productId = null): array
    {
        return $request->validate([
            // Câmpuri generale
            'name'            => ['required', 'string', 'max:255'],
            'slug'            => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
            'internal_code'   => ['nullable', 'string', 'max:100'],
            'barcode'         => ['nullable', 'string', 'max:100'],
            'erp_id'          => ['nullable', 'string', 'max:100'],
            'short_description' => ['nullable', 'string'],
            'long_description'  => ['nullable', 'string'],
            'main_category_id'  => ['required', 'integer', 'exists:categories,id'],
            'brand_id'          => ['nullable', 'integer', 'exists:brands,id'],
            'status'            => ['required', 'string', 'in:published,draft,hidden'],
            'sort_order'        => ['nullable', 'integer'],

            // Prețuri
            'list_price'      => ['required', 'numeric', 'min:0'],
            'rrp_price'       => ['nullable', 'numeric', 'min:0'],
            'vat_rate'        => ['nullable', 'numeric', 'min:0'],
            'price_override'  => ['nullable', 'numeric', 'min:0'],

            // Stoc
            'stock_status'       => ['nullable', 'string', 'max:50'],
            'stock_qty'          => ['nullable', 'integer', 'min:0'],
            'supplier_stock_qty' => ['nullable', 'integer', 'min:0'],
            'lead_time_days'     => ['nullable', 'integer', 'min:0'],

            // Flag-uri
            'is_new'         => ['nullable', 'boolean'],
            'is_promo'       => ['nullable', 'boolean'],
            'is_best_seller' => ['nullable', 'boolean'],

            // Categorii secundare
            'category_ids'   => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],

            // Atribute
            'attribute_values' => ['nullable', 'array'],
            'attribute_values.*.attribute_id' => ['required', 'integer', 'exists:attributes,id'],
            'attribute_values.*.value' => ['nullable', 'string', 'max:191'],

            // Imagini
            'images'                  => ['nullable', 'array'],
            'images.*.id'             => ['nullable', 'integer'],
            'images.*.path'           => ['required_with:images', 'string', 'max:255'],
            'images.*.is_main'        => ['nullable', 'boolean'],
            'images.*.sort_order'     => ['nullable', 'integer'],

            // Unități de măsură (Product Level)
            'units'                     => ['nullable', 'array'],
            'units.*.id'                => ['nullable', 'integer'],
            'units.*.name'              => ['required_with:units', 'string', 'max:50'],
            'units.*.unit'              => ['required_with:units', 'string', 'max:20'],
            'units.*.conversion_factor' => ['required_with:units', 'numeric', 'min:0'],
            'units.*.is_base'           => ['boolean'],

            // Variante
            'variants'                        => ['nullable', 'array'],
            'variants.*.id'                   => ['nullable', 'integer'],
            'variants.*.sku'                  => ['required_with:variants', 'string', 'max:100'],
            'variants.*.barcode'              => ['nullable', 'string', 'max:100'],
            'variants.*.erp_id'               => ['nullable', 'string', 'max:100'],
            'variants.*.price'                => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock_qty'            => ['nullable', 'integer', 'min:0'],
            'variants.*.slug'                 => ['nullable', 'string', 'max:255'],
            'variants.*.attributes'           => ['nullable', 'array'], // ex: {color: 'red', size: 'XL'}
            'variants.*.units'                => ['nullable', 'array'],
            'variants.*.units.*.name'         => ['required_with:variants.*.units', 'string', 'max:50'],
            'variants.*.units.*.unit'         => ['required_with:variants.*.units', 'string', 'max:20'],
            'variants.*.units.*.conversion_factor' => ['required_with:variants.*.units', 'numeric', 'min:0'],

            // Produse asociate
            'related_products'                => ['nullable', 'array'],
            'related_products.*.related_id'   => ['required_with:related_products', 'integer', 'exists:products,id'],
            'related_products.*.type'         => ['nullable', 'string', 'in:similar,cross_sell,up_sell'],

            // Documente
            'documents'                       => ['nullable', 'array'],
            'documents.*.id'                  => ['nullable', 'integer'],
            'documents.*.path'                => ['required_with:documents', 'string', 'max:255'],
            'documents.*.type'                => ['nullable', 'string', 'max:50'], // ex: spec_sheet, manual, certificate
            'documents.*.visibility'          => ['nullable', 'string', 'in:public,customers_only,by_request'],
        ]);
    }

    protected function fillProduct(Product $product, array $data): void
    {
        $product->name             = $data['name'];
        $product->slug             = $data['slug'];
        $product->internal_code    = $data['internal_code'] ?? null;
        $product->barcode          = $data['barcode'] ?? null;
        $product->erp_id           = $data['erp_id'] ?? null;
        $product->short_description = $data['short_description'] ?? null;
        $product->long_description  = $data['long_description'] ?? null;
        $product->main_category_id  = $data['main_category_id'];
        $product->brand_id          = $data['brand_id'] ?? null;
        $product->status            = $data['status'];
        $product->sort_order        = $data['sort_order'] ?? 0;

        $product->list_price     = $data['list_price'];
        $product->rrp_price      = $data['rrp_price'] ?? null;
        $product->vat_rate       = $data['vat_rate'] ?? null;
        $product->price_override = $data['price_override'] ?? null;

        $product->stock_status       = $data['stock_status'] ?? null;
        $product->stock_qty          = $data['stock_qty'] ?? 0;
        $product->supplier_stock_qty = $data['supplier_stock_qty'] ?? 0;
        $product->lead_time_days     = $data['lead_time_days'] ?? null;

        $product->is_new         = $data['is_new'] ?? false;
        $product->is_promo       = $data['is_promo'] ?? false;
        $product->is_best_seller = $data['is_best_seller'] ?? false;
    }

    protected function syncCategories(Product $product, array $data): void
    {
        $categoryIds = $data['category_ids'] ?? [];
        $product->categories()->sync($categoryIds);
    }

    protected function syncAttributeValues(Product $product, array $data): void
    {
        if (!array_key_exists('attribute_values', $data)) {
            return;
        }

        $product->attributeValues()->delete();

        foreach ($data['attribute_values'] as $attr) {
            $product->attributeValues()->create([
                'attribute_id' => $attr['attribute_id'],
                'value'        => $attr['value'],
            ]);
        }
    }

    protected function syncImages(Product $product, array $data): void
    {
        if (!array_key_exists('images', $data)) {
            return;
        }

        $product->images()->delete();

        foreach ($data['images'] as $img) {
            $product->images()->create([
                'path'       => $img['path'],
                'is_main'    => $img['is_main'] ?? false,
                'sort_order' => $img['sort_order'] ?? 0,
            ]);
        }
    }

    protected function syncUnits(Product $product, array $data): void
    {
        // Ștergem unitățile existente ale produsului (cele care nu sunt legate de variante)
        $product->units()->whereNull('product_variant_id')->delete();

        if (!array_key_exists('units', $data)) {
            return;
        }

        foreach ($data['units'] as $u) {
            $product->units()->create([
                'name'              => $u['name'],
                'unit'              => $u['unit'],
                'conversion_factor' => $u['conversion_factor'],
                'is_base'           => $u['is_base'] ?? false,
                'is_default'        => $u['is_default'] ?? false,
            ]);
        }
    }

    protected function syncVariants(Product $product, array $data): void
    {
        if (!array_key_exists('variants', $data)) {
            return;
        }

        // Ștergem variantele existente (cascade delete ar trebui să șteargă și unitățile variantelor)
        // Dar pentru siguranță, putem șterge manual dacă e nevoie.
        // Presupunem că ștergerea variantei șterge și unitățile ei (FK constraint).
        $product->variants()->delete();

        foreach ($data['variants'] as $variantData) {
            /** @var \App\Models\ProductVariant $variant */
            $variant = $product->variants()->create([
                'sku'        => $variantData['sku'],
                'barcode'    => $variantData['barcode'] ?? null,
                'erp_id'     => $variantData['erp_id'] ?? null,
                'price'      => $variantData['price'] ?? null,
                'stock_qty'  => $variantData['stock_qty'] ?? 0,
                'slug'       => $variantData['slug'] ?? null,
                // 'attributes' => ... nu salvăm aici direct dacă e relație
            ]);

            // Sync Variant Attributes
            if (!empty($variantData['attributes'])) {
                 // Presupunem că vine un array de genul [['attribute_id' => 1, 'value' => 'Rosu'], ...]
                 // Sau poate vine sub formă de obiect cheie-valoare? 
                 // Validarea din validateProduct zice 'variants.*.attributes' => ['nullable', 'array']
                 // Să zicem că vine array de obiecte pentru consistență cu produsul
                 foreach ($variantData['attributes'] as $attr) {
                     // Check format. If simple key-value, adapt.
                     // If standard structure:
                     if (isset($attr['attribute_id'])) {
                         $variant->attributes()->create([
                             'product_id' => $product->id, // Legăm și de produs? AttributeValue are product_id.
                             'attribute_id' => $attr['attribute_id'],
                             'value' => $attr['value'],
                         ]);
                     }
                 }
            }

            // Sync Variant Units
            if (!empty($variantData['units'])) {
                foreach ($variantData['units'] as $u) {
                    $variant->units()->create([
                        'product_id'        => $product->id, // Legăm și de produs
                        'name'              => $u['name'],
                        'unit'              => $u['unit'],
                        'conversion_factor' => $u['conversion_factor'],
                        'is_base'           => $u['is_base'] ?? false,
                        'is_default'        => $u['is_default'] ?? false,
                    ]);
                }
            }
        }
    }

    protected function syncRelatedProducts(Product $product, array $data): void
    {
        if (!array_key_exists('related_products', $data)) {
            return;
        }

        $product->related()->delete();

        foreach ($data['related_products'] as $rel) {
            $product->related()->create([
                'related_product_id' => $rel['related_id'],
                'type'               => $rel['type'] ?? 'similar',
            ]);
        }
    }

    protected function syncDocuments(Product $product, array $data): void
    {
        if (!array_key_exists('documents', $data)) {
            return;
        }

        $product->documents()->delete();

        foreach ($data['documents'] as $doc) {
            $product->documents()->create([
                'path'       => $doc['path'],
                'type'       => $doc['type'] ?? null,
                'visibility' => $doc['visibility'] ?? 'public',
            ]);
        }
    }

    // ---------------------------------------------------------------------
    //  Transformers – listă vs detaliu
    // ---------------------------------------------------------------------

    protected function transformListProduct(Product $product): array
    {
        return [
            'id'          => $product->id,
            'name'        => $product->name,
            'slug'        => $product->slug,
            'internal_code' => $product->internal_code,
            'stock_status' => $product->stock_status,
            'stock_qty'    => $product->stock_qty,
            'list_price'   => (float) $product->list_price,
            'is_new'       => (bool) $product->is_new,
            'is_promo'     => (bool) $product->is_promo,
            'is_best_seller' => (bool) $product->is_best_seller,
            'status'       => $product->status,
            'main_image_url' => $product->main_image_url, // Added this line
            'main_category' => $product->mainCategory ? [
                'id'   => $product->mainCategory->id,
                'name' => $product->mainCategory->name,
                'slug' => $product->mainCategory->slug,
            ] : null,
            'brand'        => $product->brand ? [
                'id'   => $product->brand->id,
                'name' => $product->brand->name,
                'slug' => $product->brand->slug,
            ] : null,
            'created_at'   => optional($product->created_at)->toDateTimeString(),
        ];
    }

    protected function transformDetailProduct(Product $product): array
    {
        return [
            'id'               => $product->id,
            'name'             => $product->name,
            'slug'             => $product->slug,
            'internal_code'    => $product->internal_code,
            'barcode'          => $product->barcode,
            'erp_id'           => $product->erp_id,
            'short_description'=> $product->short_description,
            'long_description' => $product->long_description,
            'main_category_id' => $product->main_category_id,
            'brand_id'         => $product->brand_id,
            'status'           => $product->status,
            'sort_order'       => $product->sort_order,
            'list_price'       => (float) $product->list_price,
            'rrp_price'        => $product->rrp_price !== null ? (float) $product->rrp_price : null,
            'vat_rate'         => $product->vat_rate !== null ? (float) $product->vat_rate : null,
            'price_override'   => $product->price_override !== null ? (float) $product->price_override : null,
            'stock_status'       => $product->stock_status,
            'stock_qty'          => (int) $product->stock_qty,
            'supplier_stock_qty' => (int) $product->supplier_stock_qty,
            'lead_time_days'     => $product->lead_time_days,
            'is_new'           => (bool) $product->is_new,
            'is_promo'         => (bool) $product->is_promo,
            'is_best_seller'   => (bool) $product->is_best_seller,

            'main_category'    => $product->mainCategory ? [
                'id'   => $product->mainCategory->id,
                'name' => $product->mainCategory->name,
                'slug' => $product->mainCategory->slug,
            ] : null,

            'brand'            => $product->brand ? [
                'id'   => $product->brand->id,
                'name' => $product->brand->name,
                'slug' => $product->brand->slug,
            ] : null,

            'category_ids'     => $product->categories->pluck('id')->values(),

            'attribute_values' => $product->attributeValues->map(function ($attrVal) {
                return [
                    'id'           => $attrVal->id,
                    'attribute_id' => $attrVal->attribute_id,
                    'value'        => $attrVal->value,
                    'attribute'    => $attrVal->attribute ? [
                        'id'   => $attrVal->attribute->id,
                        'name' => $attrVal->attribute->name,
                        'type' => $attrVal->attribute->type,
                        'slug' => $attrVal->attribute->slug,
                    ] : null,
                ];
            })->values(),

            'images'           => $product->images->map(function (ProductImage $img) {
                return [
                    'id'         => $img->id,
                    'path'       => $img->path,
                    'is_main'    => (bool) $img->is_main,
                    'sort_order' => (int) $img->sort_order,
                ];
            })->values(),

            'variants'         => $product->variants->map(function (ProductVariant $variant) {
                return [
                    'id'        => $variant->id,
                    'sku'       => $variant->sku,
                    'barcode'   => $variant->barcode,
                    'erp_id'    => $variant->erp_id,
                    'price'     => $variant->price !== null ? (float) $variant->price : null,
                    'stock_qty' => (int) $variant->stock_qty,
                    'slug'      => $variant->slug,
                    'attributes'=> $variant->attributes ?? [],
                ];
            })->values(),

            'related_products' => $product->related->map(function (RelatedProduct $rel) {
                return [
                    'id'         => $rel->id,
                    'related_id' => $rel->related_product_id,
                    'type'       => $rel->type,
                    'related'    => $rel->related ? [
                        'id'   => $rel->related->id,
                        'name' => $rel->related->name,
                        'slug' => $rel->related->slug,
                    ] : null,
                ];
            })->values(),

            'documents'        => $product->documents->map(function (ProductDocument $doc) {
                return [
                    'id'         => $doc->id,
                    'path'       => $doc->path,
                    'type'       => $doc->type,
                    'visibility' => $doc->visibility,
                ];
            })->values(),

            'created_at'       => optional($product->created_at)->toDateTimeString(),
            'updated_at'       => optional($product->updated_at)->toDateTimeString(),
        ];
    }
}
