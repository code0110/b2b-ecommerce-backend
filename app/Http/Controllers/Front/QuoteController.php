<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\QuoteRequest;
use App\Models\QuoteRequestItem;
use App\Models\Product;
use App\Models\Cart;
use App\Notifications\QuoteStatusChangedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return QuoteRequest::where('customer_id', $user->customer_id)
            ->withCount('items')
            ->orderByDesc('created_at')
            ->paginate(20);
    }

    public function show($id, Request $request)
    {
        $user = $request->user();

        $quote = QuoteRequest::where('customer_id', $user->customer_id)
            ->where('id', $id)
            ->with(['items.product', 'assignedAgent', 'offer' => function ($query) {
                $query->whereIn('status', ['sent', 'accepted', 'rejected', 'negotiation', 'approved']);
            }])
            ->firstOrFail();

        return $quote;
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'notes' => ['required', 'string', 'min:10'],
        ]);

        return DB::transaction(function () use ($user, $data) {
            $quote = QuoteRequest::create([
                'customer_id'        => $user->customer_id,
                'created_by_user_id' => $user->id,
                'assigned_agent_id'  => $user->customer?->agent_user_id,
                'status'             => 'new',
                'source'             => 'web_form',
                'estimated_total'    => 0,
                'offered_total'      => 0,
                'customer_notes'     => $data['notes'],
            ]);

            // Notify agent/admin (optional, can be done via Observer or Event)
            
            return response()->json($quote, 201);
        });
    }

    public function fromProduct(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity'   => ['required', 'numeric', 'min:0.01'],
            'notes'      => ['nullable', 'string'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $quantity = (float) $data['quantity'];

        return DB::transaction(function () use ($user, $product, $quantity, $data) {
            $estimated = ($product->list_price ?? 0) * $quantity;

            $quote = QuoteRequest::create([
                'customer_id'        => $user->customer_id,
                'created_by_user_id' => $user->id,
                'assigned_agent_id'  => $user->customer?->agent_user_id,
                'status'             => 'new',
                'source'             => 'product',
                'estimated_total'    => $estimated,
                'offered_total'      => 0,
                'customer_notes'     => $data['notes'] ?? null,
            ]);

            QuoteRequestItem::create([
                'quote_request_id' => $quote->id,
                'product_id'       => $product->id,
                'product_variant_id'=> null,
                'quantity'         => $quantity,
                'list_price'       => $product->list_price ?? 0,
                'requested_price'  => null,
            ]);

            return response()->json($quote->load('items.product'), 201);
        });
    }

    public function fromCart(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'notes' => ['nullable', 'string'],
        ]);

        $cart = Cart::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('items.product')
            ->firstOrFail();

        return DB::transaction(function () use ($user, $cart, $data) {
            $estimated = $cart->items->sum('total');

            $quote = QuoteRequest::create([
                'customer_id'        => $user->customer_id,
                'created_by_user_id' => $user->id,
                'assigned_agent_id'  => $user->customer?->agent_user_id,
                'status'             => 'new',
                'source'             => 'cart',
                'estimated_total'    => $estimated,
                'offered_total'      => 0,
                'customer_notes'     => $data['notes'] ?? null,
            ]);

            foreach ($cart->items as $item) {
                QuoteRequestItem::create([
                    'quote_request_id' => $quote->id,
                    'product_id'       => $item->product_id,
                    'product_variant_id'=> $item->product_variant_id,
                    'quantity'         => $item->quantity,
                    'list_price'       => $item->unit_price,
                    'requested_price'  => null,
                ]);
            }

            return response()->json($quote->load('items.product'), 201);
        });
    }
}
