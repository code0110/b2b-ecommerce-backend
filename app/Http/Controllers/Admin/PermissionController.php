<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $query = Permission::query();

        if ($module = $request->get('module')) {
            $query->where('module', $module);
        }

        return $query->orderBy('module')->orderBy('code')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:191'],
            'code'        => ['required', 'string', 'max:191', 'unique:permissions,code'],
            'module'      => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $perm = Permission::create($data);

        return response()->json($perm, 201);
    }

    public function show(Permission $permission)
    {
        return $permission;
    }

    public function update(Request $request, Permission $permission)
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:191'],
            'code'        => ['sometimes', 'string', 'max:191', 'unique:permissions,code,' . $permission->id],
            'module'      => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ]);

        $permission->update($data);

        return response()->json($permission);
    }

    public function destroy(Permission $permission)
    {
        $permission->roles()->detach();
        $permission->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
