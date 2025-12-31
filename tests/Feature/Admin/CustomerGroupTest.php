<?php

namespace Tests\Feature\Admin;

use App\Models\CustomerGroup;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerGroupTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create([
            'slug' => 'admin',
            'name' => 'Admin',
            'code' => 'admin',
        ]);

        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($role);
    }

    public function test_admin_can_list_customer_groups()
    {
        CustomerGroup::create(['name' => 'Group A', 'type' => 'b2b']);
        CustomerGroup::create(['name' => 'Group B', 'type' => 'b2c']);

        $response = $this->actingAs($this->admin)->getJson('/api/admin/customer-groups');

        $response->assertStatus(200)
            ->assertJsonCount(2);
    }

    public function test_admin_can_create_customer_group()
    {
        $data = [
            'name' => 'New Group',
            'type' => 'b2b',
            'default_discount_percent' => 15.5,
            'default_payment_terms_days' => 30,
            'default_credit_limit' => 10000,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/admin/customer-groups', $data);

        $response->assertStatus(201)
            ->assertJsonFragment($data);

        $this->assertDatabaseHas('customer_groups', ['name' => 'New Group']);
    }

    public function test_admin_can_show_customer_group()
    {
        $group = CustomerGroup::create(['name' => 'Group A', 'type' => 'b2b']);

        $response = $this->actingAs($this->admin)->getJson("/api/admin/customer-groups/{$group->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Group A']);
    }

    public function test_admin_can_update_customer_group()
    {
        $group = CustomerGroup::create(['name' => 'Old Name', 'type' => 'b2b']);

        $data = [
            'name' => 'Updated Name',
            'default_discount_percent' => 20,
        ];

        $response = $this->actingAs($this->admin)->putJson("/api/admin/customer-groups/{$group->id}", $data);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Name']);

        $this->assertDatabaseHas('customer_groups', ['id' => $group->id, 'name' => 'Updated Name', 'default_discount_percent' => 20]);
    }

    public function test_admin_can_delete_customer_group()
    {
        $group = CustomerGroup::create(['name' => 'To Delete', 'type' => 'b2b']);

        $response = $this->actingAs($this->admin)->deleteJson("/api/admin/customer-groups/{$group->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('customer_groups', ['id' => $group->id]);
    }

    public function test_validation_works()
    {
        $response = $this->actingAs($this->admin)->postJson('/api/admin/customer-groups', [
            'name' => '', // required
            'type' => 'invalid', // in:b2b,b2c
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'type']);
    }
}
