<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        if (!Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']], $credentials['remember'] ?? false)) {
            return response()->json([
                'message' => 'Credențiale invalide.',
            ], 422);
        }

        /** @var \App\Models\User $user */
        $user = $request->user();
        $token = $user->createToken('spa')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user->load('roles', 'customer'),
        ]);
    }

    public function registerB2C(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
            'email'      => ['required', 'email', 'max:191', 'unique:users,email'],
            'phone'      => ['nullable', 'string', 'max:50'],
            'password'   => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $customer = Customer::create([
            'type'                 => 'b2c',
            'name'                 => $data['first_name'] . ' ' . $data['last_name'],
            'email'                => $data['email'],
            'phone'                => $data['phone'] ?? null,
            'group_id'             => CustomerGroup::where('type', 'b2c')->value('id'),
            'payment_terms_days'   => 0,
            'credit_limit'         => 0,
            'current_balance'      => 0,
            'currency'             => 'RON',
            'is_active'            => true,
            'is_partner'           => false,
        ]);

        $user = User::create([
            'first_name'  => $data['first_name'],
            'last_name'   => $data['last_name'],
            'email'       => $data['email'],
            'phone'       => $data['phone'] ?? null,
            'password'    => Hash::make($data['password']),
            'customer_id' => $customer->id,
            'is_active'   => true,
        ]);

        $role = Role::where('slug', 'customer_b2c')->first();
        if ($role) {
            $user->roles()->attach($role);
        }

        $token = $user->createToken('spa')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user->load('roles', 'customer'),
        ], 201);
    }

    public function registerB2B(Request $request)
    {
        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:191'],
            'cif'          => ['required', 'string', 'max:50'],
            'reg_com'      => ['nullable', 'string', 'max:50'],
            'iban'         => ['nullable', 'string', 'max:50'],
            'contact_name' => ['required', 'string', 'max:191'],
            'email'        => ['required', 'email', 'max:191', 'unique:users,email'],
            'phone'        => ['nullable', 'string', 'max:50'],
            'password'     => ['required', 'string', 'min:6', 'confirmed'],
            'become_partner' => ['sometimes', 'boolean'],
        ]);

        $customer = Customer::create([
            'type'                 => 'b2b',
            'name'                 => $data['company_name'],
            'legal_name'           => $data['company_name'],
            'cif'                  => $data['cif'],
            'reg_com'              => $data['reg_com'] ?? null,
            'iban'                 => $data['iban'] ?? null,
            'email'                => $data['email'],
            'phone'                => $data['phone'] ?? null,
            'group_id'             => CustomerGroup::where('type', 'b2b')->value('id'),
            'payment_terms_days'   => 30,
            'credit_limit'         => 0,
            'current_balance'      => 0,
            'currency'             => 'RON',
            'is_active'            => true,
            'is_partner'           => $data['become_partner'] ?? false,
        ]);

        $user = User::create([
            'first_name'  => $data['contact_name'],
            'last_name'   => '',
            'email'       => $data['email'],
            'phone'       => $data['phone'] ?? null,
            'password'    => Hash::make($data['password']),
            'customer_id' => $customer->id,
            'is_active'   => true,
        ]);

        $role = Role::where('slug', 'customer_b2b')->first();
        if ($role) {
            $user->roles()->attach($role);
        }

        // Aici se poate adăuga logica pentru fluxul "Devino partener" (notificare internă etc.)

        $token = $user->createToken('spa')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user->load('roles', 'customer'),
        ], 201);
    }

    public function me(Request $request)
    {
        return response()->json($request->user()->load('roles', 'customer'));
    }

    public function logout(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $user->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout efectuat.']);
    }
}
