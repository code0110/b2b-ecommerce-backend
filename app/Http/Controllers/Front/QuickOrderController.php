<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Customer;
use App\Services\Pricing\PromotionPricingService;

use App\Models\ProductVariant;

class QuickOrderController extends Controller
{
    protected PromotionPricingService $promotionPricingService;

    public function __construct(PromotionPricingService $promotionPricingService)
    {
        $this->promotionPricingService = $promotionPricingService;
    }

    public function search(Request $request)
    {
        $q = trim((string) ($request->get('q') ?: $request->get('search', '')));
        $categoryId = $request->get('category_id');
        $sort = $request->get('sort', 'relevance');
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 24);

        $customer = optional($request->user())->customer;

        // Allow agents to impersonate/view as customer
        if ($request->has('customer_id') && $request->user() && $request->user()->hasRole(['sales_agent', 'sales_director', 'admin'])) {
            $customer = Customer::find($request->input('customer_id'));
        }

        $results = collect();
        $addedKeys = []; // Track unique items: "P{id}_V{vid}" or "P{id}_V"

        // Limit for DB queries - we fetch more to allow in-memory filtering/sorting/pagination
        $dbLimit = 200; 

        // 1. Search Products (Parents)
        $productsQuery = Product::query()
            ->with(['variants', 'mainCategory', 'brand'])
            ->where('status', 'published');

        if ($categoryId) {
            $productsQuery->where('main_category_id', $categoryId);
        }

        if ($q !== '') {
            $productsQuery->where(function ($qBuilder) use ($q) {
                $qBuilder->where('name', 'like', "%{$q}%")
                         ->orWhere('internal_code', 'like', "%{$q}%")
                         ->orWhere('barcode', 'like', "%{$q}%");
            });
        }

        $products = $productsQuery->limit($dbLimit)->get();

        foreach ($products as $product) {
            // If product has variants, expand them into individual items
            if ($product->variants->isNotEmpty()) {
                foreach ($product->variants as $variant) {
                    $key = "P{$product->id}_V{$variant->id}";
                    if (!in_array($key, $addedKeys)) {
                        $results->push($this->promotionPricingService->formatProductForFrontend($product, $customer, $variant));
                        $addedKeys[] = $key;
                    }
                }
            } else {
                // Standalone product
                $key = "P{$product->id}_V";
                if (!in_array($key, $addedKeys)) {
                    $results->push($this->promotionPricingService->formatProductForFrontend($product, $customer));
                    $addedKeys[] = $key;
                }
            }
        }

        // 2. Search Variants directly (only if we have a search query, otherwise product query by category covers it)
        if ($q !== '') {
            $variantsQuery = ProductVariant::query()
                ->with(['product.mainCategory', 'product.brand'])
                ->where(function ($qBuilder) use ($q) {
                    $qBuilder->where('name', 'like', "%{$q}%")
                             ->orWhere('sku', 'like', "%{$q}%")
                             ->orWhere('barcode', 'like', "%{$q}%");
                });
            
            if ($categoryId) {
                $variantsQuery->whereHas('product', function($q) use ($categoryId) {
                    $q->where('main_category_id', $categoryId);
                });
            }

            $variants = $variantsQuery->limit($dbLimit)->get();

            foreach ($variants as $variant) {
                if ($variant->product && $variant->product->status === 'published') {
                    $product = $variant->product;
                    $key = "P{$product->id}_V{$variant->id}";
                    
                    if (!in_array($key, $addedKeys)) {
                        $results->push($this->promotionPricingService->formatProductForFrontend($product, $customer, $variant));
                        $addedKeys[] = $key;
                    }
                }
            }
        }

        // 3. Sort
        if ($sort === 'price_asc') {
            $results = $results->sortBy('price');
        } elseif ($sort === 'price_desc') {
            $results = $results->sortByDesc('price');
        } elseif ($sort === 'name_asc') {
            $results = $results->sortBy('name');
        }
        // 'relevance' is default order of insertion (Products then Variants)

        // 4. Paginate (In-Memory)
        $total = $results->count();
        $paginatedResults = $results->forPage($page, $perPage)->values();
        $lastPage = max(1, ceil($total / $perPage));

        return response()->json([
            'data' => $paginatedResults,
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total
        ]);
    }

    private function resolveCart(Request $request): Cart
    {
        // User logat: încercăm întâi via sanctum, apoi via sesiunea web
        $user = $request->user('sanctum') ?? $request->user();

        if ($user) {
            // Check for explicit customer context (e.g. Agent acting on behalf of Customer)
            // Header takes precedence, then query param, then user's own customer_id
            $customerId = $request->header('X-Customer-ID') ?? $request->input('customer_id') ?? $user->customer_id;

            if ($customerId) {
                // Shared Cart Logic: Find any active cart for this customer
                $cart = Cart::where('customer_id', $customerId)
                            ->where('status', 'active')
                            ->latest()
                            ->first();

                if (!$cart) {
                    $cart = Cart::create([
                        'user_id'     => $user->id,
                        'customer_id' => $customerId,
                        'status'      => 'active',
                    ]);
                }

                return $cart;
            }

            return Cart::firstOrCreate([
                'user_id' => $user->id,
                'customer_id' => null, // No customer context
                'status'  => 'active',
            ]);
        }

        // Guest: folosim ID-ul de sesiune Laravel (middleware web)
        $sessionId = $request->session()->getId();

        return Cart::firstOrCreate([
            'session_id' => $sessionId,
            'status'     => 'active',
        ]);
    }

    protected function respondWithEnrichedCart(Cart $cart, Request $request)
    {
        $cart->loadMissing(['items.product.images']);

        $user     = $request->user();
        // If cart has a specific customer_id, use it. Otherwise fall back to user's customer.
        $customer = null;
        if ($cart->customer_id) {
            $customer = Customer::find($cart->customer_id);
        } elseif ($user) {
            $customer = $user->customer;
        }

        $pricing = $this->promotionPricingService->priceCart($cart, $customer);

        return response()->json([
            'id'             => $cart->id,
            'items'          => $pricing['items'],
            'subtotal'       => $pricing['subtotal'],
            'discount_total' => $pricing['discount_total'],
            'total'          => $pricing['total'],
        ]);
    }

    public function addToCart(Request $request)
    {
        $data = $request->validate([
            'items'           => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required_without:items.*.sku', 'integer', 'exists:products,id'],
            'items.*.sku'        => ['nullable', 'string'],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.01'],
            'items.*.unit'       => ['nullable', 'string'],
        ]);

        return DB::transaction(function () use ($request, $data) {
            $cart = $this->resolveCart($request);

            foreach ($data['items'] as $row) {
                $product = null;
                $variant = null;

                if (!empty($row['product_id'])) {
                    $product = Product::findOrFail($row['product_id']);
                } elseif (!empty($row['sku'])) {
                    // Try to find variant first
                    $variant = \App\Models\ProductVariant::where('sku', $row['sku'])->first();
                    if ($variant) {
                        $product = $variant->product;
                    } else {
                        // Fallback to main product
                        $product = Product::where('internal_code', $row['sku'])
                            ->orWhere('barcode', $row['sku'])
                            ->firstOrFail();
                    }
                }

                $quantity = (float) $row['quantity'];
                if ($quantity <= 0) {
                    continue;
                }

                // Determine base price and variant ID
                $variantId = $variant ? $variant->id : null;
                $basePrice = $variant ? ($variant->price_override ?? $variant->list_price) : ($product->price_override ?? $product->list_price ?? 0);

                // Determine Unit and Conversion Factor
                $unitName = $row['unit'] ?? 'buc';
                $conversionFactor = 1;

                // Find unit definition
                $units = \App\Models\ProductUnit::where('product_id', $product->id)
                    ->where(function($q) use ($unitName) {
                         $q->where('unit', $unitName)
                           ->orWhere('name', $unitName);
                    })
                    ->get();

                $productUnit = null;
                if ($variant) {
                    $productUnit = $units->where('product_variant_id', $variant->id)->first();
                }
                if (!$productUnit) {
                    $productUnit = $units->whereNull('product_variant_id')->first();
                }

                if ($productUnit) {
                     $conversionFactor = (float) $productUnit->conversion_factor;
                     if ($conversionFactor <= 0) $conversionFactor = 1;
                }

                $unitPrice = $basePrice * $conversionFactor;

                $item = CartItem::firstOrNew([
                    'cart_id'          => $cart->id,
                    'product_id'       => $product->id,
                    'product_variant_id' => $variantId,
                    'unit'             => $unitName,
                ]);

                $item->quantity = $item->exists ? $item->quantity + $quantity : $quantity;
                $item->unit_price = $unitPrice;
                $item->total = $item->quantity * $unitPrice;
                $item->save();
            }
            
            $cart->refresh();

            return $this->respondWithEnrichedCart($cart, $request);
        });
    }
}
