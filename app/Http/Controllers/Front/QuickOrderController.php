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

class QuickOrderController extends Controller
{
    protected PromotionPricingService $promotionPricingService;

    public function __construct(PromotionPricingService $promotionPricingService)
    {
        $this->promotionPricingService = $promotionPricingService;
    }

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

                // Determine price and variant ID
                $variantId = $variant ? $variant->id : null;
                $unitPrice = $variant ? ($variant->price_override ?? $variant->list_price) : ($product->list_price ?? 0);

                $item = CartItem::firstOrNew([
                    'cart_id'          => $cart->id,
                    'product_id'       => $product->id,
                    'product_variant_id' => $variantId,
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
