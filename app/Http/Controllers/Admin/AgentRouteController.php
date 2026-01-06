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
    /**
     * Helper to ensure the current user has permission to manage the target agent's routes.
     */
    protected function ensurePermission($user, $targetAgentId)
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('sales_director')) {
            // Director can manage subordinates
            $isSubordinate = User::where('id', $targetAgentId)
                ->where('director_id', $user->id)
                ->exists();
            
            if (!$isSubordinate) {
                abort(403, 'Nu aveți permisiunea de a gestiona rutele acestui agent.');
            }
            return true;
        }

        if ($user->hasRole('sales_agent')) {
            // Agent can only manage their own routes
            if ($user->id != $targetAgentId) {
                abort(403, 'Nu aveți permisiunea de a gestiona rutele altui agent.');
            }
            return true;
        }

        abort(403, 'Unauthorized');
    }

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

        $this->ensurePermission(Auth::user(), $data['agent_id']);

        $route = AgentRoute::create($data);

        return response()->json($route, 201);
    }

    public function update(Request $request, AgentRoute $route)
    {
        $this->ensurePermission(Auth::user(), $route->agent_id);

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
        $this->ensurePermission(Auth::user(), $route->agent_id);

        $route->delete();
        return response()->json(['message' => 'Route deleted']);
    }
}
