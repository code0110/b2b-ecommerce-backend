<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\OfferMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Notifications\OfferStatusChangedNotification; // Assuming this exists or generic notification
use App\Notifications\OfferMessageNotification;

class OfferController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->customer_id) {
            return response()->json(['data' => []]);
        }

        $query = Offer::where('customer_id', $user->customer_id)
            ->whereIn('status', ['sent', 'accepted', 'rejected', 'negotiation'])
            ->with(['agent', 'items.product'])
            ->orderByDesc('created_at');

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        return $query->paginate(20);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $offer = Offer::where('customer_id', $user->customer_id)
            ->where('id', $id)
            ->whereIn('status', ['sent', 'accepted', 'rejected', 'negotiation'])
            ->with(['agent', 'items.product', 'messages.user'])
            ->firstOrFail();

        return response()->json($offer);
    }

    public function changeStatus(Request $request, $id)
    {
        $user = $request->user();
        $offer = Offer::where('customer_id', $user->customer_id)
            ->where('id', $id)
            ->firstOrFail();

        $data = $request->validate([
            'status' => ['required', 'in:accepted,rejected,negotiation']
        ]);

        return DB::transaction(function () use ($offer, $data) {
            $offer->status = $data['status'];
            $offer->save();

            // Notify agent
            try {
                $offer->agent?->notify(new OfferStatusChangedNotification($offer, 'agent'));
            } catch (\Exception $e) {
                // ignore
            }

            return response()->json($offer);
        });
    }

    public function addMessage(Request $request, $id)
    {
        $user = $request->user();
        $offer = Offer::where('customer_id', $user->customer_id)
            ->where('id', $id)
            ->firstOrFail();

        $data = $request->validate([
            'message' => ['required', 'string', 'min:1']
        ]);

        $message = OfferMessage::create([
            'offer_id' => $offer->id,
            'user_id' => $user->id,
            'message' => $data['message'],
            'is_internal' => false
        ]);

        // If client sends message, status -> negotiation (if not already accepted/rejected/completed)
        if (!in_array($offer->status, ['accepted', 'rejected', 'completed'])) {
            $offer->status = 'negotiation';
            $offer->save();
        }

        // Notify agent
        try {
            $offer->agent?->notify(new OfferMessageNotification($offer, $message, 'customer'));
        } catch (\Exception $e) {
            // ignore
        }

        return response()->json($message->load('user'));
    }
}
