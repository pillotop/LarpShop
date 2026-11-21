<?php

namespace Tests\Feature;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class LoginPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_has_one_shared_form_unique_ids_and_six_showcase_slides(): void
    {
        $response = $this->get(route('login'))->assertOk()
            ->assertViewIs('auth.pages.login')
            ->assertSee('images/auth/wordmark-vertical.svg')
            ->assertSee('images/auth/everyday-tech.png')
            ->assertDontSee('src=""', false)
            ->assertDontSee('vh-100')
            ->assertDontSee('id="auth-panel"', false);

        $html = $response->getContent();
        preg_match_all('/\bid="([^"]+)"/', $html, $matches);
        $this->assertCount(count(array_unique($matches[1])), $matches[1]);
        $this->assertSame(1, substr_count($html, 'id="auth-form"'));
        $this->assertSame(1, substr_count($html, 'class="carousel-item active"'));
        $this->assertSame(6, substr_count($html, 'class="auth-offer-media"'));
        $response->assertSee('Pause slideshow')->assertSee('Next banner');
        preg_match('/<button[^>]*class="auth-offer-control carousel-control-play-pause"[^>]*>/s', $html, $pauseButton);
        $this->assertStringContainsString('data-bs-target="#auth-offers-carousel"', $pauseButton[0]);
        $this->assertLessThan(strpos($html, '<footer'), strpos($html, '</main>'));
    }

    public function test_navigation_links_to_login_and_keeps_the_wishlist_drawer(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee('href="'.route('login').'"', false)
            ->assertDontSee('id="auth-drawer-trigger"', false)
            ->assertSee('id="auth-panel"', false);
    }

    public function test_successful_ajax_login_returns_the_original_protected_page(): void
    {
        $user = User::factory()->create(['password' => 'test-password']);
        $this->get(route('profile'))->assertRedirect(route('login'));
        $this->get(route('login'))->assertOk();

        $this->postJson(route('login.store'), ['email' => $user->email, 'password' => 'test-password'])
            ->assertOk()->assertJsonPath('redirect', route('profile'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_without_verification_preserves_the_intended_page(): void
    {
        config(['auth.require_email_verification' => false]);
        $this->withSession(['url.intended' => route('profile')])->postJson(route('register.store'), [
            'email' => 'new@example.test', 'username' => 'new-buyer',
            'password' => 'test-password', 'password_confirmation' => 'test-password',
        ])->assertCreated()->assertJsonPath('redirect', route('profile'));
    }

    public function test_email_verification_preserves_the_intended_page(): void
    {
        Mail::fake();
        $this->withSession(['url.intended' => route('profile')])->postJson(route('register.store'), [
            'email' => 'new@example.test', 'username' => 'new-buyer',
            'password' => 'test-password', 'password_confirmation' => 'test-password',
        ])->assertStatus(202);

        $code = Mail::sent(VerificationCodeMail::class)->sole()->code;
        $this->postJson(route('verification.code.verify'), ['email' => 'new@example.test', 'code' => $code])
            ->assertOk()->assertJsonPath('redirect', route('profile'));
        $this->assertAuthenticated();
    }

    public function test_authenticated_customers_are_not_shown_the_guest_login_page(): void
    {
        $this->actingAs(User::factory()->create())->get(route('login'))->assertRedirect();
    }
}
