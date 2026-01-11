<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\FinancialRiskService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinancialRiskController extends Controller
{
    protected $riskService;

    public function __construct(FinancialRiskService $riskService)
    {
        $this->riskService = $riskService;
    }

    public function check(Customer $customer)
    {
        $this->ensureAccess($customer);

        $risk = $this->riskService->calculateRisk($customer);
        return response()->json($risk);
    }

    public function acknowledge(Request $request, Customer $customer)
    {
        $this->ensureAccess($customer);
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check permission: Director or Admin
        if (!$user->hasRole('admin') && !$user->hasRole('sales_director')) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->riskService->acknowledgeRisk($customer, $user);

        return response()->json(['message' => 'Notificarea a fost confirmată.']);
    }

    public function grantDerogation(Request $request, Customer $customer)
    {
        $this->ensureAccess($customer);
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check permission: Director or Admin
        if (!$user->hasRole('admin') && !$user->hasRole('sales_director')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'valid_until' => 'required|date|after:now',
            'reason' => 'nullable|string'
        ]);

        $until = Carbon::parse($validated['valid_until']);
        $reason = $validated['reason'] ?? null;

        $this->riskService->grantDerogation($customer, $user, $until, $reason);

        return response()->json(['message' => 'Derogarea a fost acordată cu succes.']);
    }

    private function ensureAccess(Customer $customer)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->hasRole('admin')) return;

        if ($user->hasRole('sales_director')) {
             if ($customer->sales_director_user_id == $user->id) return;
             if ($customer->agent_user_id == $user->id) return;
             
             if ($customer->agent_user_id) {
                 $isSubordinate = \App\Models\User::where('id', $customer->agent_user_id)
                     ->where('director_id', $user->id)
                     ->exists();
                 if ($isSubordinate) return;
             }
             
             abort(403, 'Unauthorized access to customer.');
        }

        if ($user->hasRole('sales_agent')) {
            if ($customer->agent_user_id == $user->id) return;
            
            if ($customer->teamMembers()->where('users.id', $user->id)->exists()) return;
            
            abort(403, 'Unauthorized access to customer.');
        }
    }
}
