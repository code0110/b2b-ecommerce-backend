<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuickOrderController extends Controller
{
    public function search(Request $request)
    {
        $q = trim((string) $request->get('q', ''));

        $query = Product::query();

        if ($q !== '') {
            $query->where(function ($qBuilder) use ($q) {
                $qBuilder
                    ->where('name', 'like', "%{$q}%")
                    ->orWhere('internal_code', 'like', "%{$q}%")
                    ->orWhere('barcode', 'like', "%{$q}%");
            });
        }

        return $query->limit(50)->get();
    }

    public function addToCart(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'items'           => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required_without:items.*.sku', 'integer', 'exists:products,id'],
            'items.*.sku'        => ['nullable', 'string'],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.01'],
        ]);

        return DB::transaction(function () use ($user, $data) {
            $cart = Cart::firstOrCreate(
                ['user_id' => $user->id, 'status' => 'active'],
                ['currency' => 'RON']
            );

            foreach ($data['items'] as $row) {
                $product = null;

                if (!empty($row['product_id'])) {
                    $product = Product::findOrFail($row['product_id']);
                } elseif (!empty($row['sku'])) {
                    $product = Product::where('internal_code', $row['sku'])->firstOrFail();
                }

                $quantity = (float) $row['quantity'];
                if ($quantity <= 0) {
                    continue;
                }

                $unitPrice = $product->list_price ?? 0;

                $item = CartItem::firstOrNew([
                    'cart_id'          => $cart->id,
                    'product_id'       => $product->id,
                    'product_variant_id' => null,
                ]);

                $item->quantity = $item->exists ? $item->quantity + $quantity : $quantity;
                $item->unit_price = $unitPrice;
                $item->total = $item->quantity * $unitPrice;
                $item->save();
            }

            return response()->json($cart->load('items.product'), 200);
        });
    }
}
