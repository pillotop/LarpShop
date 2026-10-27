<?php

namespace Tests\Feature\Vendor;

use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VendorCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    private function createActiveVendor(string $role = 'customer'): array
    {
        $user = User::factory()->create(['role' => $role]);
        $vendor = Vendor::create([
            'user_id' => $user->user_id,
            'store_name' => 'Mythic Gear '.uniqid(),
            'description' => 'Quality LARP gear',
            'status' => 'active',
        ]);

        return [$user, $vendor];
    }

    public function test_categories_table_does_not_contain_vendor_id_column(): void
    {
        $this->assertFalse(Schema::hasColumn('categories', 'vendor_id'));
        $this->assertTrue(Schema::hasColumn('categories', 'is_active'));
    }

    public function test_vendor_can_view_category_list(): void
    {
        [$user] = $this->createActiveVendor();

        Category::create([
            'name' => 'Leather Armor',
            'slug' => 'leather-armor',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('vendor.categories.index'));

        $response->assertOk();
        $response->assertSee('Leather Armor');
    }

    public function test_vendor_can_create_new_category(): void
    {
        [$user] = $this->createActiveVendor();

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
            'is_active' => 1,
        ]);
    }

    public function test_non_admin_vendor_cannot_edit_or_toggle_shared_categories(): void
    {
        [$user] = $this->createActiveVendor('customer');

        $category = Category::create([
            'name' => 'Shared Shields',
            'slug' => 'shared-shields',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('vendor.categories.edit', $category->category_id))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('vendor.categories.update', $category->category_id), [
                'name' => 'Tampered Category',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->patch(route('vendor.categories.toggle-status', $category->category_id))
            ->assertForbidden();

        $this->assertDatabaseHas('categories', [
            'category_id' => $category->category_id,
            'name' => 'Shared Shields',
            'is_active' => 1,
        ]);
    }

    public function test_admin_with_active_store_can_update_and_toggle_category(): void
    {
        [$admin] = $this->createActiveVendor('admin');

        $category = Category::create([
            'name' => 'Seasonal Capes',
            'slug' => 'seasonal-capes',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('vendor.categories.update', $category->category_id), [
                'name' => 'Royal Cloaks',
                'description' => 'Updated by admin',
            ])
            ->assertRedirect(route('vendor.categories.index'));

        $this->assertSame('Royal Cloaks', $category->fresh()->name);

        $this->actingAs($admin)
            ->patch(route('vendor.categories.toggle-status', $category->category_id))
            ->assertRedirect(route('vendor.categories.index'));

        $this->assertFalse($category->fresh()->is_active);
    }
}
