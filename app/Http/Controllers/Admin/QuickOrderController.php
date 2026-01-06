<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Pricing\PromotionPricingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuickOrderController extends Controller
{
    protected $pricingService;

    public function __construct(PromotionPricingService $pricingService)
    {
        $this->pricingService = $pricingService;
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

        // Calculate standard prices using service
        $calculatedData = $this->pricingService->priceItems($virtualItems, $customer);

        // Apply overrides if any (and recalculate totals)
        $finalItems = [];
        $subtotal = 0;
        $discountTotal = 0;
        $grandTotal = 0;
        $requiresApproval = false;
        
        $derogationThreshold = Setting::get('offer_discount_threshold_approval', 15);
        
        foreach ($calculatedData['items'] as $index => $cItem) {
            // Find original input item (assuming order is preserved or we map by product_id)
            // priceItems preserves order
            $virtualItem = $virtualItems[$index]; 
            
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
                $discountPercent = (float) $virtualItem->discount_override;
                $finalUnitPrice = $unitPrice * (1 - ($discountPercent / 100));
                $isOverridden = true;
            } else {
                // If no discount override, but price was overridden, we assume 0 discount or keep existing?
                // Let's assume if price is overridden, promotions might not apply anymore unless we re-run logic.
                // Simpler: If any override, we use manual values. 
                // If only price override, discount is 0.
                if ($isOverridden) {
                    $finalUnitPrice = $unitPrice;
                } else {
                    // Use calculated values
                    // Calculate implicit percent
                    if ($unitPrice > 0) {
                        $discountPercent = round((($unitPrice - $finalUnitPrice) / $unitPrice) * 100, 2);
                    }
                }
            }

            // Check approval
            // Only require approval if the price/discount was manually overridden and exceeds threshold.
            // Standard system promotions are considered pre-approved.
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

        return response()->json([
            'items' => $finalItems,
            'subtotal' => round($subtotal, 2),
            'discount_total' => round($discountTotal, 2),
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
