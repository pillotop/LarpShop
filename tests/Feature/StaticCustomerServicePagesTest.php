<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaticCustomerServicePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_help_page_renders_successfully(): void
    {
        $this->get(route('help'))
            ->assertOk()
            ->assertViewIs('static.customer-service.help')
            ->assertSee('How Can We Help You Today?')
            ->assertSee('Weapon Safety');
    }

    public function test_privacy_page_renders_successfully(): void
    {
        $this->get(route('privacy'))
            ->assertOk()
            ->assertViewIs('static.customer-service.privacy')
            ->assertSee('Your Privacy Matters to Us')
            ->assertSee('privacy@larpshop.com');
    }

    public function test_community_page_renders_successfully(): void
    {
        $this->get(route('community'))
            ->assertOk()
            ->assertViewIs('static.customer-service.community')
            ->assertSee('Gather at the Tavern')
            ->assertSee('Community Guidelines');
    }

    public function test_recalls_page_renders_successfully(): void
    {
        $this->get(route('recalls'))
            ->assertOk()
            ->assertViewIs('static.customer-service.recalls')
            ->assertSee('No active recalls.')
            ->assertSee('Report an Unsafe Product');
    }
}
