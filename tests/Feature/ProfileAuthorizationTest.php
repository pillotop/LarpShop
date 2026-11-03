<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_cannot_change_role_through_profile_update(): void
    {
        $user = User::factory()->create(['role' => 'customer']);

        $this->actingAs($user)->putJson(route('profile.update'), [
            'username' => $user->username,
            'email' => $user->email,
            'role' => 'admin',
        ])->assertOk();

        // This security assertion deliberately fails on the vulnerable demo branch.
        $this->assertSame('customer', $user->fresh()->role);
    }
}
