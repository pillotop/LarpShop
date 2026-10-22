<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaticCompanyPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_renders_successfully(): void
    {
        $response = $this->get(route('about'));

        $response->assertOk();
        $response->assertViewIs('static.company.about');
        $response->assertSee('Crafted for Quests, Built for Heroes');
        $response->assertSee('Our Guiding Tenets');
        $response->assertSee('About LarpShop');
    }

    public function test_contact_page_renders_successfully(): void
    {
        $response = $this->get(route('contact'));

        $response->assertOk();
        $response->assertViewIs('static.company.contact');
        $response->assertSee('Send a Dispatch to the LarpShop Guild');
        $response->assertSee('support@larpshop.com');
        $response->assertSee('Frequently Asked Questions');
    }

    public function test_contact_form_submits_successfully_with_valid_data(): void
    {
        $response = $this->from(route('contact'))->post(route('contact.submit'), [
            'name' => 'Sir Cedric of Riverdale',
            'email' => 'cedric@example.test',
            'department' => 'support',
            'subject' => 'Shield Durability Inquiry',
            'message' => 'Greetings! I would like to know if your heater shields withstand heavy foam blows.',
        ]);

        $response->assertRedirect(route('contact'));
        $response->assertSessionHas('success');
    }

    public function test_contact_form_validation_rejects_empty_data(): void
    {
        $response = $this->from(route('contact'))->post(route('contact.submit'), [
            'name' => '',
            'email' => 'invalid-email',
            'department' => 'unknown-dept',
            'subject' => '',
            'message' => 'too short',
        ]);

        $response->assertRedirect(route('contact'));
        $response->assertSessionHasErrors(['name', 'email', 'department', 'subject', 'message']);
    }

    public function test_investors_page_renders_successfully(): void
    {
        $response = $this->get(route('investors'));

        $response->assertOk();
        $response->assertViewIs('static.company.investors');
        $response->assertSee('Powering the Global Renaissance of Roleplay Commerce');
        $response->assertSee('Platform Highlights');
        $response->assertSee('ir@larpshop.com');
    }

    public function test_blog_page_renders_successfully(): void
    {
        $response = $this->get(route('blog'));

        $response->assertOk();
        $response->assertViewIs('static.company.blog');
        $response->assertSee('The Tavern Chronicle');
        $response->assertSee('Tales, Crafting Guides');
        $response->assertSee('The Definitive Guide to Combat Foam Weapons');
    }

    public function test_press_page_renders_successfully(): void
    {
        $response = $this->get(route('press'));

        $response->assertOk();
        $response->assertViewIs('static.company.press');
        $response->assertSee('Official News');
        $response->assertSee('Official Press Releases');
        $response->assertSee('press@larpshop.com');
    }
}
