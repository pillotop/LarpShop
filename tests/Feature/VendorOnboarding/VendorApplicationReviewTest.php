<?php

namespace Tests\Feature\VendorOnboarding;

use App\Enums\Vendors\VendorApplicationStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorApplication;
use App\Services\Vendors\Onboarding\VendorOnboardingService;
use App\Services\Vendors\VendorManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class VendorApplicationReviewTest extends TestCase
{
    use RefreshDatabase;

    private function submitted(): VendorApplication
    {
        $application = VendorApplication::factory()->complete()->create();

        return app(VendorOnboardingService::class)->submit($application->user, $application->getKey());
    }

    public function test_admin_can_read_all_declared_fields_and_destinations_but_customers_cannot(): void
    {
        $application = $this->submitted();
        $application->destinations()->create(['country_code' => 'VN']);
        $admin = User::factory()->create(['role' => 'admin']);
        $url = route('admin.vendors.show', $application->vendor_id);
        $this->get($url)->assertRedirect(route('login'));
        $this->actingAs($application->user)->get($url)->assertForbidden();
        $this->actingAs($admin)->get($url)->assertOk()
            ->assertSee($application->contact_email)->assertSee($application->pickup_address_line_1)
            ->assertSee('Vietnam')->assertSee('name="rejection_reason"', false);
        $this->get(route('admin.vendors.index'))->assertOk()->assertSee($url, false);
    }

    public function test_approval_updates_both_records_and_review_metadata_without_changing_roles(): void
    {
        $application = $this->submitted();
        $application->rejection_reason = 'Previous feedback';
        $application->save();
        $admin = User::factory()->create(['role' => 'admin']);
        $url = route('admin.vendors.approve', $application->vendor_id);
        $this->actingAs($admin)->post($url)->assertRedirect();
        $application->refresh();
        $this->assertSame(VendorApplicationStatus::Approved, $application->status);
        $this->assertSame('approved', $application->vendor->status);
        $this->assertSame($admin->getKey(), $application->reviewed_by);
        $this->assertNotNull($application->reviewed_at);
        $this->assertNull($application->rejection_reason);
        $this->assertSame('customer', $application->user->role);
        $this->assertSame('admin', $admin->fresh()->role);
        $reviewedAt = $application->reviewed_at;
        $this->postJson($url)->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->assertEquals($reviewedAt, $application->fresh()->reviewed_at);
    }

    public function test_rejection_requires_reason_and_owner_can_correct_and_resubmit_same_store(): void
    {
        $application = $this->submitted();
        $admin = User::factory()->create(['role' => 'admin']);
        $url = route('admin.vendors.reject', $application->vendor_id);
        $this->actingAs($admin)->postJson($url, [])->assertUnprocessable()->assertJsonValidationErrors('rejection_reason');
        $this->postJson($url, ['rejection_reason' => str_repeat('x', 2001)])->assertUnprocessable();
        $this->assertSame('pending', $application->vendor->fresh()->status);
        $this->post($url, ['rejection_reason' => 'Clarify pickup address'])->assertRedirect();
        $application->refresh();
        $this->assertSame(VendorApplicationStatus::Rejected, $application->status);
        $this->assertSame('rejected', $application->vendor->status);
        $this->assertSame('Clarify pickup address', $application->rejection_reason);
        $this->actingAs($application->user)->get(route('profile'))->assertOk()
            ->assertSee(route('vendor-applications.show', $application), false);
        $this->patchJson(route('vendor-applications.update', $application), ['pickup_address_line_1' => 'Corrected address'])->assertOk();
        $this->postJson(route('vendor-applications.submit', $application), ['confirmation' => true])->assertOk();
        $this->assertSame('pending', $application->vendor->fresh()->status);
        $this->assertSame('Clarify pickup address', $application->fresh()->rejection_reason);
        $this->assertDatabaseCount('vendors', 1);
        $this->actingAs($admin)->post(route('admin.vendors.approve', $application->vendor_id))->assertRedirect();
        $this->assertNull($application->fresh()->rejection_reason);
    }

    public function test_review_and_availability_actions_guard_all_ineligible_states(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();
        $vendor = $owner->vendors()->create(['store_name' => 'Guarded legacy store']);
        $this->actingAs($admin);
        foreach (['pending', 'rejected', 'active', 'approved', 'suspended'] as $status) {
            $vendor->update(['status' => $status]);
            foreach (['approve', 'reject', 'suspend', 'reinstate'] as $action) {
                $eligible = match ($action) {
                    'approve', 'reject' => $status === 'pending',
                    'suspend' => in_array($status, ['active', 'approved']),
                    'reinstate' => $status === 'suspended',
                };
                if (! $eligible) {
                    $this->postJson(route('admin.vendors.'.$action, $vendor), ['rejection_reason' => 'Review note'])
                        ->assertUnprocessable()->assertJsonValidationErrors('status');
                    $this->assertSame($status, $vendor->fresh()->status);
                }
            }
        }
    }

    public function test_suspension_and_reinstatement_leave_approved_application_unchanged(): void
    {
        $application = $this->submitted();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.vendors.approve', $application->vendor_id));
        $snapshot = $application->fresh()->getAttributes();
        $this->post(route('admin.vendors.suspend', $application->vendor_id))->assertRedirect();
        $this->assertSame('suspended', $application->vendor->fresh()->status);
        $this->post(route('admin.vendors.reinstate', $application->vendor_id))->assertRedirect();
        $this->assertSame('approved', $application->vendor->fresh()->status);
        $this->assertSame($snapshot, $application->fresh()->getAttributes());
    }

    public function test_mismatched_application_state_prevents_review_and_configuration(): void
    {
        $application = $this->submitted();
        $application->status = VendorApplicationStatus::Draft;
        $application->save();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->postJson(route('admin.vendors.approve', $application->vendor_id))->assertUnprocessable();
        $application->vendor->update(['status' => 'approved']);
        $this->postJson(route('admin.vendors.suspend', $application->vendor_id))->assertUnprocessable();
        $this->putJson(route('admin.vendors.update', $application->vendor_id), ['store_name' => 'Bypass'])->assertUnprocessable();
    }

    public function test_generic_configuration_cannot_set_status_or_edit_pending_review_snapshot(): void
    {
        $application = $this->submitted();
        $admin = User::factory()->create(['role' => 'admin']);
        $url = route('admin.vendors.update', $application->vendor_id);
        $this->actingAs($admin)->putJson($url, ['store_name' => 'Bypass', 'status' => 'approved'])
            ->assertUnprocessable()->assertJsonValidationErrors('status');
        $this->putJson($url, ['store_name' => 'Bypass'])->assertUnprocessable();
        $this->assertSame($application->store_name, $application->vendor->fresh()->store_name);
        $this->get(route('admin.vendors.edit', $application->vendor_id))
            ->assertRedirect(route('admin.vendors.show', $application->vendor_id));
        $this->post(route('admin.vendors.approve', $application->vendor_id));
        $this->put($url, ['store_name' => 'Live store name', 'description' => 'Updated live description'])->assertRedirect();
        $this->assertSame('Live store name', $application->vendor->fresh()->store_name);
        $this->assertNotSame('Live store name', $application->fresh()->store_name);
    }

    public function test_legacy_pending_stores_remain_reviewable_without_invented_onboarding_fields(): void
    {
        $owner = User::factory()->create();
        $vendor = $owner->vendors()->create(['store_name' => 'Legacy pending', 'status' => 'pending']);
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.vendors.show', $vendor))->assertOk()->assertSee(__('onboarding.review.legacy'));
        $this->assertDatabaseCount('vendor_applications', 0);
        $this->post(route('admin.vendors.reject', $vendor), ['rejection_reason' => 'Missing contact information'])->assertRedirect();
        $application = $vendor->fresh()->application;
        $this->assertSame('Missing contact information', $application->rejection_reason);
        $this->assertNull($application->seller_country_code);
        $this->assertNull($application->submitted_at);
        $this->assertSame($owner->getKey(), $application->user_id);
        $other = $owner->vendors()->create(['store_name' => 'Other legacy pending', 'status' => 'pending']);
        $this->post(route('admin.vendors.approve', $other))->assertRedirect();
        $this->assertSame('approved', $other->fresh()->status);
        $this->assertNull($other->fresh()->application);
    }

    public function test_legacy_reapply_is_owned_idempotent_and_requires_new_fields_before_submission(): void
    {
        $owner = User::factory()->create();
        $vendor = $owner->vendors()->create(['store_name' => 'Old rejected', 'status' => 'rejected']);
        $url = route('profile.vendor.resubmit', $vendor);
        $this->actingAs(User::factory()->create())->postJson($url)->assertNotFound();
        $response = $this->actingAs($owner)->postJson($url, ['status' => 'approved', 'store_name' => 'Ignored']);
        $response->assertOk();
        $id = $response->json('application.vendor_application_id');
        $this->postJson($url)->assertOk()->assertJsonPath('application.vendor_application_id', $id);
        $this->assertDatabaseCount('vendor_applications', 1);
        $this->assertSame('rejected', $vendor->fresh()->status);
        $this->assertSame('Old rejected', $vendor->store_name);
        $this->postJson(route('vendor-applications.submit', $id), ['confirmation' => true])
            ->assertUnprocessable()->assertJsonValidationErrors(['seller_type', 'pickup_country_code']);
    }

    public function test_legacy_owner_can_complete_new_fields_and_reuse_the_existing_vendor(): void
    {
        $owner = User::factory()->create();
        $vendor = $owner->vendors()->create(['store_name' => 'Legacy resubmission', 'status' => 'rejected']);
        $this->actingAs($owner)->post(route('profile.vendor.resubmit', $vendor))->assertStatus(303);
        $application = $vendor->fresh()->application;
        $values = VendorApplication::factory()->complete()->make()->only($application->getFillable());
        $values['store_name'] = $vendor->store_name;
        $this->patchJson(route('vendor-applications.update', $application), $values)->assertOk();
        $this->postJson(route('vendor-applications.submit', $application), ['confirmation' => true])->assertOk();
        $this->assertSame($vendor->getKey(), $application->fresh()->vendor_id);
        $this->assertSame('pending', $vendor->fresh()->status);
        $this->assertDatabaseCount('vendors', 1);
    }

    public function test_admin_detail_is_localized_and_escapes_application_and_review_text(): void
    {
        $application = $this->submitted();
        $application->contact_name = '<script>alert(1)</script>';
        $application->rejection_reason = '<img src=x onerror=alert(2)>';
        $application->save();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->withSession(['locale' => 'vi'])
            ->get(route('admin.vendors.show', $application->vendor_id))->assertOk()
            ->assertSee('Xét duyệt hồ sơ cửa hàng')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('<img src=x onerror=alert(2)>', false);
    }

    public function test_non_admin_cannot_invoke_any_review_or_configuration_action(): void
    {
        $application = $this->submitted();
        $this->actingAs($application->user);
        foreach (['approve', 'reject', 'suspend', 'reinstate'] as $action) {
            $this->postJson(route('admin.vendors.'.$action, $application->vendor_id), ['rejection_reason' => 'Injected'])->assertForbidden();
        }
        $this->putJson(route('admin.vendors.update', $application->vendor_id), ['store_name' => 'Injected'])->assertForbidden();
        $this->assertSame('pending', $application->vendor->fresh()->status);
    }

    public function test_review_failure_rolls_back_vendor_and_application_together(): void
    {
        $application = $this->submitted();
        $admin = User::factory()->create(['role' => 'admin']);
        Vendor::updating(function (Vendor $vendor) {
            if ($vendor->isDirty('status')) {
                throw new RuntimeException('Simulated vendor write failure');
            }
        });
        try {
            $this->actingAs($admin)->postJson(route('admin.vendors.approve', $application->vendor_id))->assertStatus(500);
        } finally {
            Vendor::flushEventListeners();
        }
        $this->assertSame(VendorApplicationStatus::Submitted, $application->fresh()->status);
        $this->assertNull($application->fresh()->reviewed_at);
        $this->assertSame('pending', $application->vendor->fresh()->status);
    }

    public function test_live_logo_replacement_preserves_the_review_snapshot_image(): void
    {
        Storage::fake('public');
        $application = $this->submitted();
        $admin = User::factory()->create(['role' => 'admin']);
        $path = 'vendor-applications/'.$application->getKey().'/logos/original.jpg';
        Storage::disk('public')->put($path, 'original image fixture');
        $application->brand_path = $path;
        $application->save();
        $vendor = $application->vendor;
        $vendor->brand_path = $path;
        $vendor->save();
        $this->actingAs($admin)->post(route('admin.vendors.approve', $vendor));
        app(VendorManagementService::class)->updateProfileImage(
            $application->user, $vendor->getKey(), UploadedFile::fake()->image('new.jpg'),
        );
        Storage::disk('public')->assertExists($path);
        $this->assertSame($path, $application->fresh()->brand_path);
        $this->assertNotSame($path, $vendor->fresh()->brand_path);
    }
}
