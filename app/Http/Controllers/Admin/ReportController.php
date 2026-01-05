<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerVisit;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function dashboardStats(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());
        $agentId = $request->get('agent_id');

        // Refine filtering logic
        $user = Auth::user();
        $allowedAgentIds = $this->getAllowedAgentIds($user);
        
        if ($agentId && in_array($agentId, $allowedAgentIds)) {
             $targetAgents = [$agentId];
        } else {
             $targetAgents = $allowedAgentIds;
        }

        // Visits
        $visits = CustomerVisit::whereBetween('start_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereIn('agent_id', $targetAgents);
            
        $totalVisits = $visits->count();
        $completedVisits = (clone $visits)->where('status', 'completed')->count();
        $visitsWithOrders = (clone $visits)->whereHas('orders')->count();
        $visitsWithPayments = (clone $visits)->whereHas('payments')->count();

        // Orders from visits
        $ordersQuery = Order::whereIn('customer_visit_id', $visits->select('id'));
        $ordersValue = $ordersQuery->sum('grand_total');
        $ordersCount = $ordersQuery->count();

        // Payments from visits
        $paymentsValue = Payment::whereIn('customer_visit_id', $visits->select('id'))
            ->sum('amount');

        return response()->json([
            'total_visits' => $totalVisits,
            'completed_visits' => $completedVisits,
            'visits_with_orders' => $visitsWithOrders,
            'visits_with_payments' => $visitsWithPayments,
            'orders_value' => $ordersValue,
            'orders_count' => $ordersCount,
            'payments_value' => $paymentsValue,
        ]);
    }

    public function outcomesChart(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());
        $agentId = $request->get('agent_id');

        $user = Auth::user();
        $allowedAgentIds = $this->getAllowedAgentIds($user);
        
        if ($agentId && in_array($agentId, $allowedAgentIds)) {
             $targetAgents = [$agentId];
        } else {
             $targetAgents = $allowedAgentIds;
        }

        $outcomes = CustomerVisit::whereBetween('start_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereIn('agent_id', $targetAgents)
            ->where('status', 'completed')
            ->select('outcome', DB::raw('count(*) as count'))
            ->groupBy('outcome')
            ->get();

        return response()->json($outcomes);
    }

    public function visitsChart(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());
        $agentId = $request->get('agent_id');

        $user = Auth::user();
        $allowedAgentIds = $this->getAllowedAgentIds($user);
        
        if ($agentId && in_array($agentId, $allowedAgentIds)) {
             $targetAgents = [$agentId];
        } else {
             $targetAgents = $allowedAgentIds;
        }

        $visits = CustomerVisit::whereBetween('start_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->whereIn('agent_id', $targetAgents)
            ->select(DB::raw('DATE(start_time) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json($visits);
    }

    public function agentPerformance(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());
        
        $user = Auth::user();
        $allowedAgentIds = $this->getAllowedAgentIds($user);

        $agents = User::whereIn('id', $allowedAgentIds)
            ->withCount(['visits' => function($q) use ($startDate, $endDate) {
                $q->whereBetween('start_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            }])
            ->get()
            ->map(function($agent) use ($startDate, $endDate) {
                // Calculate Order Value and Payment Value via Visits
                $visitIds = CustomerVisit::where('agent_id', $agent->id)
                    ->whereBetween('start_time', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                    ->pluck('id');

                $ordersValue = Order::whereIn('customer_visit_id', $visitIds)->sum('grand_total');
                $paymentsValue = Payment::whereIn('customer_visit_id', $visitIds)->sum('amount');

                // Get Target for the period (assuming single month selection for simplicity, or sum of targets)
                // If date range spans multiple months, this is complex. Let's try to match the start date month.
                $start = \Carbon\Carbon::parse($startDate);
                $target = \App\Models\SalesTarget::where('user_id', $agent->id)
                    ->where('year', $start->year)
                    ->where('month', $start->month)
                    ->first();

                return [
                    'id' => $agent->id,
                    'name' => $agent->first_name . ' ' . $agent->last_name,
                    'visits_count' => $agent->visits_count,
                    'orders_value' => $ordersValue,
                    'payments_value' => $paymentsValue,
                    'target_sales' => $target ? $target->target_sales_amount : 0,
                    'target_visits' => $target ? $target->target_visits_count : 0,
                ];
            });

        return response()->json($agents);
    }

    private function getAllowedAgentIds($user)
    {
        if ($user->hasRole('admin')) {
            return User::role('sales_agent')->pluck('id')->toArray();
        }
        
        if ($user->hasRole('sales_director')) {
            return $user->subordinates()->pluck('id')->toArray();
        }

        if ($user->hasRole('sales_agent')) {
            return [$user->id];
        }

        return [];
    }

    private function applyAgentFilter($query, $agentId, $column = 'agent_id')
    {
        // Deprecated by manual filtering logic above, but kept for reference if needed
        if ($agentId) {
            $query->where($column, $agentId);
        }
    }
}
