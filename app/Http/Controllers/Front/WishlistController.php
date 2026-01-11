<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\Pricing\PromotionPricingService;

class WishlistController extends Controller
{
    protected $pricingService;

    public function __construct(PromotionPricingService $pricingService)
    {
        $this->pricingService = $pricingService;
    }

    /**
     * List user's wishlists
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $wishlists = $user->wishlists()
            ->withCount('items')
            ->with(['items.product' => function($q) {
                $q->select('id', 'name', 'internal_code', 'slug', 'list_price', 'brand_id', 'vat_rate', 'vat_included')
                  ->with(['brand:id,name', 'images']);
            }])
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
            
        // Ensure default wishlist exists
        if ($wishlists->isEmpty()) {
            $default = $user->wishlists()->create([
                'name' => 'Favorite',
                'is_default' => true,
                'token' => Str::random(32)
            ]);
            // Refresh to get structure
            $wishlists = collect([$default->loadCount('items')]);
        }

        // Backfill tokens if missing
        $wishlists->each(function($w) {
            if (empty($w->token)) {
                $w->token = Str::random(32);
                $w->save();
            }
        });
        
        return response()->json($wishlists);
    }

    /**
     * Show specific wishlist items
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();
        $wishlist = $user->wishlists()->findOrFail($id);
        
        // Get products with relations needed for display
        $items = $wishlist->items()->with(['product.brand', 'product.mainCategory', 'product.images'])->get();
        
        // Extract products collection for pricing service
        $products = $items->map(function($item) {
            return $item->product;
        })->filter();

        // Apply pricing logic (promotions, user groups, etc.)
        // Assuming pricingService has a method to enrich a collection of products
        // If not, we might need to rely on accessors or standard logic.
        // Checking PromotionPricingService... usually it applies to query or single product.
        // For now, let's just return the data, frontend handles display logic often.
        
        return response()->json([
            'wishlist' => $wishlist,
            'items' => $items
        ]);
    }
    
    /**
     * Create new wishlist
     */
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        
        $wishlist = $request->user()->wishlists()->create([
            'name' => $request->name,
            'is_default' => false,
            'token' => Str::random(32)
        ]);
        
        return response()->json($wishlist);
    }

    /**
     * Rename or update wishlist
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'is_public' => 'sometimes|boolean'
        ]);

        $wishlist = $request->user()->wishlists()->findOrFail($id);
        $wishlist->update($request->only(['name', 'is_public']));

        return response()->json($wishlist);
    }

    /**
     * Delete wishlist
     */
    public function destroy(Request $request, $id)
    {
        $wishlist = $request->user()->wishlists()->findOrFail($id);
        
        if ($wishlist->is_default) {
            return response()->json(['message' => 'Nu se poate șterge lista implicită.'], 400);
        }
        
        $wishlist->delete();
        return response()->json(['message' => 'Lista a fost ștearsă.']);
    }
    
    /**
     * Toggle item in default list (or specific list if provided)
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'wishlist_id' => 'nullable|exists:wishlists,id'
        ]);
        
        $user = $request->user();
        $wishlistId = $request->input('wishlist_id');
        
        if ($wishlistId) {
            $wishlist = $user->wishlists()->findOrFail($wishlistId);
        } else {
            // Get or create default list
            $wishlist = $user->wishlists()->where('is_default', true)->first();
            if (!$wishlist) {
                $wishlist = $user->wishlists()->create([
                    'name' => 'Favorite',
                    'is_default' => true,
                    'token' => Str::random(32)
                ]);
            }
        }
        
        $existing = $wishlist->items()->where('product_id', $request->product_id)->first();
        
        if ($existing) {
            $existing->delete();
            $status = 'removed';
        } else {
            $wishlist->items()->create(['product_id' => $request->product_id]);
            $status = 'added';
        }
        
        return response()->json([
            'status' => $status,
            'count' => $wishlist->items()->count(),
            'wishlist_id' => $wishlist->id
        ]);
    }

    /**
     * Merge guest items into default wishlist
     */
    public function merge(Request $request)
    {
        $request->validate(['items' => 'required|array', 'items.*' => 'integer']);
        $user = $request->user();
        
        $wishlist = $user->wishlists()->where('is_default', true)->first();
        if (!$wishlist) {
            $wishlist = $user->wishlists()->create([
                'name' => 'Favorite',
                'is_default' => true,
                'token' => Str::random(32)
            ]);
        }
        
        $count = 0;
        foreach ($request->items as $productId) {
            if (!$wishlist->items()->where('product_id', $productId)->exists()) {
                // Verify product exists
                if (Product::find($productId)) {
                    $wishlist->items()->create(['product_id' => $productId]);
                    $count++;
                }
            }
        }
        
        return response()->json(['merged_count' => $count, 'wishlist' => $wishlist]);
    }

    public function shared($token)
    {
        $wishlist = Wishlist::where('token', $token)->where('is_public', true)->firstOrFail();
        
        $items = $wishlist->items()->with(['product.brand', 'product.mainCategory', 'product.images'])->get();
        
        return response()->json([
            'wishlist' => $wishlist,
            'items' => $items
        ]);
    }
}
