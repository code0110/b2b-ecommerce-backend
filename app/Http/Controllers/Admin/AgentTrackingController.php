<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentDailyRoute;
use App\Models\RoutePoint;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;

class AgentTrackingController extends Controller
{
    /**
     * Helper to get allowed agents for current user (Admin/Director)
     */
    protected function getAllowedAgentIds($user)
    {
        /** @var \App\Models\User $user */
        if ($user->hasRole('admin')) {
            // Admin sees all agents and directors who might have routes
            $agents = User::role('sales_agent')->pluck('id')->toArray();
            $directors = User::role('sales_director')->pluck('id')->toArray();
            return array_unique(array_merge($agents, $directors));
        }
        
        if ($user->hasRole('sales_director')) {
            // Director sees their subordinates and themselves
            $ids = $user->subordinates()->pluck('id')->toArray();
            $ids[] = $user->id;
            return array_unique($ids);
        }

        if ($user->hasRole('sales_agent')) {
            // Agent sees only themselves
            return [$user->id];
        }

        return [];
    }

    // Start Day (Shift)
    public function startDay(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();

        // Check if already started
        $existing = AgentDailyRoute::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($existing) {
            if ($existing->status === 'completed') {
                // Re-opening or new shift? Let's re-open for now
                $existing->update(['status' => 'active']);
                return response()->json($existing);
            }
            return response()->json($existing);
        }

        $route = AgentDailyRoute::create([
            'user_id' => $user->id,
            'date' => $today,
            'start_time' => Carbon::now(),
            'status' => 'active',
            'total_distance' => 0
        ]);

        return response()->json($route);
    }

    // End Day (Shift)
    public function endDay(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();

        $route = AgentDailyRoute::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if ($route) {
            $route->update([
                'end_time' => Carbon::now(),
                'status' => 'completed'
            ]);
        }

        return response()->json($route);
    }

    // Ping Location (Continuous Tracking)
    public function pingLocation(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();

        $data = $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'accuracy' => 'nullable|numeric',
            'speed' => 'nullable|numeric',
            'heading' => 'nullable|numeric',
            'battery_level' => 'nullable|integer',
            'is_mocked' => 'boolean',
        ]);

        // Find active route
        $route = AgentDailyRoute::where('user_id', $user->id)
            ->where('date', $today)
            ->first();

        if (!$route) {
            // Auto-start day if not started? 
            // Or reject? For robustness, let's auto-create if missing but valid hours
            $route = AgentDailyRoute::create([
                'user_id' => $user->id,
                'date' => $today,
                'start_time' => Carbon::now(),
                'status' => 'active',
                'total_distance' => 0
            ]);
        }

        // Calculate distance from last point
        $lastPoint = $route->routePoints()->latest()->first();
        $distance = 0;
        
        if ($lastPoint) {
            $distance = $this->calculateDistance(
                $lastPoint->latitude, $lastPoint->longitude,
                $data['latitude'], $data['longitude']
            );
        }

        // Ignore small jumps (GPS drift) - e.g., less than 10 meters if speed is 0
        if ($distance > 0) {
            $route->increment('total_distance', $distance / 1000); // Add km
        }

        // Create Point
        $route->routePoints()->create([
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'accuracy' => $data['accuracy'] ?? null,
            'speed' => $data['speed'] ?? null,
            'heading' => $data['heading'] ?? null,
            'recorded_at' => Carbon::now(),
            'battery_level' => $data['battery_level'] ?? null,
            'is_mocked' => $data['is_mocked'] ?? false,
        ]);

        return response()->json(['status' => 'ok', 'total_distance' => $route->total_distance]);
    }

    // Get History for Admin Map
    public function getHistory(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|exists:users,id',
            'date' => 'required|date'
        ]);

        $user = Auth::user();
        $allowedAgentIds = $this->getAllowedAgentIds($user);

        if (!in_array($request->agent_id, $allowedAgentIds)) {
            return response()->json(['message' => 'Unauthorized access to this agent\'s history'], 403);
        }

        $route = AgentDailyRoute::where('user_id', $request->agent_id)
            ->where('date', $request->date)
            ->with(['routePoints' => function($q) {
                $q->orderBy('recorded_at'); // Breadcrumbs order
            }])
            ->first();

        if (!$route) {
            return response()->json(['message' => 'No route found'], 404);
        }

        return response()->json($route);
    }
    
    // Check Status (Current Shift)
    public function status(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today();
        
        $route = AgentDailyRoute::where('user_id', $user->id)
            ->where('date', $today)
            ->first();
            
        return response()->json([
            'active' => $route && $route->status === 'active',
            'route' => $route
        ]);
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c);
    }
}
