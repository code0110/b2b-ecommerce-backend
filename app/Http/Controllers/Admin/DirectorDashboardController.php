<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Order;
use App\Models\Offer;
use App\Models\CustomerVisit;
use App\Models\Payment;
use Carbon\Carbon;

class DirectorDashboardController extends Controller
{
    public function summary(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        if ($user->hasRole('admin')) {
            $agentIds = User::role('sales_agent')->pluck('id')->toArray();
            // Optionally include directors too if they sell? 
            // For now, let's stick to sales_agents as "team" for admin.
        } else {
            // Get agents assigned to this director
            $agentIds = User::where('director_id', $user->id)->pluck('id')->toArray();
        }

        if (empty($agentIds)) {
            return response()->json([
                'today_sales' => 0,
                'month_sales' => 0,
                'today_visits' => 0,
                'active_visits' => 0,
                'agents_count' => 0
            ]);
        }

        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        // 1. Sales
        // We link orders to agents via `placed_by_user_id` or `customer_visit_id`. 
        // Ideally rely on `placed_by_user_id` for all orders placed by agent.
        $todaySales = Order::whereIn('placed_by_user_id', $agentIds)
            ->whereDate('created_at', $today)
            ->sum('total');

        $monthSales = Order::whereIn('placed_by_user_id', $agentIds)
            ->whereDate('created_at', '>=', $startOfMonth)
            ->sum('total');

        // 2. Visits
        $todayVisits = CustomerVisit::whereIn('agent_id', $agentIds)
            ->whereDate('start_time', $today)
            ->count();

        $activeVisits = CustomerVisit::whereIn('agent_id', $agentIds)
            ->where('status', 'in_progress')
            ->count();

        // 3. Pending Approvals (Offers)
        $pendingApprovals = Offer::whereIn('agent_id', $agentIds)
            ->where('status', 'pending_approval')
            ->count();

        return response()->json([
            'today_sales' => $todaySales,
            'month_sales' => $monthSales,
            'today_visits' => $todayVisits,
            'active_visits' => $activeVisits,
            'agents_count' => count($agentIds),
            'pending_approvals' => $pendingApprovals
        ]);
    }

    public function teamStatus(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $query = User::query();
        
        if ($user->hasRole('admin')) {
            $query->role('sales_agent');
        } else {
            $query->where('director_id', $user->id);
        }

        $agents = $query->with(['visits' => function($q) {
                $q->where('status', 'in_progress')
                  ->with('customer:id,name,cui');
            }])
            ->get()
            ->map(function($agent) {
                $activeVisit = $agent->visits->first();
                $lastVisit = null;
                if (!$activeVisit) {
                    $lastVisit = CustomerVisit::where('agent_id', $agent->id)
                        ->orderBy('end_time', 'desc')
                        ->first();
                }
                
                // Get today's stats for this agent
                $todayVisitsCount = CustomerVisit::where('agent_id', $agent->id)
                    ->whereDate('start_time', Carbon::today())
                    ->count();
                
                $todaySales = Order::where('placed_by_user_id', $agent->id)
                    ->whereDate('created_at', Carbon::today())
                    ->sum('total');

                return [
                    'id' => $agent->id,
                    'name' => $agent->first_name . ' ' . $agent->last_name,
                    'status' => $activeVisit ? 'in_visit' : 'idle',
                    'current_customer' => $activeVisit && $activeVisit->customer ? $activeVisit->customer->name : null,
                    'visit_start_time' => $activeVisit ? $activeVisit->start_time : null,
                    'latitude' => $activeVisit ? $activeVisit->latitude : ($lastVisit ? ($lastVisit->end_latitude ?? $lastVisit->latitude) : null),
                    'longitude' => $activeVisit ? $activeVisit->longitude : ($lastVisit ? ($lastVisit->end_longitude ?? $lastVisit->longitude) : null),
                    'last_seen' => $activeVisit ? $activeVisit->start_time : ($lastVisit ? $lastVisit->end_time : null),
                    'is_off_site' => $activeVisit ? $activeVisit->is_off_site : null,
                    'today_visits' => $todayVisitsCount,
                    'today_sales' => $todaySales
                ];
            });

        return response()->json($agents);
    }
}
