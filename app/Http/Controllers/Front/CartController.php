<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use App\Services\Pricing\PromotionPricingService;
use App\Services\Pricing\PromotionEngine;
use App\Models\Customer;

class CartController extends Controller
{

    protected PromotionEngine $promotionEngine;
    protected PromotionPricingService $promotionPricingService;

    public function __construct(PromotionEngine $promotionEngine, PromotionPricingService $promotionPricingService)
    {
        $this->promotionEngine = $promotionEngine;
        $this->promotionPricingService = $promotionPricingService;
    }
    /**
     * Coș-ul este legat de:
     *  - user_id (dacă e logat via Sanctum sau sesiune web)
     *  - session_id (dacă e guest, folosind sesiunea Laravel)
     */
    protected function resolveCart(Request $request): Cart
    {
        // Debugging
        \Illuminate\Support\Facades\Log::info('ResolveCart Start', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'headers_x_customer' => $request->header('X-Customer-ID'),
            'user_sanctum_id' => $request->user('sanctum')?->id,
            'user_default_id' => $request->user()?->id,
        ]);

        // User logat: încercăm întâi via sanctum, apoi via sesiunea web
        $user = $request->user('sanctum') ?? $request->user();

        if ($user) {
            // Check for explicit customer context (e.g. Agent acting on behalf of Customer)
            // Header takes precedence, then query param, then user's own customer_id
            $customerId = $request->header('X-Customer-ID') ?? $request->input('customer_id') ?? $user->customer_id;

            if ($customerId) {
                // Shared Cart Logic: Find any active cart for this customer
                // This ensures Admin (QuickOrder) and Customer (Front) see the same cart
                $cart = Cart::where('customer_id', $customerId)
                            ->where('status', 'active')
                            ->latest()
                            ->first();

                if (!$cart) {
                    \Illuminate\Support\Facades\Log::info('ResolveCart: Creating new cart for customer', ['customer_id' => $customerId]);
                    $cart = Cart::create([
                        'user_id'     => $user->id,
                        'customer_id' => $customerId,
                        'status'      => 'active',
                    ]);
                } else {
                     \Illuminate\Support\Facades\Log::info('ResolveCart: Found existing cart', ['cart_id' => $cart->id]);
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

    /**
     * Transformă modelul Cart într-o structură simplă pentru frontend.
     */
    protected function transformCart(Cart $cart): array
    {
        $cart->loadMissing(['items.product', 'items.variant']);

        $items = $cart->items->map(function (CartItem $item) {
            $product = $item->product;
            $variant = $item->variant;

            $lineTotal = $item->total ?? ($item->unit_price * $item->quantity);

            return [
                'id'                 => $item->id,
                'product_id'         => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'quantity'           => $item->quantity,
                'unit_price'         => (float) $item->unit_price,
                'total'              => (float) $lineTotal,
                'product'            => $product,
                'variant'            => $variant,
            ];
        });

        $subtotal = $items->sum('total');

        return [
            'id'       => $cart->id,
            'items'    => $items,
            'subtotal' => (float) $subtotal,
            'total'    => (float) $subtotal, // pentru moment = subtotal
        ];
    }

    protected function respondWithEnrichedCart(Cart $cart, Request $request)
    {
        \Illuminate\Support\Facades\Log::info('respondWithEnrichedCart: Start', ['cart_id' => $cart->id]);

        $user     = $request->user();
        // If cart has a specific customer_id, use it. Otherwise fall back to user's customer.
        $customer = null;
        if ($cart->customer_id) {
            $customer = Customer::find($cart->customer_id);
        } elseif ($user) {
            $customer = $user->customer;
        }

        \Illuminate\Support\Facades\Log::info('respondWithEnrichedCart: Pricing cart', ['customer_id' => $customer?->id]);
        $pricing = $this->promotionPricingService->priceCart($cart, $customer);
        \Illuminate\Support\Facades\Log::info('respondWithEnrichedCart: Pricing done');

        return response()->json([
            'id'             => $cart->id,
            'items'          => $pricing['items'],
            'subtotal'       => $pricing['subtotal'],
            'discount_total' => $pricing['discount_total'],
            'total'          => $pricing['total'],
        ]);
    }

    /**
     * GET /api/cart
     */
    public function show(Request $request)
    {
        $cart = $this->resolveCart($request);
        return $this->respondWithEnrichedCart($cart, $request);
    }



    /**
     * POST /api/cart/items
     * body: { product_id, product_variant_id?, quantity }
     */
    public function addItem(Request $request)
    {
        $data = $request->validate([
            'product_id'         => ['required', 'integer', 'exists:products,id'],
            'product_variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity'           => ['required', 'integer', 'min:1'],
        ]);

        $cart = $this->resolveCart($request);

        $product = Product::findOrFail($data['product_id']);
        $variant = null;

        if (!empty($data['product_variant_id'])) {
            $variant = ProductVariant::findOrFail($data['product_variant_id']);
        }

        // Stabilim prețul (fallback pe list_price)
        $unitPrice = 0;

        if ($variant) {
            $unitPrice = $variant->price_override ?? $variant->list_price ?? 0;
        } else {
            $unitPrice = $product->price_override ?? $product->list_price ?? 0;
        }

        // Item existent cu același produs / variantă
        $item = $cart->items()
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variant ? $variant->id : null)
            ->first();

        if ($item) {
            $item->quantity += $data['quantity'];
            $item->unit_price = $unitPrice;
        } else {
            $item = new CartItem([
                'product_id'         => $product->id,
                'product_variant_id' => $variant ? $variant->id : null,
                'quantity'           => $data['quantity'],
                'unit_price'         => $unitPrice,
            ]);
            $cart->items()->save($item);
        }

        $item->total = $item->unit_price * $item->quantity;
        $item->save();

        $cart->refresh();

        return $this->respondWithEnrichedCart($cart, $request);
    }

    /**
     * PUT /api/cart/items/{item}
     */
    public function updateItem($itemId, Request $request)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $item = CartItem::findOrFail($itemId);

        $item->quantity = $data['quantity'];
        $item->total    = $item->unit_price * $item->quantity;
        $item->save();

        $cart = $item->cart;

        return $this->respondWithEnrichedCart($cart, $request);
    }

    /**
     * DELETE /api/cart/items/{item}
     */
    public function removeItem($itemId, Request $request)
    {
        $item = CartItem::findOrFail($itemId);
        $cart = $item->cart;

        $item->delete();

        $cart->refresh();

        return $this->respondWithEnrichedCart($cart, $request);
    }

    /**
     * POST /api/cart/promotions/{id}
     * Add all products from a promotion to the cart.
     */
    public function addPromotion($id, Request $request)
    {
        $promotion = \App\Models\Promotion::findOrFail($id);
        
        // Validate Promotion
        if ($promotion->status !== 'active') {
            return response()->json(['message' => 'Această promoție nu este activă.'], 400);
        }

        $now = now();
        if ($promotion->start_at && $now->lt($promotion->start_at)) {
             return response()->json(['message' => 'Această promoție nu a început încă.'], 400);
        }
        if ($promotion->end_at && $now->gt($promotion->end_at)) {
             return response()->json(['message' => 'Această promoție a expirat.'], 400);
        }

        // Get all products associated with this promotion
        $products = $promotion->products;

        if ($products->isEmpty()) {
            // Check if it's a coupon-based promotion
            if ($promotion->code) {
                // Delegate to applyCoupon logic (manually since request might not match)
                $request->merge(['code' => $promotion->code]);
                return $this->applyCoupon($request);
            }
            
            // If it's a global rule without products (e.g. "10% Off Everything"), 
            // checking it here implies the user wants to "activate" it.
            // We attach it to the cart so it persists and the engine knows it's manually selected.
            
            $cart = $this->resolveCart($request);
            $cart->promotions()->syncWithoutDetaching([$promotion->id]);

            $cart->refresh(); // Refresh to ensure totals are up to date
            
            return $this->respondWithEnrichedCart($cart, $request);
        }

        $cart = $this->resolveCart($request);
        
        // Determine quantity to add (default 1 or min_qty_per_product)
        $qtyToAdd = $promotion->min_qty_per_product > 0 ? $promotion->min_qty_per_product : 1;

        foreach ($products as $product) {
            // Check if item exists
            $item = $cart->items()
                ->where('product_id', $product->id)
                ->first();

            $unitPrice = $product->price_override ?? $product->list_price ?? 0;

            if ($item) {
                $item->quantity += $qtyToAdd;
                $item->save();
            } else {
                $item = new CartItem([
                    'product_id'         => $product->id,
                    'product_variant_id' => null, // Assuming simple products for now
                    'quantity'           => $qtyToAdd,
                    'unit_price'         => $unitPrice,
                ]);
                $cart->items()->save($item);
            }
            
            // Update total
            $item->total = $item->unit_price * $item->quantity;
            $item->save();
        }

        $cart->refresh();

        return $this->respondWithEnrichedCart($cart, $request);
    }

    /**
     * DELETE /api/cart
     */
    public function clear(Request $request)
    {
        $cart = $this->resolveCart($request);
        $cart->items()->delete();
        // Also remove coupon
        $cart->coupon_id = null;
        $cart->save();

        $cart->refresh();
        return $this->respondWithEnrichedCart($cart, $request);
    }

    /**
     * POST /api/cart/coupon
     */
    public function applyCoupon(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $code = trim($data['code']);
        $coupon = \App\Models\Coupon::where('code', $code)->first();

        if (!$coupon) {
            return response()->json(['message' => 'Codul de reducere nu este valid.'], 404);
        }

        if (!$coupon->isValid()) {
             return response()->json(['message' => 'Codul de reducere nu mai este valabil.'], 400);
        }

        $cart = $this->resolveCart($request);
        
        // Basic validation: min cart value
        // Note: Full validation happens in PromotionPricingService usually, 
        // but simple checks here save processing.
        if ($coupon->min_cart_value > 0) {
             $subtotal = $cart->items->sum(fn($item) => $item->unit_price * $item->quantity);
             if ($subtotal < $coupon->min_cart_value) {
                 return response()->json(['message' => "Valoarea minimă a coșului trebuie să fie {$coupon->min_cart_value} RON."], 400);
             }
        }

        $cart->coupon_id = $coupon->id;
        $cart->save();

        return $this->respondWithEnrichedCart($cart, $request);
    }

    /**
     * DELETE /api/cart/coupon
     */
    public function removeCoupon(Request $request)
    {
        $cart = $this->resolveCart($request);
        $cart->coupon_id = null;
        $cart->save();

        return $this->respondWithEnrichedCart($cart, $request);
    }
}
