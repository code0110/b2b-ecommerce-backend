<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShippingMethod;
use App\Models\Address;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\CreditBlockedNotification;
use App\Services\Pricing\PromotionPricingService;

class CheckoutController extends Controller
{
    protected function resolveCart(Request $request)
    {
        $user = $request->user('sanctum') ?? $request->user();

        if ($user) {
            return Cart::with(['items.product.mainCategory', 'items.product.brand'])
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->first();
        }

        $sessionId = $request->session()->getId();
        return Cart::with(['items.product.mainCategory', 'items.product.brand'])
            ->where('session_id', $sessionId)
            ->where('status', 'active')
            ->first();
    }

    public function summary(Request $request, PromotionPricingService $pricing)
    {
        $user = $request->user('sanctum') ?? $request->user();

        $cart = $this->resolveCart($request);

        if (!$cart) {
            return response()->json([
                'message' => 'Coșul este gol.',
            ], 400);
        }

        $customer = $user ? \App\Models\Customer::find($user->customer_id) : null;

        $priced = $pricing->priceCart($cart, $customer);

        // aici poți adăuga taxele și transportul conform regulilor tale
        $shippingTotal = 0.00; // TODO: calculează în funcție de reguli
        $taxTotal = 0.00;      // TODO: calculează dacă vrei explicit

        $grandTotal = $priced['total'] + $shippingTotal + $taxTotal;

        $shippingMethods = ShippingMethod::where('is_active', true)->get();
        $addresses = $customer ? $customer->addresses : [];

        return response()->json([
            'items'           => $priced['items'],
            'subtotal'        => $priced['subtotal'],
            'discount_total'  => $priced['discount_total'],
            'shipping_total'  => $shippingTotal,
            'tax_total'       => $taxTotal,
            'grand_total'     => $grandTotal,
            'shipping_methods'=> $shippingMethods,
            'addresses'       => $addresses,
            'user'            => $user,
        ]);
    }

    public function placeOrder(Request $request, PromotionPricingService $pricing)
    {
        $user = $request->user('sanctum') ?? $request->user();

        // 1. Resolve Cart
        $cart = $this->resolveCart($request);
        if (!$cart) {
            return response()->json(['message' => 'Cart not found or empty.'], 404);
        }

        // Ensure cart items are loaded
        $cart->loadMissing(['items.product', 'items.variant']);
        if ($cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty.'], 400);
        }

        $validator = Validator::make($request->all(), [
            'shipping_method_id'   => ['required', 'integer', 'exists:shipping_methods,id'],
            'payment_method'       => ['required', 'in:card,op,b2b_terms'],
            'billing_address_id'   => ['nullable', 'integer', 'exists:addresses,id'],
            'shipping_address_id'  => ['nullable', 'integer', 'exists:addresses,id'],
            'billing_address'      => ['nullable', 'array'],
            'shipping_address'     => ['nullable', 'array'],
            'customer_visit_id'    => ['nullable', 'integer', 'exists:customer_visits,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // 2. Identify Customer
        $customer = null;
        if ($user) {
            $customer = \App\Models\Customer::find($user->customer_id);
        } elseif (!empty($data['billing_address_id'])) {
            // Guest mode: infer customer from billing address
            $address = Address::find($data['billing_address_id']);
            if ($address && $address->customer_id) {
                $customer = \App\Models\Customer::find($address->customer_id);
            }
        }

        if (!$customer) {
             return response()->json(['message' => 'Customer identification failed. Please login or provide a valid address linked to a customer.'], 400);
        }

        // Helper to resolve address
        $resolveAddress = function ($id, $addrData, $type, $customerId) {
            if ($id) {
                return Address::find($id);
            }
            if ($addrData && is_array($addrData)) {
                return Address::create([
                    'customer_id' => $customerId,
                    'type'        => $type,
                    'contact_name'=> $addrData['contact_name'] ?? 'N/A',
                    'phone'       => $addrData['phone'] ?? null,
                    'country'     => $addrData['country'] ?? 'Romania',
                    'county'      => $addrData['county'] ?? '',
                    'city'        => $addrData['city'] ?? '',
                    'street'      => $addrData['street'] ?? '',
                    'postal_code' => $addrData['postal_code'] ?? '',
                ]);
            }
            return null;
        };

        return DB::transaction(function () use ($cart, $user, $customer, $data, $pricing, $resolveAddress) {
            
            // Resolve addresses
            $billingAddress = $resolveAddress(
                $data['billing_address_id'] ?? null,
                $data['billing_address'] ?? null,
                'billing',
                $customer->id
            );
            $shippingAddress = $resolveAddress(
                $data['shipping_address_id'] ?? null,
                $data['shipping_address'] ?? null,
                'shipping',
                $customer->id
            );

            if (!$billingAddress || !$shippingAddress) {
                throw new \Exception('Addresses could not be resolved.');
            }

            // 3. Re-calculate prices
            $priced = $pricing->priceCart($cart, $customer);
            $pricedItemsMap = collect($priced['items'])->keyBy('id');

            $subtotal = $priced['subtotal'];
            $discountTotal = $priced['discount_total'];
            $shippingTotal = 0; // TODO: shipping rules
            $grandTotal = $priced['total'] + $shippingTotal;
            
            $order = Order::create([
                'order_number'       => 'C' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
                'customer_id'        => $customer->id,
                'placed_by_user_id'  => $user ? $user->id : null,
                'status'             => 'pending',
                'type'               => $customer->type ?? 'b2c',
                'total_items'        => $cart->items->sum('quantity'),
                'subtotal'           => $subtotal,
                'discount_total'     => $discountTotal,
                'tax_total'          => 0,
                'shipping_total'     => $shippingTotal,
                'grand_total'        => $grandTotal,
                'currency'           => 'RON',
                'payment_method'     => $data['payment_method'],
                'payment_status'     => $data['payment_method'] === 'card' ? 'pending' : 'awaiting',
                'shipping_method_id' => $data['shipping_method_id'],
                'billing_address_id' => $billingAddress->id,
                'shipping_address_id'=> $shippingAddress->id,
                'credit_blocked'     => false,
                'placed_at'          => now(),
                'due_date'           => null,
                'customer_visit_id'  => $data['customer_visit_id'] ?? null,
            ]);

            foreach ($cart->items as $item) {
                $calculatedItem = $pricedItemsMap->get($item->id);

                $finalUnitPrice = $calculatedItem ? $calculatedItem['unit_final_price'] : $item->unit_price;
                $finalLineTotal = $calculatedItem ? $calculatedItem['line_final_total'] : $item->total;
                $discountAmount = $calculatedItem ? ($calculatedItem['unit_base_price'] - $calculatedItem['unit_final_price']) : 0;

                OrderItem::create([
                    'order_id'          => $order->id,
                    'product_id'        => $item->product_id,
                    'product_variant_id'=> $item->product_variant_id,
                    'product_name'      => $item->product->name,
                    'sku'               => $item->product->internal_code,
                    'quantity'          => $item->quantity,
                    'unit_price'        => $finalUnitPrice,
                    'discount_amount'   => $discountAmount,
                    'tax_amount'        => 0,
                    'total'             => $finalLineTotal,
                ]);
            }

            // Approval & Credit Logic
             $requiresApproval = $customer->type === 'b2b' && $user && $user->requiresOrderApproval();
 
             if ($requiresApproval) {
                 $order->approval_status = 'pending';
                 $order->status          = 'pending_approval';
                 $order->save();
             } else {
                 if ($customer->type === 'b2b' && $customer->credit_limit > 0) {
                     $futureBalance = ($customer->current_balance ?? 0) + $order->grand_total;
 
                     if ($futureBalance > $customer->credit_limit) {
                         $order->credit_blocked = true;
                         $order->status         = 'credit_blocked';
                         $order->save();
                     } else {
                         $customer->current_balance = $futureBalance;
                         $customer->save();
                     }
                 }
             }

             // Notifications
             if ($user) {
                 try {
                    $user->notify(new OrderPlacedNotification($order));
                    
                    if ($order->credit_blocked) {
                        $user->notify(new CreditBlockedNotification($order));
                    }
                 } catch (\Exception $e) {
                     // Log notification error
                 }
             }
             
             // Notify Agent & Director about new order (if not blocked)
             // Blocked orders are handled below
             if (!$order->credit_blocked) {
                 try {
                     if ($customer->agent_user_id) {
                         $agent = \App\Models\User::find($customer->agent_user_id);
                         $agent?->notify(new OrderPlacedNotification($order));
                     }
                     if ($customer->sales_director_user_id) {
                         $director = \App\Models\User::find($customer->sales_director_user_id);
                         $director?->notify(new OrderPlacedNotification($order));
                     }
                 } catch (\Exception $e) {
                     // Log error
                 }
             }

             // Notify Sales Agents if credit blocked
             if ($order->credit_blocked) {
                 if ($customer->agent_user_id) {
                     optional($customer->agent)->notify(new CreditBlockedNotification($order));
                 }
                 if ($customer->sales_director_user_id) {
                     optional($customer->salesDirector)->notify(new CreditBlockedNotification($order));
                 }
             }
 
             $cart->status = 'converted';
            $cart->save();
            $cart->items()->delete();

            return response()->json($order->load('items'), 201);
        });
    }
}
