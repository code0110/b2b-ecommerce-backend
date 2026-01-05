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

    public function locations(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $allowedAgentIds = $this->getAllowedAgentIds($user);

        // Include directors in the list if user is admin
        $agentIds = $allowedAgentIds;
        if ($user->hasRole('admin')) {
             // Add directors to the list if we want to track them too, 
             // but user request said "locatia agentilor/ directorilor".
             // If directors perform visits, they act as agents.
             // If we want to see directors' locations even if they don't do visits?
             // Usually tracking is via visits.
             // Let's stick to users who have visits.
             $directorIds = User::role('sales_director')->pluck('id')->toArray();
             $agentIds = array_merge($agentIds, $directorIds);
             $agentIds = array_unique($agentIds);
        }

        $agents = User::whereIn('id', $agentIds)
            ->select('id', 'first_name', 'last_name')
            ->with('roles')
            ->get()
                ->map(function ($agent) {
                    $activeVisit = CustomerVisit::where('agent_id', $agent->id)
                        ->where('status', 'in_progress')
                        ->with(['customer:id,name,latitude,longitude', 'locationLogs' => function($q) {
                            $q->latest()->limit(1);
                        }])
                        ->first();

                    $lastVisit = null;
                    if (!$activeVisit) {
                        $lastVisit = CustomerVisit::where('agent_id', $agent->id)
                            ->orderBy('end_time', 'desc')
                            ->with('customer:id,name,latitude,longitude')
                            ->first();
                    }

                    // Determine accurate current location and time
                    $currentLat = null;
                    $currentLng = null;
                    $lastSeen = null;
                    $speed = null;
                    $batteryLevel = null;
                    $networkType = null;
                    $accuracy = null;

                    if ($activeVisit) {
                        $lastLog = $activeVisit->locationLogs->first();
                        if ($lastLog) {
                            $currentLat = $lastLog->latitude;
                            $currentLng = $lastLog->longitude;
                            $lastSeen = $lastLog->recorded_at;
                            $speed = $lastLog->speed;
                            $batteryLevel = $lastLog->battery_level;
                            $networkType = $lastLog->network_type;
                            $accuracy = $lastLog->accuracy;
                        } else {
                            $currentLat = $activeVisit->latitude;
                            $currentLng = $activeVisit->longitude;
                            $lastSeen = $activeVisit->updated_at;
                        }
                    } elseif ($lastVisit) {
                        $currentLat = $lastVisit->end_latitude ?? $lastVisit->latitude;
                        $currentLng = $lastVisit->end_longitude ?? $lastVisit->longitude;
                        $lastSeen = $lastVisit->end_time;
                    }

                    return [
                        'id' => $agent->id,
                        'name' => $agent->first_name . ' ' . $agent->last_name,
                        'roles' => $agent->getRoleNames(),
                        'status' => $activeVisit ? 'in_visit' : 'idle',
                        'customer_name' => $activeVisit ? $activeVisit->customer->name : ($lastVisit ? $lastVisit->customer->name : null),
                        'visit_start_time' => $activeVisit ? $activeVisit->start_time : null,
                        'latitude' => $currentLat,
                        'longitude' => $currentLng,
                        'last_seen' => $lastSeen,
                        'is_off_site' => $activeVisit ? $activeVisit->is_off_site : null,
                        'distance_deviation' => $activeVisit ? $activeVisit->distance_deviation : null,
                        'telemetry' => [
                            'speed' => $speed,
                            'battery_level' => $batteryLevel,
                            'network_type' => $networkType,
                            'accuracy' => $accuracy,
                        ]
                    ];
                });
            
        return response()->json($agents);
    }

    private function getAllowedAgentIds($user)
    {
        /** @var \App\Models\User $user */
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
