<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.client_id' => 'test-google-client-id',
            'services.google.client_secret' => 'test-google-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);
    }

    public function test_redirect_sends_user_to_google_with_state_in_session(): void
    {
        $response = $this->get(route('auth.google.redirect'));

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $location);
        $this->assertStringContainsString('client_id=test-google-client-id', $location);
        $this->assertNotNull(session('oauth_google_state'));
    }

    public function test_redirect_returns_to_login_with_error_when_not_configured(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');
    }

    public function test_callback_creates_verified_customer_and_logs_in(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fake-google-access-token',
            ]),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-uid-12345',
                'email' => 'newgoogleuser@gmail.com',
                'name' => 'Tri Minh',
                'email_verified' => true,
            ]),
        ]);

        $response = $this->withSession(['oauth_google_state' => 'valid-state-token'])
            ->get(route('auth.google.callback', [
                'code' => 'auth-code-xyz',
                'state' => 'valid-state-token',
            ]));

        $response->assertRedirect(route('home'));

        $user = User::where('email', 'newgoogleuser@gmail.com')->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-uid-12345', $user->google_id);
        $this->assertSame('customer', $user->role);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_callback_links_existing_unverified_account_by_email(): void
    {
        $existing = User::factory()->unverified()->create([
            'email' => 'existing@gmail.com',
            'username' => 'existing_member',
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'fake-google-access-token',
            ]),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-uid-99999',
                'email' => 'existing@gmail.com',
                'name' => 'Existing Member',
                'email_verified' => true,
            ]),
        ]);

        $this->withSession(['oauth_google_state' => 'state-abc'])
            ->get(route('auth.google.callback', [
                'code' => 'code-123',
                'state' => 'state-abc',
            ]))
            ->assertRedirect(route('home'));

        $existing->refresh();
        $this->assertAuthenticatedAs($existing);
        $this->assertSame('google-uid-99999', $existing->google_id);
        $this->assertNotNull($existing->email_verified_at);
    }

    public function test_callback_rejects_mismatched_state(): void
    {
        $this->withSession(['oauth_google_state' => 'expected-state'])
            ->get(route('auth.google.callback', [
                'code' => 'code-123',
                'state' => 'tampered-state',
            ]))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');

        $this->assertGuest();
    }
}
