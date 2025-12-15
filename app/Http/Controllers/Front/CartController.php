<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use App\Services\Pricing\PromotionPricingService;

class CartController extends Controller
{
    /**
     * Coș-ul este legat de:
     *  - user_id (dacă e logat via Sanctum sau sesiune web)
     *  - session_id (dacă e guest, folosind sesiunea Laravel)
     */
    protected function resolveCart(Request $request): Cart
    {
        // User logat: încercăm întâi via sanctum, apoi via sesiunea web
        $user = auth('sanctum')->user() ?? auth()->user();

        if ($user) {
            return Cart::firstOrCreate([
                'user_id' => $user->id,
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

    /**
     * GET /api/cart
     */
    public function show(Request $request, PromotionPricingService $pricing)
    {
        $cart = $this->resolveCart($request)->load('items.product', 'items.variant');

        $customer = optional($request->user())->customer;
        $priced = $pricing->priceCart($cart, $customer);

        return response()->json([
            'id'        => $cart->id,
            'items'     => $priced['items'],
            'subtotal'  => $priced['subtotal'],
            'discounts' => $priced['discount_total'],
            'total'     => $priced['total'],
        ]);
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

        return response()->json($this->transformCart($cart), 201);
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

        return response()->json($this->transformCart($cart));
    }

    /**
     * DELETE /api/cart/items/{item}
     */
    public function removeItem($itemId, Request $request)
    {
        $item = CartItem::findOrFail($itemId);
        $cart = $item->cart;

        $item->delete();

        return response()->json($this->transformCart($cart));
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
