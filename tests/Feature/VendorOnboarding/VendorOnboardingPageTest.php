<?php

namespace Tests\Feature\VendorOnboarding;

use App\Models\User;
use App\Models\VendorApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Tests\TestCase;

class VendorOnboardingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_creation_page_has_four_steps_without_creating_an_application(): void
    {
        $this->actingAs(User::factory()->create())->get(route('vendor-applications.create'))->assertOk()
            ->assertSee('Set up your store')->assertSee('Save and exit')
            ->assertSee('Seller information')->assertSee('Review and submit')
            ->assertSee('name="brand_image"', false)->assertSee('name="pickup_city"', false)
            ->assertSee('vendor-onboarding-data')->assertDontSee('Credit card')
            ->assertSee('onboarding-language')->assertSee(route('locale.update'))
            ->assertSee('name="locale"', false)->assertSee('switchLocale($event)', false)
            ->assertSee('id="onboarding-locale"', false)->assertDontSee('<summary', false)
            ->assertSee('position-fixed bottom-0 end-0', false)
            ->assertSee('@change="$el.form.requestSubmit()"', false)
            ->assertSee('Seller type')->assertSee('Contact name');
        $this->assertDatabaseCount('vendor_applications', 0);
    }

    public function test_owner_can_resume_a_draft_with_saved_values_and_selected_countries(): void
    {
        $application = VendorApplication::factory()->complete()->create(['current_step' => 'shipping', 'delivery_mode' => 'selected_countries']);
        $application->destinations()->create(['country_code' => 'US']);
        $response = $this->actingAs($application->user)->get(route('vendor-applications.show', $application))
            ->assertOk()->assertSee('Save and exit');
        $data = $response->viewData('onboarding');
        $this->assertSame($application->getKey(), $data['applicationId']);
        $this->assertSame('shipping', $data['form']['current_step']->value);
        $this->assertSame(['US'], $data['form']['destination_countries']);
        $this->assertSame($application->contact_email, $data['form']['contact_email']);
    }

    public function test_only_owned_saved_addresses_are_available_in_the_page(): void
    {
        $user = User::factory()->create();
        $user->addresses()->create(['recipient_name' => 'Own Contact', 'address_line_1' => 'Own Street', 'city' => 'Test City', 'country_code' => 'VN']);
        User::factory()->create()->addresses()->create(['recipient_name' => 'Private Contact', 'address_line_1' => 'Private Street', 'city' => 'Test City', 'country_code' => 'VN']);
        $this->actingAs($user)->get(route('vendor-applications.create'))->assertOk()
            ->assertSee('Own Street')->assertDontSee('Private Street');
    }

    public function test_profile_links_to_onboarding_and_lists_only_owned_editable_applications(): void
    {
        $mine = VendorApplication::factory()->complete()->create();
        $other = VendorApplication::factory()->complete()->create();
        $this->actingAs($mine->user)->get(route('profile'))->assertOk()
            ->assertSee('href="'.route('vendor-applications.create').'"', false)
            ->assertSee('href="'.route('vendor-applications.show', $mine).'"', false)
            ->assertSee($mine->store_name)->assertDontSee($other->store_name)
            ->assertDontSee('id="tab-vendor-new"', false)
            ->assertDontSee('action="'.route('profile.vendor.register').'"', false);
    }

    public function test_submitted_application_is_read_only_and_rejected_application_can_be_corrected(): void
    {
        $application = VendorApplication::factory()->complete()->create();
        $this->actingAs($application->user)->postJson(route('vendor-applications.submit', $application), ['confirmation' => true])->assertOk();
        $this->get(route('vendor-applications.show', $application))->assertOk()
            ->assertSee('Awaiting review')->assertDontSee('Save and exit')->assertDontSee('name="brand_image"', false);
        $application->refresh()->forceFill(['status' => 'rejected', 'rejection_reason' => 'Clarify your store description.'])->save();
        $application->vendor->update(['status' => 'rejected']);
        $this->get(route('vendor-applications.show', $application))->assertOk()
            ->assertSee('Clarify your store description.')->assertSee('Save and exit');
    }

    public function test_multipart_empty_country_array_can_clear_previous_selections(): void
    {
        $application = VendorApplication::factory()->complete()->create(['delivery_mode' => 'selected_countries']);
        $application->destinations()->create(['country_code' => 'US']);
        $this->actingAs($application->user)->postJson(route('vendor-applications.update', $application), [
            '_method' => 'PATCH', 'destination_countries' => '[]',
        ])->assertOk()->assertJsonPath('application.destinations', []);
        $this->postJson(route('vendor-applications.submit', $application), ['confirmation' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('destination_countries');
    }

    public function test_page_is_localized_and_embedded_values_are_safe_for_script_context(): void
    {
        $application = VendorApplication::factory()->complete()->create(['store_name' => '</script><script>alert(1)</script>']);
        $this->actingAs($application->user)->withSession(['locale' => 'vi'])
            ->get(route('vendor-applications.show', $application))->assertOk()
            ->assertSee('Thiết lập cửa hàng')->assertSee('Lưu và thoát')
            ->assertDontSee('</script><script>alert(1)</script>', false);
        $this->assertSame(array_keys(Arr::dot(__('onboarding', [], 'en'))), array_keys(Arr::dot(__('onboarding', [], 'vi'))));
    }
}
