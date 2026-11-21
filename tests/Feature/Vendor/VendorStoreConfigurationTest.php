<?php

namespace Tests\Feature\Vendor;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorStoreConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_guest_is_redirected_from_vendor_store_settings(): void
    {
        $response = $this->get(route('vendor.store.settings'));
        $response->assertRedirect(route('login'));
    }

    public function test_non_vendor_is_redirected_to_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('vendor.store.settings'));

        $response->assertRedirect(route('profile'));
        $response->assertSessionHas('warning');
    }

    public function test_active_vendor_can_view_store_settings(): void
    {
        $user = User::factory()->create();
        Vendor::create([
            'user_id' => $user->user_id,
            'store_name' => 'Elven Armory',
            'description' => 'Fine crafted bows and cloaks',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->get(route('vendor.store.settings'));

        $response->assertOk();
        $response->assertSee('Elven Armory');
        $response->assertSee('Fine crafted bows and cloaks');
    }

    public function test_active_vendor_can_update_store_configuration(): void
    {
        $user = User::factory()->create();
        $vendor = Vendor::create([
            'user_id' => $user->user_id,
            'store_name' => 'Initial Store Name',
            'description' => 'Initial Description',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->put(route('vendor.store.settings.update'), [
            'store_name' => 'Updated Brand Store',
            'description' => 'Updated Store Description for customers',
        ]);

        $response->assertRedirect(route('vendor.store.settings'));
        $response->assertSessionHas('success');

        $vendor->refresh();
        $this->assertSame('Updated Brand Store', $vendor->store_name);
        $this->assertSame('Updated Store Description for customers', $vendor->description);
    }

    public function test_storefront_update_saves_image_and_description_for_the_requested_store(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $first = $user->vendors()->create(['store_name' => 'First Store', 'status' => 'active']);
        $second = $user->vendors()->create(['store_name' => 'Second Store', 'status' => 'active']);
        $image = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aN1sAAAAASUVORK5CYII=');
        $oldPath = UploadedFile::fake()->createWithContent('old.png', $image)->store('vendors/profiles', 'public');
        $second->brand_path = $oldPath;
        $second->save();

        $this->actingAs($user)
            ->from('/vendors/'.$second->vendor_id)
            ->patch(route('vendor.store.profile.update', $second), [
                'description' => 'Must not update description',
                'brand_image' => UploadedFile::fake()->createWithContent('brand.png', $image),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/vendors/'.$second->vendor_id);

        $second->refresh();
        $this->assertNull($second->description);
        Storage::disk('public')->assertExists($second->brand_path);
        Storage::disk('public')->assertMissing($oldPath);
        $this->assertNull($first->fresh()->brand_path);
    }

    public function test_storefront_update_cannot_fall_back_to_a_different_owned_store(): void
    {
        $user = User::factory()->create();
        $ownStore = $user->vendors()->create(['store_name' => 'Owned Store', 'status' => 'active']);
        $otherUser = User::factory()->create();
        $otherStore = $otherUser->vendors()->create(['store_name' => 'Other Store', 'status' => 'active']);

        $this->actingAs($user)
            ->put(route('vendor.store.settings.update', ['vendor_id' => $otherStore->vendor_id]), [
                'store_name' => 'Unexpected change',
            ])
            ->assertForbidden();

        $this->assertSame('Owned Store', $ownStore->fresh()->store_name);
        $this->assertSame('Other Store', $otherStore->fresh()->store_name);
    }

    public function test_description_update_preserves_image_and_name_and_allows_clearing(): void
    {
        $user = User::factory()->create();
        $vendor = $user->vendors()->create(['store_name' => 'Description Store', 'status' => 'active']);
        $vendor->brand_path = 'vendors/profiles/existing.png';
        $vendor->save();

        foreach (['New description', ''] as $description) {
            $this->actingAs($user)
                ->patch(route('vendor.store.description.update', $vendor), [
                    'description' => $description,
                    'store_name' => 'Ignored name',
                ])
                ->assertSessionHasNoErrors()
                ->assertRedirect();

            $vendor->refresh();
            $this->assertSame($description ?: null, $vendor->description);
            $this->assertSame('Description Store', $vendor->store_name);
            $this->assertSame('vendors/profiles/existing.png', $vendor->brand_path);
        }
    }

    public function test_separate_store_updates_reject_other_owners(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $owner->vendors()->create(['store_name' => 'Own Store', 'status' => 'active']);
        $vendor = $other->vendors()->create(['store_name' => 'Protected Store', 'status' => 'active']);

        foreach (['profile', 'description', 'name'] as $section) {
            $this->actingAs($owner)
                ->patch(route('vendor.store.'.$section.'.update', $vendor), [])
                ->assertForbidden();
        }
    }

    public function test_separate_store_updates_require_their_fields(): void
    {
        $owner = User::factory()->create();
        $vendor = $owner->vendors()->create(['store_name' => 'Required Store', 'status' => 'active']);

        foreach (['profile' => 'brand_image', 'description' => 'description', 'name' => 'store_name'] as $section => $field) {
            $this->actingAs($owner)
                ->patch(route('vendor.store.'.$section.'.update', $vendor), [])
                ->assertSessionHasErrors($field);
        }
    }

    public function test_store_name_update_is_independent_and_enforces_unique_names(): void
    {
        $owner = User::factory()->create();
        $vendor = $owner->vendors()->create([
            'store_name' => 'Original Name', 'description' => 'Keep description', 'status' => 'approved',
        ]);
        $vendor->brand_path = 'vendors/profiles/keep.png';
        $vendor->save();
        $owner->vendors()->create(['store_name' => 'Taken Name', 'status' => 'active']);

        foreach (['Original Name', 'New Store Name'] as $name) {
            $this->actingAs($owner)
                ->patch(route('vendor.store.name.update', $vendor), [
                    'store_name' => $name,
                    'description' => 'Ignored description',
                ])
                ->assertSessionHasNoErrors()
                ->assertRedirect();
        }

        $vendor->refresh();
        $this->assertSame('New Store Name', $vendor->store_name);
        $this->assertSame('Keep description', $vendor->description);
        $this->assertSame('vendors/profiles/keep.png', $vendor->brand_path);
        $this->assertSame('approved', $vendor->status);

        foreach (['Taken Name', 'X', str_repeat('x', 101)] as $name) {
            $this->actingAs($owner)
                ->patch(route('vendor.store.name.update', $vendor), ['store_name' => $name])
                ->assertSessionHasErrors('store_name');
        }

        $this->assertSame('New Store Name', $vendor->fresh()->store_name);
    }

    public function test_multi_store_owner_can_switch_active_store(): void
    {
        $user = User::factory()->create();
        $store1 = Vendor::create([
            'user_id' => $user->user_id,
            'store_name' => 'First Forge',
            'status' => 'active',
        ]);
        $store2 = Vendor::create([
            'user_id' => $user->user_id,
            'store_name' => 'Second Alchemy',
            'status' => 'approved',
        ]);

        // Switch via POST /vendor/store/switch
        $this->actingAs($user)
            ->from(route('vendor.store.settings'))
            ->post(route('vendor.store.switch'), ['vendor_id' => $store2->vendor_id])
            ->assertRedirect(route('vendor.store.settings'))
            ->assertSessionHas('active_vendor_id', $store2->vendor_id);

        // Subsequent update applies to the selected store (#2), not store #1
        $this->actingAs($user)
            ->withSession(['active_vendor_id' => $store2->vendor_id])
            ->put(route('vendor.store.settings.update'), [
                'store_name' => 'Second Alchemy Updated',
                'description' => 'Potions and elixirs',
            ])
            ->assertRedirect(route('vendor.store.settings'));

        $this->assertSame('First Forge', $store1->fresh()->store_name);
        $this->assertSame('Second Alchemy Updated', $store2->fresh()->store_name);
    }

    public function test_vendor_cannot_switch_to_another_users_store(): void
    {
        $user1 = User::factory()->create();
        Vendor::create([
            'user_id' => $user1->user_id,
            'store_name' => 'User 1 Store',
            'status' => 'active',
        ]);

        $user2 = User::factory()->create();
        $otherStore = Vendor::create([
            'user_id' => $user2->user_id,
            'store_name' => 'User 2 Store',
            'status' => 'active',
        ]);

        $this->actingAs($user1)
            ->post(route('vendor.store.switch'), ['vendor_id' => $otherStore->vendor_id])
            ->assertNotFound();
    }

    public function test_admin_can_view_and_update_vendor_store_configuration(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();
        $vendor = Vendor::create([
            'user_id' => $owner->user_id,
            'store_name' => 'Vendor Under Review',
            'description' => 'Store waiting for admin adjustments',
            'status' => 'approved',
        ]);

        $editResponse = $this->actingAs($admin)->get(route('admin.vendors.edit', $vendor->vendor_id));
        $editResponse->assertOk();
        $editResponse->assertSee('Vendor Under Review');

        $updateResponse = $this->actingAs($admin)->put(route('admin.vendors.update', $vendor->vendor_id), [
            'store_name' => 'Admin Adjusted Store',
            'description' => 'Configured by administrator',
        ]);

        $updateResponse->assertRedirect(route('admin.vendors.index'));
        $updateResponse->assertSessionHas('success');

        $vendor->refresh();
        $this->assertSame('Admin Adjusted Store', $vendor->store_name);
        $this->assertSame('approved', $vendor->status);
    }
}
