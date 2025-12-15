<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CompanyUserController extends Controller
{
    public function index(Request $request)
    {
        $owner = $request->user();
        $customer = $owner->customer;

        $users = User::where('customer_id', $customer?->id)
            ->orderBy('first_name')
            ->get();

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $owner = $request->user();
        $customer = $owner->customer;

        $data = $request->validate([
            'first_name'    => 'required|string|max:255',
            'last_name'     => 'nullable|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'phone'         => 'nullable|string|max:50',
            'company_role'  => 'nullable|string|max:50', // ex. 'buyer', 'approver'
            'password'      => 'required|string|min:8',
        ]);

        $user = User::create([
            'customer_id'  => $customer?->id,
            'first_name'   => $data['first_name'],
            'last_name'    => $data['last_name'] ?? null,
            'email'        => $data['email'],
            'phone'        => $data['phone'] ?? null,
            'company_role' => $data['company_role'] ?? 'buyer',
            'password'     => Hash::make($data['password']),
            'is_active'    => true,
        ]);

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user)
    {
        $owner = $request->user();
        $customer = $owner->customer;

        if ($user->customer_id !== $customer?->id) {
            abort(403);
        }

        $data = $request->validate([
            'first_name'    => 'sometimes|string|max:255',
            'last_name'     => 'nullable|string|max:255',
            'phone'         => 'nullable|string|max:50',
            'company_role'  => 'nullable|string|max:50',
            'is_active'     => 'boolean',
        ]);

        $user->update($data);

        return response()->json($user);
    }

    public function destroy(Request $request, User $user)
    {
        $owner = $request->user();
        $customer = $owner->customer;

        if ($user->customer_id !== $customer?->id) {
            abort(403);
        }

        // nu lăsăm să se șteargă singur pe sine
        if ($user->id === $owner->id) {
            return response()->json([
                'message' => 'Nu poți șterge propriul cont din companie.'
            ], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted']);
    }
}
