<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileAccountNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create());
    }

    public function test_account_contains_three_subtabs_and_preserves_existing_pane_links(): void
    {
        $response = $this->get(route('profile'))->assertOk()
            ->assertSee('Account settings')->assertSee('Profile details')->assertSee('Login &amp; security', false);
        $document = new DOMDocument;
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($document);

        $this->assertSame(4, $xpath->query('//*[@id="profileTabs"]//*[@role="tab"]')->length);
        $this->assertSame(3, $xpath->query('//*[@id="accountTabs"]//*[@role="tab"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="profileTabs"]//*[@id="tab-customer-info" and @data-bs-target="#pane-account"]')->length);
        foreach (['pane-customer', 'pane-billing', 'pane-security'] as $pane) {
            $this->assertSame(1, $xpath->query('//*[@id="pane-account"]//*[@id="'.$pane.'"]')->length);
            $this->assertSame(1, $xpath->query('//*[@id="'.$pane.'"]')->length);
        }
        foreach (['tab-billing', 'tab-security'] as $tab) {
            $this->assertSame(0, $xpath->query('//*[@id="profileTabs"]//*[@id="'.$tab.'"]')->length);
        }
        $this->assertSame(1, $xpath->query('//*[@id="pane-customer" and @aria-labelledby="tab-profile-details"]')->length);
    }

    public function test_password_and_profile_save_feedback_select_the_matching_account_subtab(): void
    {
        $this->withSession(['status' => 'password-updated'])->get(route('profile'))
            ->assertOk()->assertSee('data-initial-tab="tab-security"', false);
        $this->withSession(['status' => 'profile-updated'])->get(route('profile'))
            ->assertOk()->assertSee('data-initial-tab="tab-profile-details"', false);
    }

    public function test_billing_and_password_validation_select_the_matching_account_subtab(): void
    {
        $this->post(route('addresses.store'), [])->assertSessionHasErrors(['city'], null, 'billing');
        $this->withCookie(config('session.cookie'), session()->getId())->get(route('profile'))
            ->assertOk()->assertSee('data-initial-tab="tab-billing"', false);

        $this->put('/account/password', [])->assertSessionHasErrors(['current_password']);
        $this->withCookie(config('session.cookie'), session()->getId())->get(route('profile'))
            ->assertOk()->assertSee('data-initial-tab="tab-security"', false);
    }
}
