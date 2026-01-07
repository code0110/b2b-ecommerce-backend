<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountRule;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class DiscountRuleController extends Controller
{
    public function index()
    {
        return DiscountRule::orderBy('id', 'desc')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rule_type' => 'required|in:approval_threshold,max_discount',
            'target_type' => 'required|in:global,role,user',
            'target_id' => 'nullable|integer',
            'limit_percent' => 'required|numeric|min:0|max:100',
            'apply_to_total' => 'boolean',
            'active' => 'boolean',
        ]);

        return DiscountRule::create($validated);
    }

    public function update(Request $request, DiscountRule $discountRule)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rule_type' => 'required|in:approval_threshold,max_discount',
            'target_type' => 'required|in:global,role,user',
            'target_id' => 'nullable|integer',
            'limit_percent' => 'required|numeric|min:0|max:100',
            'apply_to_total' => 'boolean',
            'active' => 'boolean',
        ]);

        $discountRule->update($validated);
        return $discountRule;
    }

    public function destroy(DiscountRule $discountRule)
    {
        $discountRule->delete();
        return response()->noContent();
    }

    public function options()
    {
        return response()->json([
            'roles' => Role::select('id', 'name')->get(),
            'users' => User::select('id', 'first_name', 'last_name', 'email')
                ->get()
                ->map(function($u) {
                    return [
                        'id' => $u->id,
                        'name' => $u->first_name . ' ' . $u->last_name . ' (' . $u->email . ')'
                    ];
                })
        ]);
    }
}
