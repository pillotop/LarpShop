<?php

namespace Tests\Feature\VendorOnboarding;

use App\Enums\Vendors\VendorApplicationStatus;
use App\Models\User;
use App\Models\VendorApplication;
use App\Support\Countries\CountryCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VendorApplicationDraftTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_any_onboarding_endpoint(): void
    {
        foreach ([
            ['getJson', 'index', []], ['getJson', 'create', []],
            ['postJson', 'store', []], ['getJson', 'show', [1]],
            ['patchJson', 'update', [1]], ['postJson', 'submit', [1]],
        ] as [$method, $name, $parameters]) {
            $this->{$method}(route('vendor-applications.'.$name, $parameters))->assertUnauthorized();
        }
    }

    public function test_partial_drafts_do_not_create_stores_or_change_account_roles(): void
    {
        $user = User::factory()->create();
        $id = $this->actingAs($user)->postJson(route('vendor-applications.store'), ['contact_name' => 'Test Seller'])
            ->assertCreated()->assertJsonPath('application.status', 'draft')->json('application.vendor_application_id');
        $this->patchJson(route('vendor-applications.update', $id), ['store_name' => 'My Future Store'])
            ->assertOk()->assertJsonPath('application.contact_name', 'Test Seller');
        $this->getJson(route('vendor-applications.show', $id))->assertOk()
            ->assertJsonPath('application.store_name', 'My Future Store');
        $this->assertDatabaseCount('vendors', 0);
        $this->assertSame('customer', $user->fresh()->role);
        $this->assertFalse($user->fresh()->isVendor());
    }

    public function test_get_requests_are_read_only_and_lists_are_owner_scoped(): void
    {
        $mine = VendorApplication::factory()->create();
        $other = VendorApplication::factory()->complete()->create();
        $this->actingAs($mine->user)->getJson(route('vendor-applications.create'))->assertOk()
            ->assertJsonPath('defaults.contact_email', $mine->user->email)
            ->assertJsonPath('steps', ['seller', 'store', 'shipping', 'review']);
        $this->getJson(route('vendor-applications.index'))->assertOk()
            ->assertJsonPath('total', 1)->assertJsonPath('data.0.vendor_application_id', $mine->getKey())
            ->assertDontSee($other->contact_email);
        $this->getJson(route('vendor-applications.show', $mine))->assertOk();
        $this->assertDatabaseCount('vendor_applications', 2);
        $this->assertDatabaseCount('vendors', 0);
    }

    public function test_another_owner_cannot_read_update_or_submit_an_application(): void
    {
        $application = VendorApplication::factory()->complete()->create();
        $this->actingAs(User::factory()->create());
        $this->getJson(route('vendor-applications.show', $application))->assertNotFound();
        $this->patchJson(route('vendor-applications.update', $application), ['description' => 'Changed'])->assertNotFound();
        $this->postJson(route('vendor-applications.submit', $application), ['confirmation' => true])->assertNotFound();
        $this->assertSame(VendorApplicationStatus::Draft, $application->fresh()->status);
        $this->assertDatabaseCount('vendors', 0);
    }

    public function test_step_validation_uses_persisted_values_and_does_not_trust_the_navigation_hint(): void
    {
        $application = VendorApplication::factory()->create();
        $this->actingAs($application->user)->patchJson(route('vendor-applications.update', $application), [
            'validate_step' => 'seller', 'current_step' => 'store', 'contact_name' => 'Test Seller',
        ])->assertUnprocessable()->assertJsonValidationErrors(['seller_type', 'contact_email']);
        $this->assertNull($application->fresh()->contact_name);

        $complete = VendorApplication::factory()->complete()->create(['user_id' => $application->user_id]);
        $this->patchJson(route('vendor-applications.update', $complete), [
            'validate_step' => 'seller', 'current_step' => 'store',
        ])->assertOk()->assertJsonPath('application.current_step', 'store');
        $this->patchJson(route('vendor-applications.update', $application), ['current_step' => 'review'])->assertOk();
        $this->postJson(route('vendor-applications.submit', $application), ['confirmation' => true])
            ->assertUnprocessable()->assertJsonValidationErrors(['store_name', 'pickup_city']);
    }

    public static function invalidFields(): array
    {
        return [
            ['seller_type', 'admin'], ['seller_country_code', 'ZZ'],
            ['pickup_country_code', 'USA'], ['contact_email', 'not-an-email'],
            ['delivery_mode', 'express'], ['current_step', 'finished'],
        ];
    }

    #[DataProvider('invalidFields')]
    public function test_drafts_validate_supplied_fields(string $field, string $value): void
    {
        $this->actingAs(User::factory()->create())->postJson(route('vendor-applications.store'), [$field => $value])
            ->assertUnprocessable()->assertJsonValidationErrors($field);
        $this->assertDatabaseCount('vendor_applications', 0);
    }

    public function test_business_and_country_selections_are_normalized_and_stale_values_are_removed(): void
    {
        $application = VendorApplication::factory()->complete()->create();
        $endpoint = route('vendor-applications.update', $application);
        $this->actingAs($application->user)->patchJson($endpoint, [
            'seller_type' => 'business', 'business_name' => 'Test Business',
            'seller_country_code' => 'vn', 'delivery_mode' => 'selected_countries',
            'destination_countries' => ['us', 'VN', 'US'],
        ])->assertOk()->assertJsonPath('application.seller_country_code', 'VN');
        $this->assertSame(['US', 'VN'], $application->destinations()->orderBy('country_code')->pluck('country_code')->all());
        $this->patchJson($endpoint, ['seller_type' => 'individual', 'delivery_mode' => 'worldwide'])->assertOk();
        $this->assertNull($application->fresh()->business_name);
        $this->assertSame(0, $application->destinations()->count());
    }

    public function test_pickup_address_is_an_owned_snapshot_and_never_a_live_reference(): void
    {
        $application = VendorApplication::factory()->create();
        $address = $application->user->addresses()->create([
            'recipient_name' => 'Pickup Contact', 'phone_number' => '+84901234567',
            'address_line_1' => '123 Example Street', 'city' => 'Example City', 'country_code' => 'VN',
        ]);
        $this->actingAs($application->user)->patchJson(route('vendor-applications.update', $application), [
            'pickup_address_id' => $address->getKey(),
        ])->assertOk()->assertJsonPath('application.pickup_city', 'Example City');
        $address->update(['city' => 'Changed City']);
        $this->assertSame('Example City', $application->fresh()->pickup_city);
        $this->actingAs(User::factory()->create())->postJson(route('vendor-applications.store'), [
            'pickup_address_id' => $address->getKey(),
        ])->assertNotFound();
        $this->assertDatabaseCount('vendor_applications', 1);
    }

    public function test_client_cannot_set_ownership_review_status_or_storage_paths(): void
    {
        $this->actingAs(User::factory()->create())->postJson(route('vendor-applications.store'), [
            'user_id' => 1, 'vendor_id' => 1, 'status' => 'approved',
            'brand_path' => 'other/logo.png', 'reviewed_by' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors(['user_id', 'vendor_id', 'status', 'brand_path', 'reviewed_by']);
        $this->assertDatabaseCount('vendor_applications', 0);
    }

    public function test_country_catalog_has_matching_localized_iso_codes(): void
    {
        $catalog = app(CountryCatalog::class);
        $english = $catalog->all('en');
        $vietnamese = $catalog->all('vi');
        $this->assertCount(249, $catalog->codes());
        $this->assertSame('Vietnam', $english['VN']);
        $this->assertSame('Việt Nam', $vietnamese['VN']);
        $this->assertEqualsCanonicalizing(array_keys($english), array_keys($vietnamese));
        $this->assertSame($english, $catalog->all('unsupported'));
    }

    public function test_hosted_draft_writes_require_csrf_protection(): void
    {
        $this->actingAs(User::factory()->create());
        $this->app->instance('env', 'production');
        $this->postJson(route('vendor-applications.store'), ['store_name' => 'Test Store'])->assertStatus(419);
        $this->assertDatabaseCount('vendor_applications', 0);
    }

    public function test_vietnamese_feedback_is_localized(): void
    {
        $this->actingAs(User::factory()->create())->withSession(['locale' => 'vi'])
            ->postJson(route('vendor-applications.store'), ['contact_name' => 'Test Seller'])
            ->assertCreated()->assertJsonPath('message', 'Đã lưu bản nháp hồ sơ đăng ký.');
        $this->getJson(route('vendor-applications.create'))->assertJsonPath('countries.VN', 'Việt Nam');
    }
}
