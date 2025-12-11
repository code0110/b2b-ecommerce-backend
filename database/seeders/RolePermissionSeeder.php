<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Administrator',       'code' => 'admin'],
            ['name' => 'Operator',            'code' => 'operator'],
            ['name' => 'Marketer',            'code' => 'marketer'],
            ['name' => 'Agent vânzări',       'code' => 'agent'],
            ['name' => 'Director vânzări',    'code' => 'sales_director'],
            ['name' => 'Client B2B',          'code' => 'customer_b2b'],
            ['name' => 'Client B2C',          'code' => 'customer_b2c'],
        ];

        foreach ($roles as $r) {
            Role::firstOrCreate(
                ['code' => $r['code']],
                ['name' => $r['name'], 'is_system' => true]
            );
        }

        $permissions = [
            // produse
            ['code' => 'products.view',    'name' => 'Vezi produse',       'module' => 'products'],
            ['code' => 'products.manage',  'name' => 'Gestionează produse','module' => 'products'],
            // promoții
            ['code' => 'promotions.view',  'name' => 'Vezi promoții',      'module' => 'promotions'],
            ['code' => 'promotions.manage','name' => 'Gestionează promoții','module' => 'promotions'],
            // clienți
            ['code' => 'customers.view',   'name' => 'Vezi clienți',       'module' => 'customers'],
            ['code' => 'customers.manage', 'name' => 'Gestionează clienți','module' => 'customers'],
            // comenzi
            ['code' => 'orders.view',      'name' => 'Vezi comenzi',       'module' => 'orders'],
            ['code' => 'orders.manage',    'name' => 'Gestionează comenzi','module' => 'orders'],
            // plăți
            ['code' => 'payments.manage',  'name' => 'Încasări / plăți',   'module' => 'payments'],
            // audit
            ['code' => 'audit.view',       'name' => 'Vezi audit log',     'module' => 'audit'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(
                ['code' => $p['code']],
                ['name' => $p['name'], 'module' => $p['module']]
            );
        }

        // mapează câteva permisiuni de bază pe roluri
        $admin = Role::where('code', 'admin')->first();
        if ($admin) {
            $admin->permissions()->sync(Permission::pluck('id')->all());
        }
    }
}
