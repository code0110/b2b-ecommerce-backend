<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\OrderTemplate;
use App\Models\OrderTemplateItem;
use App\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderTemplateController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return OrderTemplate::where('user_id', $user->id)
            ->withCount('items')
            ->orderBy('name')
            ->get();
    }

    public function show($id, Request $request)
    {
        $user = $request->user();

        $template = OrderTemplate::where('user_id', $user->id)
            ->where('id', $id)
            ->with('items.product')
            ->firstOrFail();

        return $template;
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'          => ['required', 'string', 'max:191'],
            'notes'         => ['nullable', 'string'],
            'items'         => ['nullable', 'array'],
            'items.*.product_id' => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.quantity'   => ['required_with:items', 'numeric', 'min:0.01'],
        ]);

        return DB::transaction(function () use ($user, $data) {
            $template = OrderTemplate::create([
                'user_id' => $user->id,
                'name'    => $data['name'],
                'notes'   => $data['notes'] ?? null,
            ]);

            if (!empty($data['items'])) {
                foreach ($data['items'] as $row) {
                    OrderTemplateItem::create([
                        'order_template_id' => $template->id,
                        'product_id'        => $row['product_id'],
                        'product_variant_id'=> null,
                        'quantity'          => $row['quantity'],
                    ]);
                }
            }

            return response()->json($template->load('items.product'), 201);
        });
    }

    public function update($id, Request $request)
    {
        $user = $request->user();

        $template = OrderTemplate::where('user_id', $user->id)->where('id', $id)->firstOrFail();

        $data = $request->validate([
            'name'          => ['sometimes', 'string', 'max:191'],
            'notes'         => ['nullable', 'string'],
            'items'         => ['nullable', 'array'],
            'items.*.product_id' => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.quantity'   => ['required_with:items', 'numeric', 'min:0.01'],
        ]);

        return DB::transaction(function () use ($template, $data) {
            $template->update([
                'name'  => $data['name'] ?? $template->name,
                'notes' => $data['notes'] ?? $template->notes,
            ]);

            if (array_key_exists('items', $data)) {
                $template->items()->delete();
                foreach ($data['items'] as $row) {
                    OrderTemplateItem::create([
                        'order_template_id' => $template->id,
                        'product_id'        => $row['product_id'],
                        'product_variant_id'=> null,
                        'quantity'          => $row['quantity'],
                    ]);
                }
            }

            return response()->json($template->load('items.product'));
        });
    }

    public function destroy($id, Request $request)
    {
        $user = $request->user();

        $template = OrderTemplate::where('user_id', $user->id)->where('id', $id)->firstOrFail();
        $template->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    public function addToCart($id, Request $request)
    {
        $user = $request->user();

        $template = OrderTemplate::where('user_id', $user->id)
            ->where('id', $id)
            ->with('items.product')
            ->firstOrFail();

        return DB::transaction(function () use ($user, $template) {
            $cart = Cart::firstOrCreate(
                ['user_id' => $user->id, 'status' => 'active'],
                ['currency' => 'RON']
            );

            foreach ($template->items as $item) {
                /** @var Product $product */
                $product = $item->product;
                if (!$product) {
                    continue;
                }

                $unitPrice = $product->list_price ?? 0;

                $cartItem = CartItem::firstOrNew([
                    'cart_id'          => $cart->id,
                    'product_id'       => $product->id,
                    'product_variant_id' => null,
                ]);

                $cartItem->quantity = $cartItem->exists
                    ? $cartItem->quantity + $item->quantity
                    : $item->quantity;

                $cartItem->unit_price = $unitPrice;
                $cartItem->total = $cartItem->quantity * $unitPrice;
                $cartItem->save();
            }

            return response()->json($cart->load('items.product'), 200);
        });
    }
}
