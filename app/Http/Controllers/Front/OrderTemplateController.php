<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\OrderTemplate;
use App\Models\OrderTemplateItem;
use App\Models\Product;
use App\Models\Cart;
use Illuminate\Http\Request;

class OrderTemplateController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $templates = OrderTemplate::with('items.product.images')
            ->where('user_id', $user->id)
            ->orderBy('name')
            ->get();

        return response()->json($templates);
    }

    public function show(Request $request, OrderTemplate $orderTemplate)
    {
        $this->authorizeTemplate($request->user()->id, $orderTemplate);

        $orderTemplate->load('items.product.images');

        return response()->json($orderTemplate);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'notes'        => 'nullable|string',
            'items'        => 'array',
            'items.*.product_id'       => 'required|integer|exists:products,id',
            'items.*.product_variant_id' => 'nullable|integer',
            'items.*.quantity'         => 'required|numeric|min:0.001',
        ]);

        $template = OrderTemplate::create([
            'user_id' => $user->id,
            'name'    => $data['name'],
            'notes'   => $data['notes'] ?? null,
        ]);

        if (!empty($data['items'])) {
            foreach ($data['items'] as $itemData) {
                OrderTemplateItem::create([
                    'order_template_id' => $template->id,
                    'product_id'        => $itemData['product_id'],
                    'product_variant_id'=> $itemData['product_variant_id'] ?? null,
                    'quantity'          => $itemData['quantity'],
                ]);
            }
        }

        $template->load('items.product.images');

        return response()->json($template, 201);
    }

    public function update(Request $request, OrderTemplate $orderTemplate)
    {
        $this->authorizeTemplate($request->user()->id, $orderTemplate);

        $data = $request->validate([
            'name'         => 'sometimes|string|max:255',
            'notes'        => 'nullable|string',
            'items'        => 'array',
            'items.*.id'   => 'nullable|integer|exists:order_template_items,id',
            'items.*.product_id'       => 'required|integer|exists:products,id',
            'items.*.product_variant_id' => 'nullable|integer',
            'items.*.quantity'         => 'required|numeric|min:0.001',
        ]);

        $orderTemplate->update([
            'name'  => $data['name'] ?? $orderTemplate->name,
            'notes' => $data['notes'] ?? $orderTemplate->notes,
        ]);

        if (isset($data['items'])) {
            $orderTemplate->items()->delete();

            foreach ($data['items'] as $itemData) {
                OrderTemplateItem::create([
                    'order_template_id' => $orderTemplate->id,
                    'product_id'        => $itemData['product_id'],
                    'product_variant_id'=> $itemData['product_variant_id'] ?? null,
                    'quantity'          => $itemData['quantity'],
                ]);
            }
        }

        $orderTemplate->load('items.product');

        return response()->json($orderTemplate);
    }

    public function destroy(Request $request, OrderTemplate $orderTemplate)
    {
        $this->authorizeTemplate($request->user()->id, $orderTemplate);

        $orderTemplate->items()->delete();
        $orderTemplate->delete();

        return response()->json(['message' => 'Template deleted']);
    }

    public function addToCart(Request $request, OrderTemplate $orderTemplate)
    {
        $user = $request->user();
        $this->authorizeTemplate($user->id, $orderTemplate);

        $cart = Cart::firstOrCreate([
            'user_id' => $user->id,
            'status'  => 'active',
        ]);

        $orderTemplate->load('items.product');

        foreach ($orderTemplate->items as $item) {
            $cart->items()->create([
                'product_id'        => $item->product_id,
                'product_variant_id'=> $item->product_variant_id,
                'quantity'          => $item->quantity,
                'unit_price'        => $item->product->price, // ajustează la logica ta de prețuri
            ]);
        }

        return response()->json([
            'message' => 'Template items added to cart',
            'cart_id' => $cart->id,
        ]);
    }

    protected function authorizeTemplate(int $userId, OrderTemplate $template): void
    {
        if ($template->user_id !== $userId) {
            abort(403);
        }
    }
}
