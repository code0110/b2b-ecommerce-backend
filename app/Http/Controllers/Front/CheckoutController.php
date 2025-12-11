<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function summary(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->where('status', 'active')->with('items.product', 'items.variant')->firstOrFail();

        $subtotal = $cart->items->sum('total');
        $shippingMethods = ShippingMethod::where('is_active', true)->get();

        return response()->json([
            'cart'             => $cart,
            'subtotal'         => $subtotal,
            'shipping_methods' => $shippingMethods,
        ]);
    }

    public function placeOrder(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $data = $request->validate([
            'shipping_method_id'   => ['required', 'integer', 'exists:shipping_methods,id'],
            'billing_address_id'   => ['required', 'integer', 'exists:addresses,id'],
            'shipping_address_id'  => ['required', 'integer', 'exists:addresses,id'],
            'payment_method'       => ['required', 'in:card,op,b2b_terms'],
        ]);

        $cart = Cart::where('user_id', $user->id)->where('status', 'active')->with('items.product', 'items.variant')->firstOrFail();

        return DB::transaction(function () use ($cart, $user, $data) {
            $subtotal = $cart->items->sum('total');
            $shippingTotal = 0; // TODO: calcul pe baza regulilor de shipping
            $grandTotal = $subtotal + $shippingTotal;

            $order = Order::create([
                'order_number'       => 'C' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
                'customer_id'        => $user->customer_id,
                'placed_by_user_id'  => $user->id,
                'status'             => 'pending',
                'type'               => $user->customer?->type ?? 'b2c',
                'total_items'        => $cart->items->sum('quantity'),
                'subtotal'           => $subtotal,
                'discount_total'     => 0,
                'tax_total'          => 0,
                'shipping_total'     => $shippingTotal,
                'grand_total'        => $grandTotal,
                'currency'           => 'RON',
                'payment_method'     => $data['payment_method'],
                'payment_status'     => $data['payment_method'] === 'card' ? 'pending' : 'awaiting',
                'shipping_method_id' => $data['shipping_method_id'],
                'billing_address_id' => $data['billing_address_id'],
                'shipping_address_id'=> $data['shipping_address_id'],
                'credit_blocked'     => false,
                'placed_at'          => now(),
                'due_date'           => null,
            ]);

            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id'          => $order->id,
                    'product_id'        => $item->product_id,
                    'product_variant_id'=> $item->product_variant_id,
                    'product_name'      => $item->product->name,
                    'sku'               => $item->product->internal_code,
                    'quantity'          => $item->quantity,
                    'unit_price'        => $item->unit_price,
                    'discount_amount'   => 0,
                    'tax_amount'        => 0,
                    'total'             => $item->total,
                ]);
            }

            $cart->status = 'converted';
            $cart->save();
            $cart->items()->delete();

            return response()->json($order->load('items'), 201);
        });
    }
}
