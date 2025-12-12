<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['slug' => 'admin',         'name' => 'Administrator'],
            ['slug' => 'customer_b2c',  'name' => 'Client B2C'],
            ['slug' => 'customer_b2b',  'name' => 'Client B2B'],
            ['slug' => 'sales_agent',   'name' => 'Agent vânzări'],
            ['slug' => 'sales_director','name' => 'Director vânzări'],
            ['slug' => 'operator',      'name' => 'Operator birou'],
            ['slug' => 'marketer',      'name' => 'Marketer'],
        ];

        foreach ($roles as $data) {
            Role::updateOrCreate(
                ['slug' => $data['slug']],
                ['name' => $data['name']]
            );
        }
    }
}
