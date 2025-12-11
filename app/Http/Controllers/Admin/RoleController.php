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
        return Role::with('permissions')->orderBy('name')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:191'],
            'code'        => ['required', 'string', 'max:100', 'unique:roles,code'],
            'description' => ['nullable', 'string'],
            'is_system'   => ['boolean'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role = Role::create([
            'name'        => $data['name'],
            'code'        => $data['code'],
            'description' => $data['description'] ?? null,
            'is_system'   => $data['is_system'] ?? false,
        ]);

        if (!empty($data['permission_ids'])) {
            $role->permissions()->sync($data['permission_ids']);
        }

        return response()->json($role->load('permissions'), 201);
    }

    public function show(Role $role)
    {
        return $role->load('permissions');
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:191'],
            'code'        => ['sometimes', 'string', 'max:100', 'unique:roles,code,' . $role->id],
            'description' => ['nullable', 'string'],
            'is_system'   => ['boolean'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        if ($role->is_system && isset($data['code']) && $data['code'] !== $role->code) {
            return response()->json(['message' => 'Nu poți modifica codul unui rol de sistem.'], 422);
        }

        $role->update($data);

        if (array_key_exists('permission_ids', $data)) {
            $role->permissions()->sync($data['permission_ids'] ?? []);
        }

        return response()->json($role->load('permissions'));
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return response()->json(['message' => 'Nu poți șterge un rol de sistem.'], 422);
        }

        $role->permissions()->detach();
        $role->users()->detach();
        $role->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
