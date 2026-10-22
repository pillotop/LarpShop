<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_can_be_rendered(): void
    {
        $response = $this->get(route('password.request'));

        $response
            ->assertOk()
            ->assertViewIs('auth.pages.forgot-password');
    }

    public function test_reset_password_link_can_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'If an account exists for this email address, a password reset link will be sent.',
            ]);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_password_link_request_does_not_reveal_non_existent_email(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'email' => 'nonexistent@example.com',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'If an account exists for this email address, a password reset link will be sent.',
            ]);

        Notification::assertNothingSent();
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->get(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]));

        $response
            ->assertOk()
            ->assertViewIs('auth.pages.reset-password')
            ->assertViewHas('token', $token);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'message' => 'Your password has been reset successfully.',
                'redirect' => route('login'),
            ]);

        $this->assertTrue(Hash::check('NewSecurePassword123!', $user->fresh()->password));
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $response = $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $response
            ->assertStatus(401)
            ->assertJsonStructure([
                'message',
                'errors' => ['email'],
            ]);

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }
}
