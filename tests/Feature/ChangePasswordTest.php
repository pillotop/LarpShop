<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('current-password-123'),
        ]);

        $response = $this->actingAs($user)->put(route('password.change'), [
            'current_password' => 'current-password-123',
            'password' => 'brand-new-password-789',
            'password_confirmation' => 'brand-new-password-789',
        ]);

        $response
            ->assertRedirect(route('profile'))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('brand-new-password-789', $user->refresh()->password));
    }

    public function test_password_update_fails_with_incorrect_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password-123'),
        ]);

        $response = $this->actingAs($user)->put(route('password.change'), [
            'current_password' => 'wrong-pass',
            'password' => 'brand-new-password-789',
            'password_confirmation' => 'brand-new-password-789',
        ]);

        $response->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('correct-password-123', $user->refresh()->password));
    }

    public function test_password_change_supports_json_and_requires_confirmation(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $this->actingAs($user)->putJson(route('password.change'), [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'different-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('password');

        $this->putJson(route('password.change'), [
            'current_password' => 'old-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertOk()->assertJsonPath('success', true);
        $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    }
}
