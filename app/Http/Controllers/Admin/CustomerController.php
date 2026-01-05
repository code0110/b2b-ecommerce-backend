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

        return $query->paginate(25);
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

        if (array_key_exists('agent_user_id', $data) && !empty($data['agent_user_id'])) {
            $agent = User::find($data['agent_user_id']);
            if ($agent && $agent->director_id) {
                $data['sales_director_user_id'] = $agent->director_id;
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
        return $customer->load('addresses', 'users', 'group', 'agent:id,first_name,last_name,email', 'salesDirector:id,first_name,last_name,email', 'teamMembers:id,first_name,last_name,email');
    }

    public function update(Request $request, Customer $customer)
    {
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
        $customer->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
