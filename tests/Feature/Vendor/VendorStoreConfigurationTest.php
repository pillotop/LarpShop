<?php

namespace Tests\Feature\Vendor;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $vendor = Vendor::create([
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

    public function test_admin_can_view_and_update_vendor_store_configuration(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create();
        $vendor = Vendor::create([
            'user_id' => $owner->user_id,
            'store_name' => 'Vendor Under Review',
            'description' => 'Store waiting for admin adjustments',
            'status' => 'pending',
        ]);

        // Admin view edit page
        $editResponse = $this->actingAs($admin)->get(route('admin.vendors.edit', $vendor->vendor_id));
        $editResponse->assertOk();
        $editResponse->assertSee('Vendor Under Review');

        // Admin update configuration
        $updateResponse = $this->actingAs($admin)->put(route('admin.vendors.update', $vendor->vendor_id), [
            'store_name' => 'Admin Adjusted Store',
            'description' => 'Configured by administrator',
            'status' => 'approved',
        ]);

        $updateResponse->assertRedirect(route('admin.vendors.index'));
        $updateResponse->assertSessionHas('success');

        $vendor->refresh();
        $this->assertSame('Admin Adjusted Store', $vendor->store_name);
        $this->assertSame('approved', $vendor->status);
    }
}
