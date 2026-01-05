<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerVisit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerVisitController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        $query = CustomerVisit::with(['customer', 'agent', 'orders', 'payments'])->orderByDesc('created_at');

        if ($user->hasRole('sales_agent')) {
            $query->where('agent_id', $user->id);
        } elseif ($user->hasRole('sales_director')) {
            $subordinates = $user->subordinates()->pluck('id');
            $query->whereIn('agent_id', $subordinates);
        }

        if ($agentId = $request->get('agent_id')) {
            $query->where('agent_id', $agentId);
        }
        
        if ($customerId = $request->get('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($date = $request->get('date')) {
            $query->whereDate('start_time', $date);
        }

        return response()->json($query->paginate(request('per_page', 20)));
    }

    public function startVisit(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'latitude'    => ['nullable', 'numeric'],
            'longitude'   => ['nullable', 'numeric'],
        ]);

        /** @var User $user */
        $user = Auth::user();
        
        // Close any ongoing visits for this user
        CustomerVisit::where('agent_id', $user->id)
            ->where('status', 'in_progress')
            ->update(['status' => 'completed', 'end_time' => now()]);

        $visit = CustomerVisit::create([
            'agent_id'    => $user->id,
            'customer_id' => $data['customer_id'],
            'start_time'  => now(),
            'status'      => 'in_progress',
            'latitude'    => $data['latitude'] ?? null,
            'longitude'   => $data['longitude'] ?? null,
        ]);

        return response()->json($visit, 201);
    }

    public function endVisit(Request $request, $id)
    {
        $visit = CustomerVisit::findOrFail($id);
        
        /** @var User $user */
        $user = Auth::user();

        if ($visit->agent_id !== $user->id && !$user->hasRole('admin')) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'notes' => ['nullable', 'string'],
            'outcome' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $visit->update([
            'status'    => 'completed',
            'end_time'  => now(),
            'notes'     => $data['notes'] ?? $visit->notes,
            'outcome'   => $data['outcome'] ?? null,
             // Update location on end if provided? Maybe distinct exit location
        ]);

        return response()->json($visit);
    }
    
    // Generic store/update if needed for admins
}
