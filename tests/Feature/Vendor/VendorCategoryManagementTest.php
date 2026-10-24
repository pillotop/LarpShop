<?php

namespace Tests\Feature\Vendor;

use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    private function createActiveVendor(): array
    {
        $user = User::factory()->create();
        $vendor = Vendor::create([
            'user_id' => $user->user_id,
            'store_name' => 'Mythic Gear '.uniqid(),
            'description' => 'Quality LARP gear',
            'status' => 'active',
        ]);

        return [$user, $vendor];
    }

    public function test_vendor_can_view_category_list(): void
    {
        [$user, $vendor] = $this->createActiveVendor();

        $category = Category::create([
            'name' => 'Leather Armor',
            'slug' => 'leather-armor',
            'vendor_id' => $vendor->vendor_id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('vendor.categories.index'));

        $response->assertOk();
        $response->assertSee('Leather Armor');
    }

    public function test_vendor_can_create_new_category(): void
    {
        [$user, $vendor] = $this->createActiveVendor();

        $response = $this->actingAs($user)->post(route('vendor.categories.store'), [
            'name' => 'Foam Swords',
            'slug' => 'foam-swords',
            'description' => 'Battle-ready safe foam weaponry',
        ]);

        $response->assertRedirect(route('vendor.categories.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'name' => 'Foam Swords',
            'slug' => 'foam-swords',
            'vendor_id' => $vendor->vendor_id,
            'is_active' => 1,
        ]);
    }

    public function test_vendor_can_update_own_category(): void
    {
        [$user, $vendor] = $this->createActiveVendor();

        $category = Category::create([
            'name' => 'Old Shields',
            'slug' => 'old-shields',
            'vendor_id' => $vendor->vendor_id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->put(route('vendor.categories.update', $category->category_id), [
            'name' => 'Viking Round Shields',
            'description' => 'Reinforced wooden shields',
        ]);

        $response->assertRedirect(route('vendor.categories.index'));
        $response->assertSessionHas('success');

        $category->refresh();
        $this->assertSame('Viking Round Shields', $category->name);
        $this->assertSame('Reinforced wooden shields', $category->description);
    }

    public function test_vendor_cannot_update_other_vendor_category(): void
    {
        [$user1, $vendor1] = $this->createActiveVendor();
        [$user2, $vendor2] = $this->createActiveVendor();

        $category = Category::create([
            'name' => 'Vendor 2 Category',
            'slug' => 'vendor-2-cat',
            'vendor_id' => $vendor2->vendor_id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user1)->put(route('vendor.categories.update', $category->category_id), [
            'name' => 'Hacked Category',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('categories', ['name' => 'Hacked Category']);
    }

    public function test_vendor_can_toggle_category_active_status(): void
    {
        [$user, $vendor] = $this->createActiveVendor();

        $category = Category::create([
            'name' => 'Seasonal Capes',
            'slug' => 'seasonal-capes',
            'vendor_id' => $vendor->vendor_id,
            'is_active' => true,
        ]);

        // Toggle to inactive (disable)
        $response1 = $this->actingAs($user)->patch(route('vendor.categories.toggle-status', $category->category_id));
        $response1->assertRedirect(route('vendor.categories.index'));
        $this->assertFalse($category->fresh()->is_active);

        // Toggle to active (re-enable)
        $response2 = $this->actingAs($user)->patch(route('vendor.categories.toggle-status', $category->category_id));
        $response2->assertRedirect(route('vendor.categories.index'));
        $this->assertTrue($category->fresh()->is_active);
    }
}
