<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->orderBy('id')->get();

        return response()->json(
            $roles->map(function (Role $role) {
                return $this->transformRole($role);
            })
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'slug'          => ['required', 'string', 'max:255', 'unique:roles,slug'],
            'name'          => ['required', 'string', 'max:255'],
            'code'          => ['required', 'string', 'max:255', 'unique:roles,code'],
            'description'   => ['nullable', 'string', 'max:255'],
            'is_system'     => ['nullable', 'boolean'],
            'permission_ids'=> ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role = Role::create([
            'slug'        => $data['slug'],
            'name'        => $data['name'],
            'code'        => $data['code'],
            'description' => $data['description'] ?? null,
            'is_system'   => $data['is_system'] ?? false,
        ]);

        if (!empty($data['permission_ids'])) {
            $role->permissions()->sync($data['permission_ids']);
        }

        return response()->json($this->transformRole($role->fresh('permissions')), 201);
    }

    public function show(Role $role)
    {
        $role->load('permissions');

        return response()->json($this->transformRole($role));
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'slug'          => ['sometimes', 'required', 'string', 'max:255', "unique:roles,slug,{$role->id}"],
            'name'          => ['sometimes', 'required', 'string', 'max:255'],
            'code'          => ['sometimes', 'required', 'string', 'max:255', "unique:roles,code,{$role->id}"],
            'description'   => ['nullable', 'string', 'max:255'],
            'is_system'     => ['nullable', 'boolean'],
            'permission_ids'=> ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        // opțional: blocăm modificarea slug/code pentru rolurile critice
        if ($role->is_system) {
            unset($data['slug'], $data['code'], $data['is_system']);
        }

        $role->fill($data);
        $role->save();

        if (array_key_exists('permission_ids', $data)) {
            $role->permissions()->sync($data['permission_ids'] ?? []);
        }

        return response()->json($this->transformRole($role->fresh('permissions')));
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return response()->json([
                'message' => 'Rolurile de sistem nu pot fi șterse.',
            ], 422);
        }

        if ($role->users()->exists()) {
            return response()->json([
                'message' => 'Rolul are utilizatori alocați și nu poate fi șters.',
            ], 422);
        }

        $role->permissions()->detach();
        $role->delete();

        return response()->json(null, 204);
    }

    protected function transformRole(Role $role): array
    {
        return [
            'id'          => $role->id,
            'slug'        => $role->slug,
            'name'        => $role->name,
            'code'        => $role->code,
            'description' => $role->description,
            'is_system'   => (bool) $role->is_system,
            'created_at'  => optional($role->created_at)->toDateTimeString(),
            'permissions' => $role->permissions->map(function (Permission $p) {
                return [
                    'id'    => $p->id,
                    'name'  => $p->name,
                    'code'  => $p->code,
                    'module'=> $p->module,
                ];
            })->values(),
        ];
    }
}
