<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CompanyUserController extends Controller
{
    protected function ensureOwner(Request $request): void
    {
        $user = $request->user();

        if (!$user->customer || $user->customer->type !== 'b2b' || !$user->isCompanyOwner()) {
            abort(403, 'Doar administratorul companiei poate gestiona utilizatorii.');
        }
    }

    public function index(Request $request)
    {
        $this->ensureOwner($request);
        $user = $request->user();

        return User::where('customer_id', $user->customer_id)
            ->orderBy('id')
            ->get();
    }

    public function store(Request $request)
    {
        $this->ensureOwner($request);
        $owner = $request->user();

        $data = $request->validate([
            'first_name'       => ['required', 'string', 'max:191'],
            'last_name'        => ['required', 'string', 'max:191'],
            'email'            => ['required', 'email', 'max:191', 'unique:users,email'],
            'company_role'     => ['required', 'in:owner,approver,buyer'],
            'requires_approval'=> ['boolean'],
        ]);

        $plainPassword = Str::random(12);

        $user = User::create([
            'first_name'       => $data['first_name'],
            'last_name'        => $data['last_name'],
            'email'            => $data['email'],
            'password'         => Hash::make($plainPassword),
            'customer_id'      => $owner->customer_id,
            'company_role'     => $data['company_role'],
            'requires_approval'=> $data['requires_approval'] ?? false,
        ]);

        // atașăm rolul de client B2B (dacă există)
        $role = Role::where('code', 'customer_b2b')->first();
        if ($role) {
            $user->roles()->attach($role->id);
        }

        return response()->json([
            'user'             => $user,
            'initial_password' => $plainPassword, // UI poate afișa o singură dată
        ], 201);
    }

    public function update($id, Request $request)
    {
        $this->ensureOwner($request);
        $owner = $request->user();

        $user = User::where('customer_id', $owner->customer_id)
            ->where('id', $id)
            ->firstOrFail();

        if ($user->id === $owner->id && $request->get('company_role') === 'buyer') {
            return response()->json(['message' => 'Nu poți coborî propriul rol sub owner.'], 422);
        }

        $data = $request->validate([
            'first_name'       => ['sometimes', 'string', 'max:191'],
            'last_name'        => ['sometimes', 'string', 'max:191'],
            'company_role'     => ['sometimes', 'in:owner,approver,buyer'],
            'requires_approval'=> ['boolean'],
        ]);

        $user->update($data);

        return response()->json($user);
    }

    public function destroy($id, Request $request)
    {
        $this->ensureOwner($request);
        $owner = $request->user();

        $user = User::where('customer_id', $owner->customer_id)
            ->where('id', $id)
            ->firstOrFail();

        if ($user->id === $owner->id) {
            return response()->json(['message' => 'Nu îți poți șterge propriul cont.'], 422);
        }

        $user->roles()->detach();
        $user->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
