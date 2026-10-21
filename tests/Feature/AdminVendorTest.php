<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminVendorTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_admin_dashboard(): void
    {
        // Guest is redirected to login
        $guestResponse = $this->get('/admin');
        $guestResponse->assertRedirect('/login');

        // Customer gets 403 Forbidden
        $customer = User::factory()->create(['role' => 'customer']);
        $customerResponse = $this->actingAs($customer)->get('/admin');
        $customerResponse->assertForbidden();
    }

    public function test_admin_can_view_admin_dashboard_with_metrics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create a pending vendor application
        $customer = User::factory()->create(['role' => 'customer']);
        $pendingVendor = Vendor::create([
            'user_id' => $customer->user_id,
            'store_name' => 'Pending Leather Works',
            'description' => 'Armor crafting shop',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get('/admin');

        $response
            ->assertOk()
            ->assertSee('Bảng điều khiển quản trị')
            ->assertSee('Pending Leather Works')
            ->assertSee('Đơn Vendor chờ duyệt');
    }

    public function test_admin_can_view_vendor_management_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $customer = User::factory()->create(['role' => 'customer']);
        Vendor::create([
            'user_id' => $customer->user_id,
            'store_name' => 'Royal Shields Guild',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get('/admin/vendors');

        $response
            ->assertOk()
            ->assertSee('Royal Shields Guild')
            ->assertSee('Xét duyệt và Quản lý gian hàng');
    }

    public function test_admin_can_approve_pending_vendor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $customer = User::factory()->create(['role' => 'customer']);
        $vendor = Vendor::create([
            'user_id' => $customer->user_id,
            'store_name' => 'Gothic Armor Smithy',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post("/admin/vendors/{$vendor->vendor_id}/approve");

        $response
            ->assertRedirect()
            ->assertSessionHas('success');

        $vendor->refresh();
        $customer->refresh();

        $this->assertSame('approved', $vendor->status);
        $this->assertSame('vendor', $customer->role);
        $this->assertTrue($customer->isVendor());
    }

    public function test_admin_can_reject_pending_vendor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $customer = User::factory()->create(['role' => 'customer']);
        $vendor = Vendor::create([
            'user_id' => $customer->user_id,
            'store_name' => 'Fake Goods Store',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->post("/admin/vendors/{$vendor->vendor_id}/reject");

        $response
            ->assertRedirect()
            ->assertSessionHas('warning');

        $vendor->refresh();
        $customer->refresh();

        $this->assertSame('rejected', $vendor->status);
        $this->assertSame('customer', $customer->role);
        $this->assertFalse($customer->isVendor());
    }

    public function test_admin_can_suspend_active_vendor(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $vendorUser = User::factory()->vendor()->create();
        $vendor = Vendor::create([
            'user_id' => $vendorUser->user_id,
            'store_name' => 'Misbehaving Armory',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin)->post("/admin/vendors/{$vendor->vendor_id}/suspend");

        $response
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertSame('suspended', $vendor->refresh()->status);
    }
}
