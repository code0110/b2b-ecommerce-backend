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

        if ($module = $request->query('module')) {
            $query->where('module', $module);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $permissions = $query->orderBy('module')->orderBy('code')->get();

        return response()->json($permissions);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'code'        => ['required', 'string', 'max:255', 'unique:permissions,code'],
            'module'      => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $permission = Permission::create($data);

        return response()->json($permission, 201);
    }

    public function show(Permission $permission)
    {
        return response()->json($permission);
    }

    public function update(Request $request, Permission $permission)
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'required', 'string', 'max:255'],
            'code'        => ['sometimes', 'required', 'string', 'max:255', "unique:permissions,code,{$permission->id}"],
            'module'      => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $permission->fill($data);
        $permission->save();

        return response()->json($permission);
    }

    public function destroy(Permission $permission)
    {
        // opțional: verifici dacă e folosită în roluri și blochezi.
        if ($permission->roles()->exists()) {
            return response()->json([
                'message' => 'Permisiunea este asociată unor roluri și nu poate fi ștearsă.',
            ], 422);
        }

        $permission->delete();

        return response()->json(null, 204);
    }
}
