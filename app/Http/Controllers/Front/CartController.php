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
        // User logat: încercăm întâi via sanctum, apoi via sesiunea web
        $user = $request->user('sanctum') ?? $request->user();

        if ($user) {
            return Cart::firstOrCreate([
                'user_id' => $user->id,
                'customer_id' => $user->customer_id, // Support for impersonation / specific customer carts
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
        $user     = $request->user();
        $customer = $user?->customer ?? null;

        $pricing = $this->promotionPricingService->priceCart($cart, $customer);

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
        
        // Get all products associated with this promotion
        // Assuming the promotion has a 'products' relationship
        $products = $promotion->products;

        if ($products->isEmpty()) {
            return response()->json(['message' => 'Această promoție nu are produse asociate direct.'], 400);
        }

        $cart = $this->resolveCart($request);

        foreach ($products as $product) {
            // Check if item exists
            $item = $cart->items()
                ->where('product_id', $product->id)
                ->first();

            $unitPrice = $product->price_override ?? $product->list_price ?? 0;

            if ($item) {
                $item->quantity += 1; // Increment by 1
                // We don't update price here usually as it might have been set by something else, 
                // but for consistency with addItem:
                // $item->unit_price = $unitPrice; 
                $item->save();
            } else {
                $item = new CartItem([
                    'product_id'         => $product->id,
                    'product_variant_id' => null, // Assuming simple products for now
                    'quantity'           => 1,
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

        return response()->json($this->transformCart($cart));
    }
}
