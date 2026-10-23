<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountVendorTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_can_view_profile_page_with_store_details(): void
    {
        $user = User::factory()->create([
            'username' => 'blacksmith',
            'email' => 'blacksmith@larpshop.test',
        ]);

        $vendor = Vendor::create([
            'user_id' => $user->user_id,
            'store_name' => 'Dragon Forge Armory',
            'description' => 'Handcrafted foam swords and plate armor.',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response
            ->assertOk()
            ->assertSee('Dragon Forge Armory')
            ->assertSee('Handcrafted foam swords and plate armor.')
            ->assertSee('Customer');
    }

    public function test_vendor_can_update_store_information(): void
    {
        $user = User::factory()->create();

        $vendor = Vendor::create([
            'user_id' => $user->user_id,
            'store_name' => 'Old Forge Name',
            'description' => 'Old description text.',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->put(route('profile.vendor.update', $vendor->vendor_id), [
            'store_name' => 'Mythic Armory Crafts',
            'description' => 'High-grade LARP battle shields and leather boots.',
        ]);

        $response
            ->assertRedirect(route('profile'))
            ->assertSessionHas('success');

        $vendor->refresh();

        $this->assertSame('Mythic Armory Crafts', $vendor->store_name);
        $this->assertSame('High-grade LARP battle shields and leather boots.', $vendor->description);
    }

    public function test_customer_can_register_as_vendor(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $response = $this->actingAs($customer)->post(route('profile.vendor.register'), [
            'store_name' => 'Elven Woodwork Workshop',
            'description' => 'Bow staves and custom quivers.',
        ]);

        $response
            ->assertRedirect(route('profile'))
            ->assertSessionHas('success');

        $customer->refresh();

        $this->assertSame('customer', $customer->role);
        $vendor = $customer->vendors()->sole();
        $this->assertSame('Elven Woodwork Workshop', $vendor->store_name);
        $this->assertSame('Bow staves and custom quivers.', $vendor->description);
        $this->assertSame('pending', $vendor->status);
    }

    public function test_customer_vendor_registration_validates_store_name(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->post(route('profile.vendor.register'), [
            'store_name' => '',
            'description' => 'Some description',
        ]);

        $response->assertSessionHasErrors('store_name');
    }

    public function test_customer_vendor_registration_requires_unique_store_name(): void
    {
        $existingVendorUser = User::factory()->create();
        Vendor::create([
            'user_id' => $existingVendorUser->user_id,
            'store_name' => 'Existing Unique Armory',
            'status' => 'active',
        ]);

        $customer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($customer)->post(route('profile.vendor.register'), [
            'store_name' => 'Existing Unique Armory',
            'description' => 'Trying to use the same name',
        ]);

        $response->assertSessionHasErrors('store_name');
    }

    public function test_user_can_register_multiple_stores(): void
    {
        $user = User::factory()->create();
        Vendor::create([
            'user_id' => $user->user_id,
            'store_name' => 'First Store',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->post(route('profile.vendor.register'), [
            'store_name' => 'Second Store Attempt',
        ]);

        $response
            ->assertRedirect(route('profile'))
            ->assertSessionHas('success');

        $this->assertSame(2, $user->vendors()->count());
        $this->assertSame('customer', $user->refresh()->role);
    }

    public function test_update_targets_only_the_owned_store_in_the_url(): void
    {
        $user = User::factory()->create();
        $first = $user->vendors()->create(['store_name' => 'First Store', 'status' => 'active']);
        $second = $user->vendors()->create(['store_name' => 'Second Store', 'status' => 'approved']);

        $this->actingAs($user)->putJson(route('profile.vendor.update', $second->vendor_id), [
            'store_name' => 'Renamed Store',
            'user_id' => 999,
            'status' => 'rejected',
        ])->assertOk()->assertJsonPath('vendor.vendor_id', $second->vendor_id);

        $this->assertSame('First Store', $first->refresh()->store_name);
        $this->assertSame('Renamed Store', $second->refresh()->store_name);
        $this->assertSame($user->user_id, $second->user_id);
        $this->assertSame('approved', $second->status);
    }

    public function test_another_users_store_cannot_be_updated_or_resubmitted(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $store = $owner->vendors()->create(['store_name' => 'Private Store', 'status' => 'approved']);

        $this->actingAs($other)->putJson(route('profile.vendor.update', $store->vendor_id), [
            'store_name' => 'Stolen Store',
        ])->assertNotFound();

        $store->update(['status' => 'rejected']);
        $this->postJson(route('profile.vendor.resubmit', $store->vendor_id), [
            'store_name' => 'Stolen Store',
        ])->assertNotFound();

        $this->assertSame('Private Store', $store->refresh()->store_name);
        $this->assertSame('rejected', $store->status);
    }

    public function test_rejected_store_can_be_resubmitted_without_creating_another_store(): void
    {
        $user = User::factory()->create();
        $store = $user->vendors()->create(['store_name' => 'Retry Store', 'status' => 'rejected']);

        $this->actingAs($user)->postJson(route('profile.vendor.resubmit', $store->vendor_id), [
            'store_name' => 'Retry Store',
            'description' => 'Revised application',
        ])->assertOk();

        $this->assertSame(1, $user->vendors()->count());
        $this->assertSame('pending', $store->refresh()->status);
        $this->assertSame('Revised application', $store->description);
    }

    public function test_store_status_limits_editing_and_resubmission(): void
    {
        $user = User::factory()->create();
        $store = $user->vendors()->create(['store_name' => 'Status Store', 'status' => 'pending']);

        foreach (['pending', 'rejected', 'suspended'] as $status) {
            $store->update(['status' => $status]);
            $this->actingAs($user)->putJson(route('profile.vendor.update', $store->vendor_id), [
                'store_name' => 'Changed Store',
            ])->assertUnprocessable()->assertJsonValidationErrors('store_name');
        }

        foreach (['pending', 'active', 'approved', 'suspended'] as $status) {
            $store->update(['status' => $status]);
            $this->postJson(route('profile.vendor.resubmit', $store->vendor_id), [
                'store_name' => 'Changed Store',
            ])->assertUnprocessable()->assertJsonValidationErrors('store_name');
            $this->assertSame($status, $store->refresh()->status);
        }
    }

    public function test_store_name_validation_ignores_only_the_selected_store(): void
    {
        $user = User::factory()->create();
        $first = $user->vendors()->create(['store_name' => 'First Name', 'status' => 'active']);
        $second = $user->vendors()->create(['store_name' => 'Second Name', 'status' => 'active']);

        $this->actingAs($user)->putJson(route('profile.vendor.update', $second->vendor_id), [
            'store_name' => 'Second Name',
        ])->assertOk();
        $this->putJson(route('profile.vendor.update', $second->vendor_id), [
            'store_name' => $first->store_name,
        ])->assertUnprocessable()->assertJsonValidationErrors('store_name');
    }

    public function test_profile_shows_only_the_users_stores_and_restores_the_failed_form(): void
    {
        $user = User::factory()->create();
        $first = $user->vendors()->create(['store_name' => 'Owned First', 'status' => 'active']);
        $second = $user->vendors()->create(['store_name' => 'Owned Second', 'status' => 'active']);
        User::factory()->create()->vendors()->create(['store_name' => 'Other Owner Store']);

        $this->actingAs($user)->get(route('profile'))->assertOk()
            ->assertSee('Owned First')->assertSee('Owned Second')->assertDontSee('Other Owner Store');

        $this->from(route('profile'))->put(route('profile.vendor.update', $second->vendor_id), [
            'store_name' => '',
        ])->assertSessionHasErrors('store_name')
            ->assertSessionHas('_old_input.vendor_form', (string) $second->vendor_id);

        $this->get(route('profile'))->assertOk()
            ->assertSee('id="tab-vendor-'.$second->vendor_id.'"', false)
            ->assertSee('Owned First');
    }
}
