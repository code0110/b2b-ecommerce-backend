<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use App\Models\QuoteRequestItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Notifications\QuoteStatusChangedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $query = QuoteRequest::with(['customer', 'assignedAgent', 'offer']);

        // RBAC Filtering
        if ($user->hasRole('sales_agent')) {
            // Agent sees requests assigned to them OR from their customers
            $query->where(function ($q) use ($user) {
                $q->where('assigned_agent_id', $user->id)
                  ->orWhereHas('customer', function ($c) use ($user) {
                      $c->where('agent_user_id', $user->id);
                  });
            });
        } elseif ($user->hasRole('sales_director')) {
            // Director sees requests from their team
            $subordinateIds = \App\Models\User::where('director_id', $user->id)->pluck('id');
            $query->where(function ($q) use ($user, $subordinateIds) {
                $q->where('assigned_agent_id', $user->id)
                  ->orWhereIn('assigned_agent_id', $subordinateIds)
                  ->orWhereHas('customer', function ($c) use ($user, $subordinateIds) {
                      $c->where('agent_user_id', $user->id)
                        ->orWhereIn('agent_user_id', $subordinateIds);
                  });
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($agentId = $request->get('assigned_agent_id')) {
            $query->where('assigned_agent_id', $agentId);
        }

        return $query->orderByDesc('created_at')->paginate(25);
    }

    public function show(QuoteRequest $quoteRequest)
    {
        $this->ensureQuoteAccess($quoteRequest);
        return $quoteRequest->load('customer', 'items.product', 'assignedAgent', 'createdBy');
    }

    public function update(Request $request, QuoteRequest $quoteRequest)
    {
        $this->ensureQuoteAccess($quoteRequest);
        $data = $request->validate([
            'status'                        => ['sometimes', 'string', 'max:50'],
            'valid_until'                   => ['nullable', 'date'],
            'internal_notes'                => ['nullable', 'string'],
            'assigned_agent_id'             => ['nullable', 'integer'],
            'items'                         => ['nullable', 'array'],
            'items.*.id'                    => ['required_with:items', 'integer'],
            'items.*.offered_price'        => ['nullable', 'numeric', 'min:0'],
        ]);

        return DB::transaction(function () use ($quoteRequest, $data) {
            if (isset($data['items'])) {
                foreach ($data['items'] as $row) {
                    $item = QuoteRequestItem::where('quote_request_id', $quoteRequest->id)
                        ->where('id', $row['id'])
                        ->first();

                    if ($item && array_key_exists('offered_price', $row)) {
                        $item->offered_price = $row['offered_price'];
                        $item->save();
                    }
                }

                $quoteRequest->offered_total = $quoteRequest->items()->sum(DB::raw('quantity * COALESCE(offered_price, list_price)'));
            }

            if (isset($data['status'])) {
                $quoteRequest->status = $data['status'];
            }

            if (isset($data['valid_until'])) {
                $quoteRequest->valid_until = $data['valid_until'];
            }

            if (isset($data['internal_notes'])) {
                $quoteRequest->internal_notes = $data['internal_notes'];
            }

            if (isset($data['assigned_agent_id'])) {
                $quoteRequest->assigned_agent_id = $data['assigned_agent_id'];
            }

            $quoteRequest->save();

            // notificăm clientul la schimbare status
            $quoteRequest->customer->user?->notify(new QuoteStatusChangedNotification($quoteRequest));

            return response()->json($quoteRequest->load('items.product'));
        });
    }

    public function convertToOrder(QuoteRequest $quoteRequest, Request $request)
    {
        $this->ensureQuoteAccess($quoteRequest);
        $data = $request->validate([
            'payment_method'       => ['required', 'in:card,op,b2b_terms'],
            'shipping_method_id'   => ['required', 'integer', 'exists:shipping_methods,id'],
            'billing_address_id'   => ['required', 'integer', 'exists:addresses,id'],
            'shipping_address_id'  => ['required', 'integer', 'exists:addresses,id'],
        ]);

        return DB::transaction(function () use ($quoteRequest, $data) {
            $customer = $quoteRequest->customer;
            $user     = $quoteRequest->createdBy;

            $subtotal = 0;
            foreach ($quoteRequest->items as $item) {
                $price = $item->offered_price ?? $item->list_price;
                $subtotal += $item->quantity * $price;
            }

            $shippingTotal = 0; // TODO: calcul din reguli
            $grandTotal = $subtotal + $shippingTotal;

            $order = Order::create([
                'order_number'       => 'Q' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
                'customer_id'        => $customer->id,
                'placed_by_user_id'  => $user?->id,
                'status'             => 'pending',
                'type'               => $customer->type ?? 'b2b',
                'total_items'        => $quoteRequest->items->sum('quantity'),
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

            foreach ($quoteRequest->items as $item) {
                $price = $item->offered_price ?? $item->list_price;

                OrderItem::create([
                    'order_id'          => $order->id,
                    'product_id'        => $item->product_id,
                    'product_variant_id'=> $item->product_variant_id,
                    'product_name'      => $item->product?->name,
                    'sku'               => $item->product?->internal_code,
                    'quantity'          => $item->quantity,
                    'unit_price'        => $price,
                    'discount_amount'   => 0,
                    'tax_amount'        => 0,
                    'total'             => $item->quantity * $price,
                ]);
            }

            // marcăm oferta ca "approved"
            $quoteRequest->status = 'approved';
            $quoteRequest->save();

            return response()->json($order->load('items'), 201);
        });
    }

    private function ensureQuoteAccess(QuoteRequest $quoteRequest)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return;
        }

        if ($user->hasRole('sales_agent')) {
            // Agent sees requests assigned to them OR from their customers
            $isAssigned = $quoteRequest->assigned_agent_id === $user->id;
            $isCustomerAgent = $quoteRequest->customer && $quoteRequest->customer->agent_user_id === $user->id;
            
            if (!$isAssigned && !$isCustomerAgent) {
                 abort(403, 'Unauthorized access to quote request.');
            }
        } elseif ($user->hasRole('sales_director')) {
            // Director sees requests from their team
            $subordinateIds = \App\Models\User::where('director_id', $user->id)->pluck('id');
            $subordinateIds->push($user->id); // Include self
            
            $isAssigned = $subordinateIds->contains($quoteRequest->assigned_agent_id);
            $isCustomerAgent = $quoteRequest->customer && $subordinateIds->contains($quoteRequest->customer->agent_user_id);

            if (!$isAssigned && !$isCustomerAgent) {
                 abort(403, 'Unauthorized access to quote request.');
            }
        } else {
             // Client access
             if ($quoteRequest->customer_id !== $user->customer_id) {
                 abort(403, 'Unauthorized access to quote request.');
             }
        }
    }
}
