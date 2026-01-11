<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Listă utilizatori (cu filtre) + roluri atașate.
     */
    public function index(Request $request)
    {
        $query = User::query()->with('roles');

        // RBAC: Sales Director sees only their subordinates and themselves
        if ($request->user()->hasRole('sales_director') && !$request->user()->hasRole('admin')) {
            $query->where(function($q) use ($request) {
                $q->where('director_id', $request->user()->id)
                  ->orWhere('id', $request->user()->id);
            });
        } elseif ($request->user()->hasRole('sales_agent') && !$request->user()->hasRole('admin')) {
            // Agents only see themselves
            $query->where('id', $request->user()->id);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->query('role')) {
            // role = slug
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('slug', $role);
            });
        }

        if ($type = $request->query('type')) {
            if ($type === 'internal') {
                $query->whereNull('customer_id');
            } elseif ($type === 'customer') {
                $query->whereNotNull('customer_id');
            }
        }

        if ($request->filled('is_active')) {
             $query->where('is_active', (bool) $request->query('is_active'));
        }

        $query->orderBy('created_at', 'desc');

        $perPage = (int) $request->query('per_page', 20);
        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function (User $user) {
            return $this->transformUser($user);
        });

        return response()->json($paginator);
    }

    /**
     * Creare utilizator de back-office + roluri.
     */
    public function store(Request $request)
    {
        $isDirector = $request->user()->hasRole('sales_director') && !$request->user()->hasRole('admin');

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name'  => ['nullable', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone'      => ['nullable', 'string', 'max:50'],
            'password'   => ['required', 'string', 'min:8'],
            'is_active'  => ['nullable', 'boolean'],
            'director_id' => ['nullable', 'integer', 'exists:users,id'],
            'role_ids'   => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);

        // RBAC enforcement for Sales Director
        if ($isDirector) {
            // Director can only create users assigned to themselves
            $data['director_id'] = $request->user()->id;
            
            // Director can only assign 'sales_agent' role
            $agentRole = Role::where('slug', 'sales_agent')->first();
            if (!$agentRole) {
                abort(500, 'Role sales_agent not found');
            }
            $data['role_ids'] = [$agentRole->id];
        }

        $user = new User();
        $user->first_name = $data['first_name'];
        $user->last_name  = $data['last_name'] ?? null;
        $user->email      = $data['email'];
        $user->phone      = $data['phone'] ?? null;
        $user->is_active  = $data['is_active'] ?? true;
        $user->director_id = $data['director_id'] ?? null; // Added this line
        $user->password   = Hash::make($data['password']);
        $user->save();

        if (!empty($data['role_ids'])) {
            $user->roles()->sync($data['role_ids']);
        }

        return response()->json($this->transformUser($user->fresh('roles')), 201);
    }

    protected function ensureDirectorAccess(Request $request, User $user)
    {
        if ($request->user()->hasRole('sales_director') && !$request->user()->hasRole('admin')) {
            if ($user->director_id !== $request->user()->id && $user->id !== $request->user()->id) {
                abort(403, 'Nu aveți permisiunea de a accesa acest utilizator.');
            }
        }
    }

    /**
     * Detaliu utilizator.
     */
    public function show(Request $request, User $user)
    {
        $this->ensureDirectorAccess($request, $user);
        $user->load('roles');

        return response()->json($this->transformUser($user));
    }

    /**
     * Update utilizator + roluri.
     */
    public function update(Request $request, User $user)
    {
        $this->ensureDirectorAccess($request, $user);
        
        $data = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name'  => ['nullable', 'string', 'max:255'],
            'email'      => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone'      => ['nullable', 'string', 'max:50'],
            'password'   => ['nullable', 'string', 'min:8'],
            'is_active'  => ['nullable', 'boolean'],
            'director_id' => ['nullable', 'integer', 'exists:users,id'],
            'role_ids'   => ['nullable', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);

        // RBAC: Director restrictions
        if ($request->user()->hasRole('sales_director') && !$request->user()->hasRole('admin')) {
            // Directors cannot transfer agents or change their roles
            unset($data['director_id']);
            unset($data['role_ids']);
        }

        if (array_key_exists('first_name', $data)) {
            $user->first_name = $data['first_name'];
        }

        if (array_key_exists('last_name', $data)) {
            $user->last_name = $data['last_name'];
        }

        if (array_key_exists('email', $data)) {
            $user->email = $data['email'];
        }

        if (array_key_exists('phone', $data)) {
            $user->phone = $data['phone'];
        }

        if (array_key_exists('is_active', $data)) {
            $user->is_active = $data['is_active'];
        }

        if (array_key_exists('director_id', $data)) {
            $user->director_id = $data['director_id'];
            
            // Actualizăm automat clienții agentului cu noul director
            Customer::where('agent_user_id', $user->id)
                ->update(['sales_director_user_id' => $data['director_id']]);
        }

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        if (array_key_exists('role_ids', $data)) {
            $user->roles()->sync($data['role_ids'] ?? []);
        }

        return response()->json($this->transformUser($user->fresh('roles')));
    }

    /**
     * Dezactivare utilizator (nu-l ștergem complet).
     */
    public function destroy(Request $request, User $user)
    {
        $this->ensureDirectorAccess($request, $user);

        // opțional: nu permiți să te dezactivezi singur
        if ($request->user()?->id === $user->id) {
            return response()->json([
                'message' => 'Nu poți dezactiva propriul utilizator.',
            ], 422);
        }

        $user->is_active = false;
        $user->save();

        return response()->json(null, 204);
    }

    protected function transformUser(User $user): array
    {
        return [
            'id'         => $user->id,
            'first_name' => $user->first_name,
            'last_name'  => $user->last_name,
            'name'       => trim($user->first_name . ' ' . ($user->last_name ?? '')),
            'email'      => $user->email,
            'phone'      => $user->phone,
            'director_id' => $user->director_id,
            'director_name' => optional($user->director)->full_name,
            'is_active'  => (bool) $user->is_active,
            'created_at' => optional($user->created_at)->toDateTimeString(),
            'roles'      => $user->roles->map(function (Role $role) {
                return [
                    'id'   => $role->id,
                    'slug' => $role->slug,
                    'name' => $role->name,
                    'code' => $role->code,
                ];
            })->values(),
        ];
    }
}
