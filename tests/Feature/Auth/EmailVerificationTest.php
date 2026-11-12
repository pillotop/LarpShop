<?php

namespace Tests\Feature\Auth;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function register(string $email = 'buyer@example.test'): void
    {
        $this->postJson(route('register.store'), [
            'email' => $email, 'username' => 'buyer',
            'password' => 'test-password', 'password_confirmation' => 'test-password',
        ])->assertStatus(202)->assertJsonPath('verify', true);
    }

    public function test_registration_sends_a_code_and_does_not_log_in(): void
    {
        Mail::fake();

        $this->register();

        $this->assertGuest();
        $this->assertNull(User::where('email', 'buyer@example.test')->sole()->email_verified_at);
        Mail::assertSent(VerificationCodeMail::class, fn ($mail) => $mail->hasTo('buyer@example.test') && strlen($mail->code) === 6);
    }

    public function test_correct_code_verifies_and_logs_in(): void
    {
        Mail::fake();
        $this->register();

        $code = null;
        Mail::assertSent(VerificationCodeMail::class, function ($mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        $this->postJson(route('verification.code.verify'), ['email' => 'buyer@example.test', 'code' => $code])
            ->assertOk()->assertJsonPath('redirect', route('home'));

        $user = User::where('email', 'buyer@example.test')->sole();
        $this->assertNotNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_code_is_rejected_and_locks_after_too_many_attempts(): void
    {
        Mail::fake();
        $this->register();

        $code = null;
        Mail::assertSent(VerificationCodeMail::class, function ($mail) use (&$code) {
            $code = $mail->code;

            return true;
        });
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('verification.code.verify'), ['email' => 'buyer@example.test', 'code' => $wrong])
                ->assertUnauthorized()->assertJsonPath('errors.code.0', __('auth.verification.invalid_code'));
        }

        // Even the right code no longer works once attempts are exhausted.
        $this->postJson(route('verification.code.verify'), ['email' => 'buyer@example.test', 'code' => $code])
            ->assertUnauthorized();
        $this->assertGuest();
    }

    public function test_expired_code_is_rejected(): void
    {
        Mail::fake();
        $this->register();

        $code = null;
        Mail::assertSent(VerificationCodeMail::class, function ($mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        $this->travel(11)->minutes();

        $this->postJson(route('verification.code.verify'), ['email' => 'buyer@example.test', 'code' => $code])
            ->assertUnauthorized();
    }

    public function test_unverified_user_cannot_sign_in_and_receives_a_new_code(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create(['password' => 'test-password']);

        $this->postJson(route('login.store'), ['email' => $user->email, 'password' => 'test-password'])
            ->assertForbidden()->assertJsonPath('requires_verification', true);

        $this->assertGuest();
        Mail::assertSent(VerificationCodeMail::class, 1);
    }

    public function test_resend_respects_cooldown_and_hides_account_existence(): void
    {
        Mail::fake();
        $this->register();

        $this->postJson(route('verification.code.resend'), ['email' => 'buyer@example.test'])->assertOk();
        Mail::assertSent(VerificationCodeMail::class, 1); // cooldown blocked the second send

        $this->travel(2)->minutes();
        $this->postJson(route('verification.code.resend'), ['email' => 'buyer@example.test'])->assertOk();
        Mail::assertSent(VerificationCodeMail::class, 2);

        $this->postJson(route('verification.code.resend'), ['email' => 'nobody@example.test'])
            ->assertOk()->assertJsonPath('message', __('auth.verification.resent'));
    }

    public function test_code_is_stored_hashed(): void
    {
        Mail::fake();
        $this->register();

        $code = null;
        Mail::assertSent(VerificationCodeMail::class, function ($mail) use (&$code) {
            $code = $mail->code;

            return true;
        });

        $stored = \DB::table('email_verification_codes')->value('code_hash');
        $this->assertNotSame($code, $stored);
        $this->assertTrue(Hash::check($code, $stored));
    }

    public function test_verification_can_be_disabled_by_config(): void
    {
        config(['auth.require_email_verification' => false]);

        $this->postJson(route('register.store'), [
            'email' => 'quick@example.test', 'username' => 'quick',
            'password' => 'test-password', 'password_confirmation' => 'test-password',
        ])->assertCreated()->assertJsonPath('redirect', route('home'));

        $this->assertAuthenticated();
    }
}
