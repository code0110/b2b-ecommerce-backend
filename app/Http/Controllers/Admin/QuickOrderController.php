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
     * Create Order from Quick Order form.
     */
    public function createOrder(Request $request)
    {
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
