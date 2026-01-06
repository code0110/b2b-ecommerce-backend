<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\ProductStockAlert;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductStockAlertController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Trebuie să fii autentificat pentru a primi alerte.'], 401);
        }

        $alert = ProductStockAlert::firstOrCreate(
            [
                'user_id' => $user->id,
                'product_id' => $request->product_id,
                'product_variant_id' => $request->product_variant_id,
            ],
            [
                'email' => $user->email,
            ]
        );

        // Reset notified_at if re-subscribing
        if ($alert->notified_at) {
            $alert->update(['notified_at' => null]);
        }

        return response()->json(['message' => 'Te-ai abonat cu succes la alerta de stoc.']);
    }

    public function checkStatus(Request $request)
    {
        $user = $request->user();
        if (!$user) return response()->json(['subscribed' => false]);

        $exists = ProductStockAlert::where('user_id', $user->id)
            ->where('product_id', $request->query('product_id'))
            ->when($request->query('product_variant_id'), function ($q, $v) {
                return $q->where('product_variant_id', $v);
            }, function ($q) {
                return $q->whereNull('product_variant_id');
            })
            ->whereNull('notified_at')
            ->exists();

        return response()->json(['subscribed' => $exists]);
    }
}
