<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admins_cannot_access_user_management(): void
    {
        $this->get('/admin/users')->assertRedirect('/login');

        $customer = User::factory()->create(['role' => 'customer']);
        $other = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->get('/admin/users')->assertForbidden();
        $this->actingAs($customer)->patch("/admin/users/{$other->user_id}/role", ['role' => 'admin'])->assertForbidden();
        $this->assertSame('customer', $other->refresh()->role);
    }

    public function test_admin_can_list_search_and_filter_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'username' => 'boss_admin']);
        User::factory()->create(['role' => 'customer', 'username' => 'alice_buyer', 'email' => 'alice@example.test']);
        User::factory()->create(['role' => 'customer', 'username' => 'bob_buyer', 'email' => 'bob@example.test']);

        $this->actingAs($admin)->get('/admin/users')
            ->assertOk()->assertSee('alice_buyer')->assertSee('bob_buyer')->assertSee('boss_admin');

        $this->actingAs($admin)->get('/admin/users?q=alice')
            ->assertOk()->assertSee('alice_buyer')->assertDontSee('bob_buyer');

        $this->actingAs($admin)->get('/admin/users?role=admin')
            ->assertOk()->assertSee('boss_admin')->assertDontSee('alice_buyer');
    }

    public function test_invalid_filter_values_are_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/users?role=vendor')->assertSessionHasErrors('role');
        $this->actingAs($admin)->get('/admin/users?q[]=x')->assertSessionHasErrors('q');
    }

    public function test_admin_can_promote_customer_without_touching_stores(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);
        $vendor = $customer->vendors()->create(['store_name' => 'Keep Me', 'status' => 'approved']);

        $this->actingAs($admin)->patch("/admin/users/{$customer->user_id}/role", ['role' => 'admin'])
            ->assertSessionHas('success');

        $this->assertSame('admin', $customer->refresh()->role);
        $this->assertSame('approved', $vendor->refresh()->status);
    }

    public function test_role_must_be_in_allowlist(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $customer = User::factory()->create(['role' => 'customer']);

        foreach (['vendor', 'superuser', ''] as $bad) {
            $this->actingAs($admin)->patch("/admin/users/{$customer->user_id}/role", ['role' => $bad])
                ->assertSessionHasErrors('role');
        }

        $this->assertSame('customer', $customer->refresh()->role);
    }

    public function test_admin_cannot_demote_themselves(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->patch("/admin/users/{$admin->user_id}/role", ['role' => 'customer'])
            ->assertSessionHas('warning');

        $this->assertSame('admin', $admin->refresh()->role);
    }

    public function test_admin_can_demote_another_admin_when_others_remain(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $other = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->patch("/admin/users/{$other->user_id}/role", ['role' => 'customer'])
            ->assertSessionHas('success');

        $this->assertSame('customer', $other->refresh()->role);
    }
}
