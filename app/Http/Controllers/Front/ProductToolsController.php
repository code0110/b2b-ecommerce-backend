<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\RecentlyViewedProduct;
use App\Models\ProductComparison;
use Illuminate\Http\Request;

class ProductToolsController extends Controller
{
    protected function resolveSessionKey(Request $request): ?string
    {
        $key = $request->header('X-Session-Key') ?: $request->header('X-Cart-Session');
        
        if (!$key) {
            // Fallback to Laravel session ID (consistent for API if stateful, or just current request session)
            // This ensures we always have a key for guest operations even if headers are missing
            $key = session()->getId();
        }
        
        return $key;
    }

    public function trackView($productId, Request $request)
    {
        $user = $request->user();
        $sessionKey = $this->resolveSessionKey($request);

        if (!$user && !$sessionKey) {
            // dacă nu avem nici user, nici sessionKey, nu înregistrăm
            return response()->json(['message' => 'No context for tracking.'], 200);
        }

        Product::findOrFail($productId);

        RecentlyViewedProduct::updateOrCreate(
            [
                'user_id'     => $user?->id,
                'session_key' => $user ? null : $sessionKey,
                'product_id'  => $productId,
            ],
            [
                'viewed_at' => now(),
            ]
        );

        return response()->json(['message' => 'Tracked.']);
    }

    public function recentlyViewed(Request $request)
    {
        $user = $request->user();
        $sessionKey = $this->resolveSessionKey($request);

        $query = RecentlyViewedProduct::with('product');

        if ($user) {
            $query->where('user_id', $user->id);
        } elseif ($sessionKey) {
            $query->where('session_key', $sessionKey);
        } else {
            return response()->json([]);
        }

        $items = $query
            ->orderByDesc('viewed_at')
            ->limit((int) $request->get('limit', 10))
            ->get()
            ->pluck('product');

        return $items;
    }

    // --- Compară produse ---

    public function comparisonList(Request $request)
    {
        try {
            $user = $request->user();
            $sessionKey = $this->resolveSessionKey($request);

            if (!$user && !$sessionKey) {
                // Return empty if no user and no session key
                return response()->json([
                    'products' => [],
                    'all_attributes' => []
                ]);
            }

            $query = ProductComparison::with(['product.brand', 'product.mainCategory']);

            if ($user) {
                $query->where('user_id', $user->id);
            } elseif ($sessionKey) {
                $query->where('session_key', $sessionKey);
            }

            $products = $query->get()->pluck('product')->filter();

            // Extract all unique attribute keys from technical_specs
            $allAttributes = collect();
            foreach ($products as $product) {
                if ($product && !empty($product->technical_specs) && is_array($product->technical_specs)) {
                    foreach (array_keys($product->technical_specs) as $key) {
                        $allAttributes->push($key);
                    }
                }
            }
            $allAttributes = $allAttributes->unique()->values();

            return response()->json([
                'products' => $products->values(),
                'all_attributes' => $allAttributes
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Comparison load error: ' . $e->getMessage());
            return response()->json(['error' => 'Server Error'], 500);
        }
    }

    public function addToComparison(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        $user = $request->user();
        $sessionKey = $this->resolveSessionKey($request);

        if (!$user && !$sessionKey) {
            return response()->json(['message' => 'No context.'], 422);
        }

        ProductComparison::firstOrCreate(
            [
                'user_id'     => $user?->id,
                'session_key' => $user ? null : $sessionKey,
                'product_id'  => $data['product_id'],
            ]
        );

        return response()->json(['message' => 'Added to comparison.']);
    }

    public function removeFromComparison($productId, Request $request)
    {
        $user = $request->user();
        $sessionKey = $this->resolveSessionKey($request);

        if (!$user && !$sessionKey) {
            return response()->json(['message' => 'No context.'], 422);
        }

        $query = ProductComparison::where('product_id', $productId);

        if ($user) {
            $query->where('user_id', $user->id);
        } elseif ($sessionKey) {
            $query->where('session_key', $sessionKey);
        }

        $query->delete();

        return response()->json(['message' => 'Removed from comparison.']);
    }
}
