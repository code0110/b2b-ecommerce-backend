<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Cart;
use App\Models\ShippingMethod;
use App\Services\Pricing\PromotionPricingService;
use App\Services\Pricing\PromotionEngine;
use App\Services\DiscountRuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuickOrderController extends Controller
{
    protected $pricingService;
    protected $promotionEngine;
    protected $discountRuleService;

    public function __construct(
        PromotionPricingService $pricingService,
        PromotionEngine $promotionEngine,
        DiscountRuleService $discountRuleService
    ) {
        $this->pricingService = $pricingService;
        $this->promotionEngine = $promotionEngine;
        $this->discountRuleService = $discountRuleService;
    }

    public function getCheckoutData(Request $request)
    {
        $customerId = $request->query('customer_id') ?? $request->header('X-Customer-ID');

        if (!$customerId) {
            return response()->json(['message' => 'Customer ID required'], 400);
        }

        $customer = Customer::with('addresses')->find($customerId);
        if (!$customer) {
            return response()->json(['message' => 'Customer not found'], 404);
        }

        $addresses = $customer->addresses;
        $shippingMethods = ShippingMethod::where('is_active', true)->get();

        return response()->json([
            'addresses' => $addresses,
            'shipping_methods' => $shippingMethods
        ]);
    }

    /**
     * Calculate totals for a list of items.
     */
    public function calculate(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price_override' => 'nullable|numeric|min:0',
            'items.*.discount_override' => 'nullable|numeric|min:0|max:100',
        ]);

        $customer = Customer::findOrFail($request->customer_id);
        $itemsData = $request->items;

        $productIds = collect($itemsData)->pluck('product_id')->unique();
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

        // Build virtual items collection
        $virtualItems = collect();

        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $canOverride = $user->hasRole(['admin', 'sales_director', 'sales_agent']);
        $bypassApproval = $user->hasRole(['admin', 'sales_director']);

        foreach ($itemsData as $itemData) {
            $product = $products->get($itemData['product_id']);
            if (!$product) continue;

            // Create a virtual object
            $virtualItem = new \stdClass();
            $virtualItem->product = $product;
            $virtualItem->quantity = $itemData['quantity'];
            $virtualItem->id = null; // No ID
            
            // Attach overrides only if user has permission
            if ($canOverride) {
                $virtualItem->price_override = $itemData['price_override'] ?? null;
                $virtualItem->discount_override = $itemData['discount_override'] ?? null;
            } else {
                $virtualItem->price_override = null;
                $virtualItem->discount_override = null;
            }

            $virtualItems->push($virtualItem);
        }

        // Calculate standard prices using PromotionEngine (New System)
        $calculatedData = $this->promotionEngine->calculateItems($virtualItems, $user, $customer);

        // Apply overrides if any (and recalculate totals)
        $finalItems = [];
        $subtotal = 0;
        $discountTotal = 0;
        $grandTotal = 0;
        $requiresApproval = false;
        
        $derogationThreshold = $this->discountRuleService->getApprovalThreshold($user);
        $maxDiscount = $this->discountRuleService->getMaxDiscount($user);
        
        $globalDiscountPercent = $request->input('global_discount_percent', 0);
        
        // Validate permissions for global discount
        if ($globalDiscountPercent > 0) {
            if (!$customer->allow_global_discount && !$canOverride) {
                 $globalDiscountPercent = 0; // Force to 0 if not allowed
            }
            if ($globalDiscountPercent > $maxDiscount) {
                return response()->json(['message' => "Discount global nu poate depăși {$maxDiscount}%"], 422);
            }
        }

        foreach ($calculatedData['items'] as $index => $cItem) {
            // Find original input item (assuming order is preserved or we map by product_id)
            // calculateItems preserves order of iteration usually, but let's be safe.
            // We can match by product_id
            $virtualItem = $virtualItems->firstWhere('product.id', $cItem['product_id']);
            
            if (!$virtualItem) continue; // Should not happen

            // Base values from calculation
            $unitPrice = $cItem['unit_base_price'];
            $discountPercent = 0; // Derived
            $finalUnitPrice = $cItem['unit_final_price'];
            
            // Logic: 
            // 1. If price_override is present, that becomes the unit_price (base).
            // 2. If discount_override is present, that is applied.
            
            $isOverridden = false;

            if (!is_null($virtualItem->price_override)) {
                $unitPrice = (float) $virtualItem->price_override;
                $isOverridden = true;
            }

            if (!is_null($virtualItem->discount_override)) {
                // Check permission for line discount
                if ($customer->allow_line_discount || $canOverride) {
                    if ((float) $virtualItem->discount_override > $maxDiscount) {
                        return response()->json(['message' => "Discountul de linie pentru {$cItem['product_name']} nu poate depăși {$maxDiscount}%"], 422);
                    }
                    $discountPercent = (float) $virtualItem->discount_override;
                    $finalUnitPrice = $unitPrice * (1 - ($discountPercent / 100));
                    $isOverridden = true;
                }
            } else {
                // If no discount override, but price was overridden
                if ($isOverridden) {
                    $finalUnitPrice = $unitPrice;
                } else {
                    // Use calculated values from PromotionEngine
                    // Calculate implicit percent
                    if ($unitPrice > 0) {
                        $discountPercent = round((($unitPrice - $finalUnitPrice) / $unitPrice) * 100, 2);
                    }
                }
            }

            // Check approval
            if ($isOverridden && !$bypassApproval && $discountPercent > $derogationThreshold) {
                $requiresApproval = true;
            }

            $lineTotal = $finalUnitPrice * $virtualItem->quantity;
            $lineDiscount = ($unitPrice - $finalUnitPrice) * $virtualItem->quantity;

            $finalItems[] = [
                'product_id' => $cItem['product_id'],
                'product_name' => $cItem['product_name'],
                'sku' => $products->get($cItem['product_id'])->internal_code ?? $products->get($cItem['product_id'])->sku,
                'quantity' => $virtualItem->quantity,
                'unit_price' => round($unitPrice, 2),
                'discount_percent' => round($discountPercent, 2),
                'final_unit_price' => round($finalUnitPrice, 2),
                'line_total' => round($lineTotal, 2),
                'line_discount' => round($lineDiscount, 2),
                'is_overridden' => $isOverridden,
                'applied_promotions' => $cItem['applied_promotions'] ?? [],
            ];

            $subtotal += ($unitPrice * $virtualItem->quantity);
            $discountTotal += $lineDiscount;
            $grandTotal += $lineTotal;
        }

        // Apply Global Discount to Grand Total (after line discounts)
        if ($globalDiscountPercent > 0) {
            $globalDiscountAmount = $grandTotal * ($globalDiscountPercent / 100);
            $grandTotal -= $globalDiscountAmount;
            $discountTotal += $globalDiscountAmount;
            
            // Check approval for global discount
            if (!$bypassApproval && $globalDiscountPercent > $derogationThreshold) {
                $requiresApproval = true;
            }
        }
        
        // Add Shipping Cost from PromotionEngine if not overridden?
        // QuickOrder doesn't seem to have shipping selection in calculation input usually, but createOrder has.
        // If PromotionEngine calculated shipping, we should include it.
        $shippingCost = $calculatedData['final_shipping'] ?? 0.0;
        $shippingDiscount = $calculatedData['shipping_discount'] ?? 0.0;
        
        // Note: grandTotal calculated above implies NO shipping.
        // We should add shipping to grandTotal.
        $grandTotal += $shippingCost;

        return response()->json([
            'items' => $finalItems,
            'subtotal' => round($subtotal, 2),
            'discount_total' => round($discountTotal, 2),
            'shipping_cost' => round($shippingCost, 2),
            'shipping_discount' => round($shippingDiscount, 2),
            'total' => round($grandTotal, 2),
            'requires_approval' => $requiresApproval,
        ]);
    }

    /**
     * Get available promotions for a specific product and customer.
     */
    public function availablePromotions(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'product_id' => 'required|exists:products,id',
        ]);

        $customer = Customer::findOrFail($request->customer_id);
        $product = Product::findOrFail($request->product_id);

        $activePromotions = $this->pricingService->getActivePromotionsForCustomer($customer);
        
        $applicablePromotions = $activePromotions->filter(function ($promotion) use ($product) {
            return $this->pricingService->promotionAppliesToProduct($promotion, $product);
        })->values();

        $basePrice = $this->pricingService->getBasePrice($product, $customer);
        
        $results = $applicablePromotions->map(function ($promotion) use ($basePrice) {
            [$promoPrice, $discountPercent] = $this->pricingService->applyPromotionOnPrice($promotion, $basePrice);
            
            return [
                'id' => $promotion->id,
                'name' => $promotion->name,
                'description' => $promotion->description,
                'bonus_type' => $promotion->bonus_type,
                'discount_percent' => $promotion->discount_percent,
                'discount_value' => $promotion->discount_value,
                'min_qty' => $promotion->min_qty_per_product,
                'promo_price' => $promoPrice,
                'calculated_discount_percent' => $discountPercent,
                'start_at' => $promotion->start_at,
                'end_at' => $promotion->end_at,
            ];
        });

        return response()->json([
            'base_price' => $basePrice,
            'promotions' => $results
        ]);
    }

    /**
     * Get all active promotions for a customer (GET method).
     */
    public function getPromotionsForCustomer($customerId)
    {
        $customer = Customer::findOrFail($customerId);
        
        // Get all active promotions
        $promotions = $this->pricingService->getActivePromotionsForCustomer($customer);
        
        // Enrich with product details if applicable
        $result = $promotions->map(function ($promotion) use ($customer) {
            $data = [
                'id' => $promotion->id,
                'name' => $promotion->name,
                'description' => $promotion->description,
                'bonus_type' => $promotion->bonus_type,
                'applies_to' => $promotion->applies_to,
                'min_qty' => $promotion->min_qty_per_product,
                'discount_percent' => $promotion->discount_percent,
                'discount_value' => $promotion->discount_value,
                'products' => [],
                'benefit' => [
                    'discountPercent' => $promotion->discount_percent,
                    'discountValue' => $promotion->discount_value
                ]
            ];

            // If promotion applies to specific products, load them
            if ($promotion->applies_to === 'products') {
                $productIds = $promotion->products->pluck('id');
                $products = Product::whereIn('id', $productIds)->take(50)->get();
                
                $data['products'] = $products->map(function ($product) use ($promotion, $customer) {
                    // Calculate price with this promotion
                    [$promoPrice, $discountPercent] = $this->pricingService->applyPromotionOnPrice($promotion, $this->pricingService->getBasePrice($product, $customer));
                    
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->internal_code ?? $product->sku,
                        'base_price' => $this->pricingService->getBasePrice($product, $customer),
                        'promo_price' => $promoPrice,
                        'discount_percent' => $discountPercent
                    ];
                });
            }
            
            return $data;
        });

        return response()->json(['data' => $result]);
    }

    /**
     * Get all active promotions for a customer, potentially with associated products.
     */
    public function getCustomerPromotions(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
        ]);

        $customer = Customer::findOrFail($request->customer_id);
        
        // Get all active promotions
        $promotions = $this->pricingService->getActivePromotionsForCustomer($customer);
        
        // Enrich with product details if applicable
        $result = $promotions->map(function ($promotion) use ($customer) {
            $data = [
                'id' => $promotion->id,
                'name' => $promotion->name,
                'description' => $promotion->description,
                'bonus_type' => $promotion->bonus_type,
                'applies_to' => $promotion->applies_to,
                'min_qty' => $promotion->min_qty_per_product,
                'discount_percent' => $promotion->discount_percent,
                'discount_value' => $promotion->discount_value,
                'products' => [],
            ];

            // If promotion applies to specific products, load them
            if ($promotion->applies_to === 'products') {
                $productIds = $promotion->products->pluck('id');
                $products = Product::whereIn('id', $productIds)->take(50)->get();
                
                $data['products'] = $products->map(function ($product) use ($promotion, $customer) {
                    // Calculate price with this promotion
                    [$promoPrice, $discountPercent] = $this->pricingService->applyPromotionOnPrice($promotion, $this->pricingService->getBasePrice($product, $customer));
                    
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'sku' => $product->internal_code ?? $product->sku,
                        'base_price' => $this->pricingService->getBasePrice($product, $customer),
                        'promo_price' => $promoPrice,
                        'discount_percent' => $discountPercent
                    ];
                });
            }
            
            return $data;
        });

        return response()->json($result);
    }

    /**
     * Create Order from Quick Order form.
     */
    public function createOrder(Request $request)
    {
        $request->validate([
            'customer_visit_id' => 'nullable|exists:customer_visits,id',
            'due_date' => 'nullable|date',
            'payment_method' => 'nullable|string',
            'payment_document' => 'nullable|string',
            'global_discount_percent' => 'nullable|numeric|min:0|max:100',
            'internal_note' => 'nullable|string',
            'shipping_method_id' => 'nullable|exists:shipping_methods,id',
            'billing_address_id' => 'nullable|exists:addresses,id',
            'shipping_address_id' => 'nullable|exists:addresses,id',
        ]);

        // Re-use logic or trust the input?
        // Better to re-calculate to ensure integrity.
        // But for "Quick Order", user sees the calculation.
        // Let's assume the calculation endpoint is called before, but we must validate again.
        
        // Actually, we can just call calculate internally.
        $calcResponse = $this->calculate($request);
        if ($calcResponse->status() !== 200) {
            return $calcResponse;
        }
        
        $data = $calcResponse->getData(); // Object
        
        DB::beginTransaction();
        try {
            // Generate Order Number
            $lastOrder = Order::latest('id')->first();
            $nextId = $lastOrder ? $lastOrder->id + 1 : 1;
            $orderNumber = 'CMD-' . date('Y') . '-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

            $order = new Order();
            $order->order_number = $orderNumber;
            $order->customer_id = $request->customer_id;
            $order->customer_visit_id = $request->customer_visit_id;
            $order->placed_by_user_id = Auth::id();
            $order->type = 'b2b';
            $order->currency = 'RON';
            
            // New fields
            $order->due_date = $request->due_date ? \Carbon\Carbon::parse($request->due_date) : null;
            $order->payment_method = $request->payment_method;
            $order->payment_document = $request->payment_document;
            $order->global_discount_percent = $request->global_discount_percent ?? 0;
            $order->internal_note = $request->internal_note;
            $order->shipping_method_id = $request->shipping_method_id;
            $order->billing_address_id = $request->billing_address_id;
            $order->shipping_address_id = $request->shipping_address_id;
            
            // Status logic
            $requiresApproval = $data->requires_approval;
            
            if ($requiresApproval) {
                $order->status = 'pending_approval';
                $order->approval_status = 'pending';
            } else {
                $order->status = 'pending'; // Normal pending
                $order->approval_status = 'approved'; // Auto-approved
            }

            $order->subtotal = $data->subtotal;
            $order->discount_total = $data->discount_total;
            $order->grand_total = $data->total;
            $order->total_items = collect($data->items)->sum('quantity');
            $order->payment_status = 'pending';
            
            $order->save();

            foreach ($data->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'sku' => $item->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount_amount' => $item->line_discount, // Total discount for line
                    'total' => $item->line_total,
                ]);
            }

            // Clear Cart Logic
            $user = Auth::user();
            if ($user) {
                 $customerId = $request->header('X-Customer-ID') ?? $request->customer_id ?? $user->customer_id;
                 
                 if ($customerId) {
                     // Clear shared cart
                     Cart::where('customer_id', $customerId)
                         ->where('status', 'active')
                         ->delete();
                 } else {
                     // Fallback for non-customer context
                     Cart::where('user_id', $user->id)
                         ->whereNull('customer_id')
                         ->where('status', 'active')
                         ->delete();
                 }
            }

            DB::commit();
            
            return response()->json([
                'order_id' => $order->id,
                'message' => 'Comanda a fost creată cu succes.',
                'requires_approval' => $requiresApproval
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Eroare la crearea comenzii: ' . $e->getMessage()], 500);
        }
    }
}
