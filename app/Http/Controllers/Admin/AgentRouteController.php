<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentRoute;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AgentRouteController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        
        // Agents see their own routes
        // Directors see their subordinates' routes
        // Admins see all
        
        $query = AgentRoute::with(['customer', 'agent']);

        if ($user->hasRole('sales_agent')) {
            $query->where('agent_id', $user->id);
        } elseif ($user->hasRole('sales_director')) {
            $subordinates = $user->subordinates()->pluck('id');
            $query->whereIn('agent_id', $subordinates);
        }

        if ($agentId = $request->get('agent_id')) {
            $query->where('agent_id', $agentId);
        }

        if ($day = $request->get('day_of_week')) {
            $query->where('day_of_week', $day);
        }

        return response()->json($query->orderBy('day_of_week')->orderBy('sort_order')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'agent_id'    => ['required', 'exists:users,id'],
            'customer_id' => ['required', 'exists:customers,id'],
            'day_of_week' => ['required', Rule::in(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])],
            'week_type'   => ['sometimes', Rule::in(['all', 'odd', 'even'])],
            'sort_order'  => ['integer'],
        ]);

        $route = AgentRoute::create($data);

        return response()->json($route, 201);
    }

    public function update(Request $request, AgentRoute $route)
    {
        $data = $request->validate([
            'day_of_week' => ['sometimes', Rule::in(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])],
            'week_type'   => ['sometimes', Rule::in(['all', 'odd', 'even'])],
            'sort_order'  => ['sometimes', 'integer'],
        ]);

        $route->update($data);

        return response()->json($route);
    }

    public function destroy(AgentRoute $route)
    {
        $route->delete();
        return response()->json(['message' => 'Route deleted']);
    }
}
