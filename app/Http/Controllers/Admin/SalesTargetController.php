<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SalesTarget;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class SalesTargetController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('n'));
        $userId = $request->input('user_id');

        $query = SalesTarget::with(['user', 'items'])
            ->where('year', $year)
            ->where('month', $month);

        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        // RBAC: Director sees only his agents, Agent sees only himself
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        
        if ($currentUser->hasRole('sales_agent')) {
            $query->where('user_id', $currentUser->id);
        } elseif ($currentUser->hasRole('sales_director') && !$currentUser->hasRole('admin')) {
             // Find agents where director_id = current user OR it is the director themselves
             $agentIds = User::where('director_id', $currentUser->id)->pluck('id')->toArray();
             $agentIds[] = $currentUser->id;
             $query->whereIn('user_id', $agentIds);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        if (!$currentUser->hasRole(['admin', 'sales_director'])) {
             return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'year' => 'required|integer|min:2025',
            'month' => 'required|integer|min:1|max:12',
            'target_sales_amount' => 'required|numeric|min:0',
            'target_visits_count' => 'required|integer|min:0',
            'target_new_customers' => 'nullable|integer|min:0',
            'items' => 'nullable|array',
            'items.*.target_type' => 'required|string|in:category',
            'items.*.target_id' => 'required|integer',
            'items.*.target_amount' => 'required|numeric|min:0',
        ]);

        // RBAC: Director can only set targets for their subordinates
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if ($currentUser->hasRole('sales_director') && !$currentUser->hasRole('admin')) {
             $isSubordinate = User::where('id', $request->user_id)
                                  ->where('director_id', $currentUser->id)
                                  ->exists();
             if (!$isSubordinate) {
                 return response()->json(['message' => 'Unauthorized: You can only set targets for your assigned agents.'], 403);
             }
        }

        $target = SalesTarget::updateOrCreate(
            [
                'user_id' => $request->user_id,
                'year' => $request->year,
                'month' => $request->month,
            ],
            [
                'target_sales_amount' => $request->target_sales_amount,
                'target_visits_count' => $request->target_visits_count,
                'target_new_customers' => $request->target_new_customers ?? 0,
            ]
        );

        if ($request->has('items')) {
            $target->items()->delete();
            foreach ($request->items as $item) {
                $target->items()->create([
                    'target_type' => $item['target_type'],
                    'target_id' => $item['target_id'],
                    'target_amount' => $item['target_amount']
                ]);
            }
        }

        return response()->json($target->load('items'));
    }
    
    public function show($id)
    {
        return SalesTarget::findOrFail($id);
    }

    public function destroy($id)
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        if (!$currentUser->hasRole(['admin', 'sales_director'])) {
             return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $target = SalesTarget::findOrFail($id);

        if ($currentUser->hasRole('sales_director') && !$currentUser->hasRole('admin')) {
             $isSubordinate = User::where('id', $target->user_id)
                                  ->where('director_id', $currentUser->id)
                                  ->exists();
             if (!$isSubordinate) {
                 return response()->json(['message' => 'Unauthorized.'], 403);
             }
        }

        $target->delete();
        return response()->noContent();
    }
}
