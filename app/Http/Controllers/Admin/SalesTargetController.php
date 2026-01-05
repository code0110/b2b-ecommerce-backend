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

        $query = SalesTarget::with('user')
            ->where('year', $year)
            ->where('month', $month);

        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        // RBAC: Director sees only his agents
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        if ($currentUser->hasRole('sales_director') && !$currentUser->hasRole('admin')) {
             // Find agents where director_id = current user
             $agentIds = User::where('director_id', $currentUser->id)->pluck('id');
             $query->whereIn('user_id', $agentIds);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'year' => 'required|integer|min:2025',
            'month' => 'required|integer|min:1|max:12',
            'target_sales_amount' => 'required|numeric|min:0',
            'target_visits_count' => 'required|integer|min:0',
            'target_new_customers' => 'nullable|integer|min:0'
        ]);

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

        return response()->json($target);
    }
    
    public function show($id)
    {
        return SalesTarget::findOrFail($id);
    }

    public function destroy($id)
    {
        SalesTarget::findOrFail($id)->delete();
        return response()->noContent();
    }
}
