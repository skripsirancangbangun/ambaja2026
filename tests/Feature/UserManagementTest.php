<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_a_user_with_a_role(): void
    {
        $owner = User::factory()->create(['role' => 'Owner']);

        $response = $this->actingAs($owner)->post(route('users.store'), [
            'name' => 'Staff Penjualan',
            'email' => 'sales.staff@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'role' => 'Penjualan',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'sales.staff@example.com',
            'role' => 'Penjualan',
            'is_active' => 1,
        ]);

        $createdUser = User::query()->where('email', 'sales.staff@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('SecurePass123!', $createdUser->password));
    }

    public function test_admin_can_create_the_first_owner_when_no_active_owner_exists(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)->get(route('users.create'))
            ->assertOk()
            ->assertSee('Owner');

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Owner Pertama',
            'email' => 'first.owner@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'role' => 'Owner',
            'is_active' => '1',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'first.owner@example.com',
            'role' => 'Owner',
            'is_active' => 1,
        ]);
    }

    public function test_admin_cannot_assign_another_owner_when_an_active_owner_exists(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        User::factory()->create(['role' => 'Owner', 'is_active' => true]);

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Unexpected Owner',
            'email' => 'unexpected.owner@example.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'role' => 'Owner',
            'is_active' => '1',
        ])->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'unexpected.owner@example.com']);
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $salesUser = User::factory()->create(['role' => 'Penjualan']);

        $this->actingAs($salesUser)
            ->get(route('users.index'))
            ->assertStatus(403);
    }

    public function test_sales_role_cannot_access_accounting_module(): void
    {
        $salesUser = User::factory()->create(['role' => 'Penjualan']);

        $this->actingAs($salesUser)
            ->get(route('coa.index'))
            ->assertStatus(403);
    }

    public function test_accounting_role_cannot_write_sales_transactions(): void
    {
        $accountingUser = User::factory()->create(['role' => 'Accounting']);

        $this->actingAs($accountingUser)
            ->post(route('penjualan.store'), [])
            ->assertStatus(403);
    }

    public function test_owner_cannot_deactivate_their_own_account(): void
    {
        $owner = User::factory()->create(['role' => 'Owner', 'is_active' => true]);

        $this->actingAs($owner)
            ->delete(route('users.destroy', $owner))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $owner->id, 'is_active' => 1]);
    }
}
