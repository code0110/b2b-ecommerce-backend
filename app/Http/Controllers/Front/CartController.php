<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected function resolveCart(Request $request): Cart
    {
        $user = $request->user();

        if ($user) {
            return Cart::firstOrCreate([
                'user_id' => $user->id,
                'status'  => 'active',
            ]);
        }

        $sessionId = $request->header('X-Cart-Session') ?? $request->session()->getId();

        return Cart::firstOrCreate([
            'session_id' => $sessionId,
            'status'     => 'active',
        ]);
    }

    public function show(Request $request)
    {
        $cart = $this->resolveCart($request)->load('items.product', 'items.variant');

        return $cart;
    }

    public function addItem(Request $request)
    {
        $cart = $this->resolveCart($request);

        $data = $request->validate([
            'product_id'        => ['required', 'integer', 'exists:products,id'],
            'product_variant_id'=> ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity'          => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $variant = null;

        if (!empty($data['product_variant_id'])) {
            $variant = ProductVariant::findOrFail($data['product_variant_id']);
        }

        $unitPrice = $variant
            ? ($variant->price_override ?? $variant->list_price)
            : ($product->price_override ?? $product->list_price);

        $item = $cart->items()->create([
            'product_id'        => $product->id,
            'product_variant_id'=> $variant?->id,
            'quantity'          => $data['quantity'],
            'unit_price'        => $unitPrice,
            'total'             => $unitPrice * $data['quantity'],
        ]);

        return response()->json($item->load('product', 'variant'), 201);
    }

    public function updateItem($itemId, Request $request)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $item = CartItem::findOrFail($itemId);

        $item->quantity = $data['quantity'];
        $item->total    = $item->unit_price * $item->quantity;
        $item->save();

        return response()->json($item->load('product', 'variant'));
    }

    public function removeItem($itemId)
    {
        $item = CartItem::findOrFail($itemId);
        $item->delete();

        return response()->json(['message' => 'Removed.']);
    }

    public function clear(Request $request)
    {
        $cart = $this->resolveCart($request);
        $cart->items()->delete();

        return response()->json(['message' => 'Cart cleared.']);
    }
}
