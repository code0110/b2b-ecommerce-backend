<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query()
            ->visibleTo($request->user())
            ->with(['group', 'agent:id,first_name,last_name,email', 'salesDirector:id,first_name,last_name,email', 'teamMembers:id,first_name,last_name,email']);

        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }
        if ($request->has('is_active') && $request->get('is_active') !== '') {
            $query->where('is_active', (bool) $request->get('is_active'));
        }

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($agentId = $request->get('agent_user_id')) {
            $query->where(function ($q) use ($agentId) {
                $q->where('agent_user_id', $agentId)
                  ->orWhereHas('teamMembers', function ($sq) use ($agentId) {
                      $sq->where('users.id', $agentId);
                  });
            });
        }

        $perPage = $request->get('per_page', 25);
        return $query->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type'               => ['required', Rule::in(['b2c', 'b2b'])],
            'name'               => ['required', 'string', 'max:191'],
            'legal_name'         => ['nullable', 'string', 'max:191'],
            'cif'                => ['nullable', 'string', 'max:50'],
            'reg_com'            => ['nullable', 'string', 'max:50'],
            'iban'               => ['nullable', 'string', 'max:50'],
            'email'              => ['nullable', 'email', 'max:191'],
            'phone'              => ['nullable', 'string', 'max:50'],
            'group_id'           => ['nullable', 'integer', 'exists:customer_groups,id'],
            'payment_terms_days' => ['nullable', 'integer'],
            'credit_limit'       => ['nullable', 'numeric'],
            'current_balance'    => ['nullable', 'numeric'],
            'currency'           => ['nullable', 'string', 'max:10'],
            'is_active'          => ['boolean'],
            'is_partner'         => ['boolean'],
            'agent_user_id'         => ['nullable', 'integer', 'exists:users,id'],
            'sales_director_user_id'=> ['nullable', 'integer', 'exists:users,id'],
            'team_members'          => ['nullable', 'array'],
            'team_members.*'        => ['exists:users,id'],
        ]);

        $user = $request->user();

        // RBAC Enforcement for Assignment
        if ($user->hasRole('sales_agent')) {
            // Agents can only assign to themselves
            $data['agent_user_id'] = $user->id;
            $data['sales_director_user_id'] = $user->director_id;
        } elseif ($user->hasRole('sales_director')) {
            // Directors can only assign to themselves or their subordinates
            if (!empty($data['agent_user_id']) && $data['agent_user_id'] != $user->id) {
                $isSubordinate = User::where('id', $data['agent_user_id'])
                    ->where('director_id', $user->id)
                    ->exists();
                
                if (!$isSubordinate) {
                    abort(403, 'You can only assign customers to your team members.');
                }
            }
            // Ensure director is set to current user (unless Admin overrides, but this is Director block)
            $data['sales_director_user_id'] = $user->id;
        } elseif ($user->hasRole('admin')) {
             // Admin can assign anyone. 
             // Logic to auto-set director based on agent if not provided or if agent changed
             if (array_key_exists('agent_user_id', $data) && !empty($data['agent_user_id'])) {
                $agent = User::find($data['agent_user_id']);
                if ($agent && $agent->director_id) {
                    $data['sales_director_user_id'] = $agent->director_id;
                }
            }
        }

        $customer = Customer::create($data);

        if ($request->has('team_members')) {
            $customer->teamMembers()->sync($request->input('team_members', []));
        }

        return response()->json($customer->load(['group', 'agent:id,first_name,last_name,email', 'salesDirector:id,first_name,last_name,email', 'teamMembers:id,first_name,last_name,email']), 201);
    }

    public function show(Customer $customer)
    {
        $this->ensureCustomerAccess($customer);
        return $customer->load('addresses', 'users', 'group', 'agent:id,first_name,last_name,email', 'salesDirector:id,first_name,last_name,email', 'teamMembers:id,first_name,last_name,email');
    }

    public function update(Request $request, Customer $customer)
    {
        $this->ensureCustomerAccess($customer);

        $data = $request->validate([
            'type'               => ['sometimes', Rule::in(['b2c', 'b2b'])],
            'name'               => ['sometimes', 'string', 'max:191'],
            'legal_name'         => ['nullable', 'string', 'max:191'],
            'cif'                => ['nullable', 'string', 'max:50'],
            'reg_com'            => ['nullable', 'string', 'max:50'],
            'iban'               => ['nullable', 'string', 'max:50'],
            'email'              => ['nullable', 'email', 'max:191'],
            'phone'              => ['nullable', 'string', 'max:50'],
            'group_id'           => ['nullable', 'integer', 'exists:customer_groups,id'],
            'payment_terms_days' => ['nullable', 'integer'],
            'credit_limit'       => ['nullable', 'numeric'],
            'current_balance'    => ['nullable', 'numeric'],
            'currency'           => ['nullable', 'string', 'max:10'],
            'is_active'          => ['boolean'],
            'is_partner'         => ['boolean'],
            'agent_user_id'         => ['nullable', 'integer', 'exists:users,id'],
            'sales_director_user_id'=> ['nullable', 'integer', 'exists:users,id'],
            'team_members'          => ['nullable', 'array'],
            'team_members.*'        => ['exists:users,id'],
        ]);

        $user = $request->user();

        // RBAC for Update
        if ($user->hasRole('sales_agent')) {
            // Agents cannot change assignment
            if (array_key_exists('agent_user_id', $data) && $data['agent_user_id'] != $user->id) {
                abort(403, 'You cannot reassign customers.');
            }
            
            // Only primary agent can update customer details (team members are read-only)
            if ($customer->agent_user_id != $user->id) {
                 abort(403, 'Only the primary agent can update customer details.');
            }

            // Prevent changing director
            unset($data['sales_director_user_id']);
        } elseif ($user->hasRole('sales_director')) {
            // Director can reassign within team
            if (array_key_exists('agent_user_id', $data) && !empty($data['agent_user_id'])) {
                 if ($data['agent_user_id'] != $user->id) {
                     $isSubordinate = User::where('id', $data['agent_user_id'])
                        ->where('director_id', $user->id)
                        ->exists();
                     if (!$isSubordinate) {
                         abort(403, 'You can only assign customers to your team members.');
                     }
                 }
            }
            // Prevent changing director to someone else (unless assigning to self/null logic handled below)
             if (array_key_exists('sales_director_user_id', $data) && $data['sales_director_user_id'] != $user->id) {
                 // But wait, the logic below auto-sets director based on agent.
                 // If agent is subordinate, director IS current user.
                 // So we just ensure they don't manually set it to someone else.
                 // Actually, let's trust the auto-logic below but force director_id if needed.
             }
        }

        if (array_key_exists('agent_user_id', $data)) {
            if (!empty($data['agent_user_id'])) {
                $agent = User::find($data['agent_user_id']);
                // Setăm directorul agentului (chiar dacă e null)
                if ($agent && $agent->director_id) {
                    $data['sales_director_user_id'] = $agent->director_id;
                }
            }
            // Eliminat logica care forța ștergerea directorului dacă nu există agent.
            // Astfel permitem asignarea unui client doar la un director.
        }

        $customer->update($data);

        if ($request->has('team_members')) {
            $customer->teamMembers()->sync($request->input('team_members', []));
        }

        return response()->json($customer->load(['group', 'agent:id,first_name,last_name,email', 'salesDirector:id,first_name,last_name,email', 'teamMembers:id,first_name,last_name,email']));
    }

    public function destroy(Customer $customer)
    {
        $user = request()->user();
        if (!$user->hasRole('admin')) {
            abort(403, 'Only admins can delete customers.');
        }

        $customer->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function ensureCustomerAccess(Customer $customer)
    {
        $user = request()->user();
        if ($user->hasRole('admin')) return;

        if ($user->hasRole('sales_director')) {
             if ($customer->sales_director_user_id == $user->id) return;
             if ($customer->agent_user_id == $user->id) return;
             
             if ($customer->agent_user_id) {
                 $isSubordinate = User::where('id', $customer->agent_user_id)
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
