<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Offer;
use App\Models\OfferItem;
use App\Models\OfferMessage;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\Pricing\PromotionPricingService;
use App\Models\Setting;
use App\Notifications\OfferStatusChangedNotification;
use App\Notifications\OfferMessageNotification;
use App\Notifications\OfferApprovalRequiredNotification;

class OfferController extends Controller
{
    public function checkPrice(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'product_id' => 'required|exists:products,id',
        ]);

        $customer = \App\Models\Customer::find($request->customer_id);
        $product = \App\Models\Product::find($request->product_id);

        $service = new PromotionPricingService();
        $priceData = $service->calculateProductPrice($product, $customer);

        return response()->json($priceData);
    }

    /**
     * Convert Offer to Order
     */
    public function convertToOrder(Request $request, $id)
    {
        $offer = Offer::with(['items.product', 'customer'])->findOrFail($id);
        
        $this->ensureOfferAccess($offer);

        // Ensure offer is in a state that can be converted
        if (!in_array($offer->status, ['accepted', 'sent', 'approved'])) {
            return response()->json(['message' => 'Oferta trebuie să fie acceptată de client pentru a fi transformată în comandă.'], 400);
        }

        if ($offer->status === 'completed') {
             return response()->json(['message' => 'Oferta a fost deja transformată.'], 400);
        }

        DB::beginTransaction();
        try {
            // Generate Order Number
            $lastOrder = \App\Models\Order::latest('id')->first();
            $nextId = $lastOrder ? $lastOrder->id + 1 : 1;
            $orderNumber = 'CMD-' . date('Y') . '-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

            $order = new \App\Models\Order();
            $order->order_number = $orderNumber;
            $order->customer_id = $offer->customer_id;
            $order->customer_visit_id = $offer->customer_visit_id;
            $order->placed_by_user_id = Auth::id();
            $order->status = 'pending'; 
            $order->type = 'b2b';
            $order->currency = 'RON'; // Default
            
            $order->save(); 

            $subtotal = 0;
            $discountTotal = 0;
            $grandTotal = 0;
            $totalItems = 0;

            foreach ($offer->items as $item) {
                 $product = $item->product;
                 $unitPrice = $item->unit_price;
                 $qty = $item->quantity;
                 
                 $finalUnitPrice = $item->final_price; 
                 $lineTotal = $finalUnitPrice * $qty;
                 
                 $originalLineTotal = $unitPrice * $qty;
                 $lineDiscount = $originalLineTotal - $lineTotal;

                 \App\Models\OrderItem::create([
                     'order_id' => $order->id,
                     'product_id' => $item->product_id,
                     'product_name' => $product ? $product->name : 'Produs Șters',
                     'sku' => $product ? ($product->internal_code ?? $product->sku) : '',
                     'quantity' => $qty,
                     'unit_price' => $unitPrice,
                     'discount_amount' => $lineDiscount,
                     'total' => $lineTotal,
                 ]);
                 
                 $subtotal += $originalLineTotal;
                 $discountTotal += $lineDiscount;
                 $grandTotal += $lineTotal;
                 $totalItems += $qty;
            }

            $order->subtotal = $subtotal;
            $order->discount_total = $discountTotal;
            $order->grand_total = $grandTotal;
            $order->total_items = $totalItems;
            $order->payment_status = 'pending';
            $order->save();

            // Update Offer
            $offer->status = 'completed';
            $offer->save();

            DB::commit();
            return response()->json(['order_id' => $order->id, 'message' => 'Comanda a fost creată cu succes.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Eroare la crearea comenzii: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $query = Offer::with(['customer', 'agent', 'items.product', 'messages.user']);

        // Role based filtering
        if ($user->hasRole('sales_agent')) {
            $query->where('agent_id', $user->id);
        } elseif ($user->hasRole('sales_director')) {
            // Director sees their own and their subordinates' offers
            $subordinateIds = User::where('director_id', $user->id)->pluck('id');
            $query->where(function($q) use ($user, $subordinateIds) {
                $q->where('agent_id', $user->id)
                  ->orWhereIn('agent_id', $subordinateIds);
            });
        } elseif ($user->hasRole('admin')) {
             // Admin sees all
        } else {
            // Assume Client (or other role) -> See only offers for their customer_id
            // Need to link User -> Customer. 
            // Usually user has customer_id or linked via customer_users.
            // Assuming $user->customer_id exists or similar logic.
            // Check User model or Auth logic.
            // Fallback: If no role matched above, restrict to user's customer.
            if ($user->customer_id) {
                $query->where('customer_id', $user->customer_id);
                // Clients should only see 'sent', 'approved', 'negotiation' status?
                // Not 'draft' or 'pending_approval' (internal).
                $query->whereIn('status', ['sent', 'approved', 'negotiation', 'rejected', 'completed', 'accepted']);
            } else {
                 // No customer attached? Return empty?
                 // Or maybe it's a B2C user?
                 return response()->json([]);
            }
        }

        // Filter by Status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by Customer
        if ($request->has('customer_id') && $request->customer_id) {
            $query->where('customer_id', $request->customer_id);
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate(20));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'required|numeric|min:0|max:100',
            'valid_until' => 'nullable|date',
            'notes' => 'nullable|string',
            'quote_request_id' => 'nullable|integer|exists:quote_requests,id',
            'customer_visit_id' => 'nullable|exists:customer_visits,id',
        ]);

        DB::beginTransaction();
        try {
            $offer = new Offer();
            $offer->agent_id = Auth::id();
            $offer->customer_id = $request->customer_id;
            $offer->customer_visit_id = $request->customer_visit_id;
            $offer->quote_request_id = $request->quote_request_id;
            $offer->status = 'draft';
            $offer->notes = $request->notes;
            $offer->valid_until = $request->valid_until;
            $offer->save();

            $totalAmount = 0;
            $totalDiscount = 0;
            $requiresApproval = false;
            $derogationThreshold = Setting::get('offer_discount_threshold_approval', 15);
            $maxDiscount = Setting::get('offer_discount_max', 20);

            foreach ($request->items as $item) {
                if ($item['discount_percent'] > $maxDiscount) {
                    throw new \Exception("Discount cannot exceed {$maxDiscount}% for product ID {$item['product_id']}.");
                }

                $finalPrice = $item['unit_price'] * (1 - ($item['discount_percent'] / 100));
                $lineTotal = $finalPrice * $item['quantity'];
                
                $originalLineTotal = $item['unit_price'] * $item['quantity'];
                $lineDiscount = $originalLineTotal - $lineTotal;

                if ($item['discount_percent'] > $derogationThreshold) {
                    $requiresApproval = true;
                }

                OfferItem::create([
                    'offer_id' => $offer->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_percent' => $item['discount_percent'],
                    'final_price' => $finalPrice,
                ]);

                $totalAmount += $lineTotal;
                $totalDiscount += $lineDiscount;
            }

            $offer->total_amount = $totalAmount;
            $offer->discount_amount = $totalDiscount;
            $offer->requires_director_approval = $requiresApproval;
            
            // If user is Admin or Director, they don't need approval
            /** @var \App\Models\User $currentUser */
            $currentUser = Auth::user();
            if ($currentUser->hasRole('admin') || $currentUser->hasRole('sales_director')) {
                $offer->requires_director_approval = false;
            }

            $offer->save();

            if ($request->quote_request_id) {
                QuoteRequest::where('id', $request->quote_request_id)->update(['status' => 'offered']);
            }

            if ($offer->requires_director_approval) {
                // Notification will be sent when status changes to pending_approval
            }

            DB::commit();
            return response()->json($offer->load('items'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating offer: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $offer = Offer::with(['customer', 'agent', 'items.product', 'messages.user'])->findOrFail($id);
        
        // Check permission (Director/Agent/Admin) logic similar to index...
        // Assuming middleware handles basic auth, but rigorous checking is good practice.

        return response()->json($offer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $offer = Offer::findOrFail($id);
        
        // Prevent editing if already approved or completed (unless logic allows reopening)
        if (in_array($offer->status, ['approved', 'completed', 'rejected'])) {
            return response()->json(['message' => 'Cannot edit finalized offer.'], 400);
        }

        $request->validate([
            'items' => 'required|array|min:1',
            // ... validation similar to store
        ]);

        DB::beginTransaction();
        try {
            $offer->notes = $request->notes;
            $offer->valid_until = $request->valid_until;
            // If status was pending_approval, reset to draft or negotiation if changed
            if ($offer->status === 'pending_approval') {
                $offer->status = 'draft';
            }
            $offer->save();

            // Recreate items
            $offer->items()->delete();

            $totalAmount = 0;
            $totalDiscount = 0;
            $requiresApproval = false;
            $derogationThreshold = Setting::get('offer_discount_threshold_approval', 15);
            $maxDiscount = Setting::get('offer_discount_max', 20);

            foreach ($request->items as $item) {
                if ($item['discount_percent'] > $maxDiscount) {
                    throw new \Exception("Discount cannot exceed {$maxDiscount}% for product ID {$item['product_id']}.");
                }

                $finalPrice = $item['unit_price'] * (1 - ($item['discount_percent'] / 100));
                $lineTotal = $finalPrice * $item['quantity'];
                
                $originalLineTotal = $item['unit_price'] * $item['quantity'];
                $lineDiscount = $originalLineTotal - $lineTotal;

                if ($item['discount_percent'] > $derogationThreshold) {
                    $requiresApproval = true;
                }

                OfferItem::create([
                    'offer_id' => $offer->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_percent' => $item['discount_percent'],
                    'final_price' => $finalPrice,
                ]);

                $totalAmount += $lineTotal;
                $totalDiscount += $lineDiscount;
            }

            $offer->total_amount = $totalAmount;
            $offer->discount_amount = $totalDiscount;
            $offer->requires_director_approval = $requiresApproval;
            
            /** @var \App\Models\User $updater */
            $updater = Auth::user();
             if ($updater->hasRole('admin') || $updater->hasRole('sales_director')) {
                $offer->requires_director_approval = false;
            }

            $offer->save();

            DB::commit();
            return response()->json($offer->load('items'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating offer'], 500);
        }
    }

    /**
     * Change Status (Send, Approve, Reject)
     */
    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:sent,approved,rejected,completed,accepted,negotiation'
        ]);

        $offer = Offer::findOrFail($id);
        
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $newStatus = $request->status;

        // Check if user is allowed to change status
        if ($user->hasRole('sales_agent')) {
            // Agents can only mark as sent, completed, rejected (if not approved yet?)
            // If pending approval, they cannot change to approved.
            if ($newStatus === 'approved') {
                return response()->json(['message' => 'Unauthorized status change.'], 403);
            }
        }

        // Logic for "Sent to Client"
        if ($newStatus === 'sent') {
            if ($offer->requires_director_approval) {
                 // If it requires approval and user is not director/admin, set to pending_approval instead
                 if (!$user->hasRole('sales_director') && !$user->hasRole('admin')) {
                     $newStatus = 'pending_approval';
                 }
            }
        }

        // Logic for "Approved" (by Director)
        if ($newStatus === 'approved') {
            // If it was pending approval, director approves it -> it becomes 'sent' (to client) or 'approved' (internally)?
            // Usually: Director approves -> Offer is valid -> Status becomes 'sent' (ready for client)
            // OR: Director approves -> Status 'approved_internal' -> Agent sends.
            // Let's simplify: If Director approves a 'pending_approval' offer, it goes to 'sent' (active for client).
            
            if ($offer->status === 'pending_approval') {
                if (!$user->hasRole('sales_director') && !$user->hasRole('admin')) {
                    return response()->json(['message' => 'Unauthorized to approve derogation.'], 403);
                }
                $newStatus = 'sent'; // Now the client can see it
                $offer->requires_director_approval = false; // Cleared
            }
        }

        // Logic for "Accepted" (by Client)
        if ($newStatus === 'accepted') {
             // Maybe create an order here? Or just mark as accepted.
             // For now just status change.
        }

        $offer->status = $newStatus;
        $offer->save();

        if (in_array($newStatus, ['sent', 'approved', 'rejected', 'negotiation'])) {
             try {
                $offer->customer?->user?->notify(new OfferStatusChangedNotification($offer, 'customer'));
             } catch (\Exception $e) {
                 // ignore notification errors
             }

             // Notify Agent if Director Approved (sent) or Rejected
             if ($offer->agent_id && $offer->agent_id !== $user->id) {
                 if ($newStatus === 'sent' || $newStatus === 'rejected') {
                     try {
                        $offer->agent?->notify(new OfferStatusChangedNotification($offer, 'agent'));
                     } catch (\Exception $e) {}
                 }
             }
        }

        return response()->json($offer);
    }

    /**
     * Add Message (Negotiation)
     */
    public function addMessage(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
            'is_internal' => 'boolean'
        ]);

        $offer = Offer::findOrFail($id);
        
        $this->ensureOfferAccess($offer);

        $msg = OfferMessage::create([
            'offer_id' => $offer->id,
            'user_id' => Auth::id(),
            'message' => $request->message,
            'is_internal' => $request->is_internal ?? false,
        ]);

        // Auto-update status to negotiation if message is external
        if (!($request->is_internal ?? false)) {
             // If client writes, always negotiation
             // If agent writes (publicly), negotiation
             if ($offer->status !== 'draft' && $offer->status !== 'completed' && $offer->status !== 'rejected') {
                 $offer->status = 'negotiation';
                 $offer->save();
             }

             // Notify customer
             try {
                $offer->customer?->user?->notify(new OfferMessageNotification($offer, $msg, 'agent'));
             } catch (\Exception $e) {
                 // ignore
             }
        }
        
        return response()->json($msg->load('user'));
    }

    /**
     * Destroy
     */
    public function destroy($id)
    {
        $offer = Offer::findOrFail($id);
        
        $this->ensureOfferAccess($offer);

        if ($offer->status !== 'draft' && $offer->status !== 'rejected') {
             return response()->json(['message' => 'Doar ofertele draft sau respinse pot fi șterse.'], 400);
        }

        $offer->items()->delete();
        $offer->messages()->delete();
        $offer->delete();

        return response()->noContent();
    }

    private function ensureOfferAccess(Offer $offer)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return;
        }

        if ($user->hasRole('sales_director')) {
            if ($offer->agent_id === $user->id) {
                return;
            }
            
            $isSubordinate = User::where('id', $offer->agent_id)
                ->where('director_id', $user->id)
                ->exists();
                
            if ($isSubordinate) {
                return;
            }
        }

        if ($user->hasRole('sales_agent')) {
            if ($offer->agent_id === $user->id) {
                return;
            }
        }

        // For clients, ensure they only see their own offers
        if ($user->customer_id && $offer->customer_id === $user->customer_id) {
            // Clients should only see 'sent', 'approved', 'negotiation' etc (non-internal)
            if (in_array($offer->status, ['sent', 'approved', 'negotiation', 'rejected', 'completed', 'accepted'])) {
                return;
            }
        }

        abort(403, 'Unauthorized access to offer.');
    }
}
