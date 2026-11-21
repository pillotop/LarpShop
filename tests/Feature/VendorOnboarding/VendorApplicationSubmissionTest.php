<?php

namespace Tests\Feature\VendorOnboarding;

use App\Enums\Vendors\VendorApplicationStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VendorApplicationSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_submission_creates_one_pending_store_and_duplicate_requests_reuse_it(): void
    {
        $application = VendorApplication::factory()->complete()->create();
        $this->actingAs($application->user);
        $endpoint = route('vendor-applications.submit', $application);
        $this->postJson($endpoint, ['confirmation' => true])->assertOk()
            ->assertJsonPath('application.status', 'submitted');
        $vendor = $application->fresh()->vendor;
        $this->assertSame('pending', $vendor->status);
        $this->assertSame($application->user_id, $vendor->user_id);
        $this->assertSame($application->store_name, $vendor->store_name);
        $this->assertNotNull($application->fresh()->submitted_at);
        $this->postJson($endpoint, ['confirmation' => true])->assertOk()
            ->assertJsonPath('application.vendor_id', $vendor->getKey());
        $this->assertDatabaseCount('vendors', 1);
        $this->assertSame('customer', $application->user->fresh()->role);
        $this->assertFalse($application->user->fresh()->isVendor());
    }

    public function test_submission_requires_confirmation_and_validates_stored_fields_not_posted_overrides(): void
    {
        $application = VendorApplication::factory()->create();
        $this->actingAs($application->user)->postJson(route('vendor-applications.submit', $application), [])
            ->assertUnprocessable()->assertJsonValidationErrors('confirmation');
        $this->postJson(route('vendor-applications.submit', $application), [
            'confirmation' => true, 'store_name' => 'Unsaved Store', 'status' => 'approved',
        ])->assertUnprocessable()->assertJsonValidationErrors(['store_name', 'contact_name', 'pickup_country_code']);
        $this->assertSame(VendorApplicationStatus::Draft, $application->fresh()->status);
        $this->assertDatabaseCount('vendors', 0);
    }

    public function test_business_name_and_selected_destinations_are_required_at_submission(): void
    {
        $application = VendorApplication::factory()->complete()->create([
            'seller_type' => 'business', 'delivery_mode' => 'selected_countries',
        ]);
        $this->actingAs($application->user)->postJson(route('vendor-applications.submit', $application), ['confirmation' => true])
            ->assertUnprocessable()->assertJsonValidationErrors(['business_name', 'destination_countries']);
        $this->patchJson(route('vendor-applications.update', $application), [
            'business_name' => 'Test Business', 'destination_countries' => ['VN', 'US'],
        ])->assertOk();
        $this->postJson(route('vendor-applications.submit', $application), ['confirmation' => true])->assertOk();
        $this->assertSame(2, $application->destinations()->count());
    }

    public function test_drafts_do_not_reserve_names_but_submission_enforces_vendor_uniqueness(): void
    {
        $owner = User::factory()->create();
        $first = VendorApplication::factory()->complete()->create(['user_id' => $owner->getKey(), 'store_name' => 'Shared Name']);
        $second = VendorApplication::factory()->complete()->create(['user_id' => $owner->getKey(), 'store_name' => 'Shared Name']);
        $this->actingAs($owner)->postJson(route('vendor-applications.submit', $first), ['confirmation' => true])->assertOk();
        $this->postJson(route('vendor-applications.submit', $second), ['confirmation' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('store_name');
        $this->assertSame(VendorApplicationStatus::Draft, $second->fresh()->status);
        $this->assertNull($second->fresh()->vendor_id);
        $this->assertDatabaseCount('vendors', 1);
        $this->patchJson(route('vendor-applications.update', $second), ['store_name' => 'Another Name'])->assertOk();
        $this->postJson(route('vendor-applications.submit', $second), ['confirmation' => true])->assertOk();
        $this->assertSame(2, $owner->vendors()->count());
    }

    public function test_submitted_and_approved_applications_cannot_be_edited(): void
    {
        $application = VendorApplication::factory()->complete()->create();
        $this->actingAs($application->user)->postJson(route('vendor-applications.submit', $application), ['confirmation' => true])->assertOk();
        $this->patchJson(route('vendor-applications.update', $application), ['store_name' => 'Changed'])
            ->assertUnprocessable()->assertJsonValidationErrors('application');
        $application->refresh()->forceFill(['status' => 'approved'])->save();
        $application->vendor->update(['status' => 'approved']);
        $this->patchJson(route('vendor-applications.update', $application), ['description' => 'Changed'])
            ->assertUnprocessable()->assertJsonValidationErrors('application');
        $this->postJson(route('vendor-applications.submit', $application), ['confirmation' => true])->assertUnprocessable();
        $this->assertDatabaseCount('vendors', 1);
    }

    public function test_rejected_application_resubmits_the_same_store_and_retains_previous_review_feedback(): void
    {
        $application = VendorApplication::factory()->complete()->create();
        $this->actingAs($application->user)->postJson(route('vendor-applications.submit', $application), ['confirmation' => true])->assertOk();
        $application->refresh()->forceFill([
            'status' => 'rejected', 'rejection_reason' => 'Please clarify the description.', 'reviewed_at' => now(),
        ])->save();
        $vendor = $application->vendor;
        $vendor->update(['status' => 'rejected']);
        $this->patchJson(route('vendor-applications.update', $application), ['description' => 'Clarified description'])
            ->assertOk();
        $this->assertNotSame('Clarified description', $vendor->fresh()->description);
        $this->postJson(route('vendor-applications.submit', $application), ['confirmation' => true])->assertOk()
            ->assertJsonPath('application.vendor_id', $vendor->getKey())
            ->assertJsonPath('application.rejection_reason', 'Please clarify the description.');
        $this->assertSame('pending', $vendor->fresh()->status);
        $this->assertSame('Clarified description', $vendor->fresh()->description);
        $this->assertDatabaseCount('vendors', 1);
    }

    public function test_inconsistent_or_foreign_vendor_links_cannot_ignore_name_uniqueness_or_modify_stores(): void
    {
        $application = VendorApplication::factory()->complete()->create();
        $other = User::factory()->create()->vendors()->create(['store_name' => 'Other Store', 'status' => 'rejected']);
        $application->forceFill(['status' => 'rejected', 'vendor_id' => $other->getKey()])->save();
        $this->actingAs($application->user)->postJson(route('vendor-applications.submit', $application), ['confirmation' => true])
            ->assertNotFound();
        $this->assertSame('Other Store', $other->fresh()->store_name);
    }

    public function test_existing_stores_and_customer_addresses_remain_unchanged(): void
    {
        $owner = User::factory()->create();
        foreach (['active', 'approved', 'pending', 'rejected', 'suspended'] as $status) {
            $owner->vendors()->create(['store_name' => 'Existing '.$status, 'status' => $status]);
        }
        $application = VendorApplication::factory()->complete()->create(['user_id' => $owner->getKey()]);
        $this->actingAs($owner)->postJson(route('vendor-applications.submit', $application), ['confirmation' => true])->assertOk();
        $this->assertSame(6, $owner->vendors()->count());
        $this->assertSame(2, $owner->vendors()->active()->count());
        $this->assertSame(0, $owner->addresses()->count());
        $this->assertSame(1, $owner->vendorApplications()->count());
    }

    public function test_database_name_collision_after_validation_returns_a_field_error_and_rolls_back(): void
    {
        $application = VendorApplication::factory()->complete()->create();
        // Simulate a name being taken after the validation query and before the INSERT.
        Vendor::creating(fn (Vendor $vendor) => DB::table('vendors')->insert($vendor->getAttributes()));
        $this->actingAs($application->user)->postJson(route('vendor-applications.submit', $application), ['confirmation' => true])
            ->assertUnprocessable()->assertJsonValidationErrors('store_name');
        $this->assertDatabaseCount('vendors', 0);
        $this->assertNull($application->fresh()->vendor_id);
        $this->assertSame(VendorApplicationStatus::Draft, $application->fresh()->status);
    }

    public function test_failure_when_linking_the_application_leaves_no_partial_store(): void
    {
        $application = VendorApplication::factory()->complete()->create();
        VendorApplication::updating(fn () => throw new \RuntimeException('Simulated linking failure'));
        $this->actingAs($application->user)->postJson(route('vendor-applications.submit', $application), ['confirmation' => true])
            ->assertServerError();
        $this->assertDatabaseCount('vendors', 0);
        $this->assertNull($application->fresh()->vendor_id);
        $this->assertSame(VendorApplicationStatus::Draft, $application->fresh()->status);
    }
}
