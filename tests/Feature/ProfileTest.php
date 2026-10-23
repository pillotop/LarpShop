<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_profile(): void
    {
        $response = $this->get('/profile');

        $response->assertRedirect('/login');
    }

    public function test_customer_can_view_profile_page(): void
    {
        $user = User::factory()->create([
            'username' => 'customerhero',
            'email' => 'hero@larpshop.test',
            'phone_number' => '0912345678',
            'role' => 'customer',
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response
            ->assertOk()
            ->assertSee('customerhero')
            ->assertSee('hero@larpshop.test')
            ->assertSee('0912345678')
            ->assertSee('Customer');
    }

    public function test_customer_can_update_profile_information(): void
    {
        $user = User::factory()->create([
            'username' => 'initialuser',
            'email' => 'initial@larpshop.test',
            'phone_number' => '0911111111',
        ]);

        $response = $this->actingAs($user)->put('/profile', [
            'username' => 'updateduser',
            'email' => 'updated@larpshop.test',
            'phone_number' => '0922222222',
        ]);

        $response
            ->assertRedirect(route('profile'))
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertSame('updateduser', $user->username);
        $this->assertSame('updated@larpshop.test', $user->email);
        $this->assertSame('0922222222', $user->phone_number);
    }

    public function test_update_profile_validates_unique_username_and_email(): void
    {
        $userA = User::factory()->create([
            'username' => 'user_alpha',
            'email' => 'alpha@larpshop.test',
        ]);

        $userB = User::factory()->create([
            'username' => 'user_beta',
            'email' => 'beta@larpshop.test',
        ]);

        // Attempt to update userB with userA's username and email
        $response = $this->actingAs($userB)->put('/profile', [
            'username' => 'user_alpha',
            'email' => 'alpha@larpshop.test',
            'phone_number' => '0933333333',
        ]);

        $response->assertSessionHasErrors(['username', 'email']);

        $userB->refresh();
        $this->assertSame('user_beta', $userB->username);
        $this->assertSame('beta@larpshop.test', $userB->email);
    }
}
