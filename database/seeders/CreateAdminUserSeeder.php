<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CreateAdminUserSeeder extends Seeder
{
    public function run()
    {
        // 1. Găsește sau creează userul
        $user = User::firstOrCreate(
            ['email' => 'cod.binar@gmail.com'],
            [
                'first_name' => 'Admin',
                'last_name'  => 'System',
                'phone'      => '0700000000',
                'password'   => Hash::make('password'), // Parola implicită
                'is_active'  => true,
            ]
        );

        // 2. Găsește rolul de admin
        $adminRole = Role::where('slug', 'admin')->orWhere('code', 'admin')->first();

        // 3. Atribuie rolul dacă există
        if ($adminRole) {
            $user->roles()->syncWithoutDetaching([$adminRole->id]);
            $this->command->info('Admin user restored: cod.binar@gmail.com / password');
        } else {
            $this->command->error('Admin role not found!');
        }
    }
}
