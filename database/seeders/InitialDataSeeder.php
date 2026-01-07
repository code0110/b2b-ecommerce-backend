<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\CustomerGroup;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Administrator', 'slug' => 'admin'],
            ['name' => 'Client B2C', 'slug' => 'customer_b2c'],
            ['name' => 'Client B2B', 'slug' => 'customer_b2b'],
            ['name' => 'Agent vânzări', 'slug' => 'sales_agent'],
            ['name' => 'Director vânzări', 'slug' => 'sales_director'],
            ['name' => 'Operator', 'slug' => 'operator'],
            ['name' => 'Marketer', 'slug' => 'marketer'],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                [
                    'name' => $roleData['name'],
                    'code' => $roleData['slug']
                ]
            );
        }

        CustomerGroup::firstOrCreate(
            ['name' => 'Clienți B2C', 'type' => 'b2c'],
            ['default_discount_percent' => 0, 'default_payment_terms_days' => 0, 'default_credit_limit' => 0]
        );

        CustomerGroup::firstOrCreate(
            ['name' => 'Clienți B2B', 'type' => 'b2b'],
            ['default_discount_percent' => 0, 'default_payment_terms_days' => 30, 'default_credit_limit' => 0]
        );
    }
}
