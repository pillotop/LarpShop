<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminProductModerationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function makeVendor(string $status = 'approved'): int
    {
        $owner = User::factory()->create();

        return DB::table('vendors')->insertGetId([
            'user_id' => $owner->user_id, 'store_name' => 'Store '.uniqid(), 'status' => $status,
            'created_at' => now(), 'updated_at' => now(),
        ], 'vendor_id');
    }

    private function makeProduct(string $name, string $status = 'pending', ?int $vendorId = null): int
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Cat '.uniqid(), 'slug' => 'cat-'.uniqid(), 'created_at' => now(), 'updated_at' => now(),
        ], 'category_id');

        return DB::table('products')->insertGetId([
            'category_id' => $categoryId, 'vendor_id' => $vendorId ?? $this->makeVendor(),
            'name' => $name, 'slug' => 'p-'.uniqid(), 'price' => 10, 'stock' => 5, 'status' => $status,
            'created_at' => now(), 'updated_at' => now(),
        ], 'product_id');
    }

    public function test_non_admins_cannot_access_product_moderation(): void
    {
        $id = $this->makeProduct('Sword');

        $this->get('/admin/products/moderation')->assertRedirect('/login');

        $customer = User::factory()->create(['role' => 'customer']);
        $this->actingAs($customer)->get('/admin/products/moderation')->assertForbidden();
        $this->actingAs($customer)->patch("/admin/products/{$id}/approve")->assertForbidden();
        $this->actingAs($customer)->patch("/admin/products/{$id}/reject")->assertForbidden();

        $this->assertDatabaseHas('products', ['product_id' => $id, 'status' => 'pending']);
    }

    public function test_existing_products_default_to_approved(): void
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'C', 'slug' => 'c', 'created_at' => now(), 'updated_at' => now(),
        ], 'category_id');
        $id = DB::table('products')->insertGetId([
            'category_id' => $categoryId, 'vendor_id' => $this->makeVendor(), 'name' => 'Legacy',
            'slug' => 'legacy', 'price' => 1, 'stock' => 1, 'created_at' => now(), 'updated_at' => now(),
        ], 'product_id');

        $this->assertDatabaseHas('products', ['product_id' => $id, 'status' => 'approved']);
    }

    public function test_admin_can_list_filter_and_search_products(): void
    {
        $this->makeProduct('Pending Sword', 'pending');
        $this->makeProduct('Approved Shield', 'approved');
        $this->makeProduct('Rejected Bow', 'rejected');

        $admin = $this->admin();

        // Default view shows pending items.
        $this->actingAs($admin)->get('/admin/products/moderation')
            ->assertOk()->assertSee('Pending Sword')->assertDontSee('Approved Shield')->assertDontSee('Rejected Bow');

        $this->actingAs($admin)->get('/admin/products/moderation?status=rejected')
            ->assertOk()->assertSee('Rejected Bow')->assertDontSee('Pending Sword');

        $this->actingAs($admin)->get('/admin/products/moderation?status=all&q=Shield')
            ->assertOk()->assertSee('Approved Shield')->assertDontSee('Rejected Bow');
    }

    public function test_admin_can_filter_by_vendor(): void
    {
        $vendorA = $this->makeVendor();
        $this->makeProduct('Alpha Item', 'pending', $vendorA);
        $this->makeProduct('Beta Item', 'pending');

        $this->actingAs($this->admin())->get("/admin/products/moderation?status=all&vendor_id={$vendorA}")
            ->assertOk()->assertSee('Alpha Item')->assertDontSee('Beta Item');
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $this->actingAs($this->admin())->get('/admin/products/moderation?status=bogus')->assertSessionHasErrors('status');
        $this->actingAs($this->admin())->get('/admin/products/moderation?vendor_id=abc')->assertSessionHasErrors('vendor_id');
    }

    public function test_admin_can_approve_pending_product(): void
    {
        $id = $this->makeProduct('Sword', 'pending');

        $this->actingAs($this->admin())->patch("/admin/products/{$id}/approve")->assertSessionHas('success');

        $this->assertDatabaseHas('products', ['product_id' => $id, 'status' => 'approved']);
    }

    public function test_admin_can_reject_product(): void
    {
        $id = $this->makeProduct('Fake Sword', 'approved');

        $this->actingAs($this->admin())->patch("/admin/products/{$id}/reject")->assertSessionHas('success');

        $this->assertDatabaseHas('products', ['product_id' => $id, 'status' => 'rejected']);
    }

    public function test_cannot_approve_product_of_inactive_store(): void
    {
        $id = $this->makeProduct('Sword', 'pending', $this->makeVendor('suspended'));

        $this->actingAs($this->admin())->patch("/admin/products/{$id}/approve")->assertSessionHasErrors('product');

        $this->assertDatabaseHas('products', ['product_id' => $id, 'status' => 'pending']);
    }

    public function test_unknown_product_returns_404(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->patch('/admin/products/99999/approve')->assertNotFound();
        $this->actingAs($admin)->patch('/admin/products/99999/reject')->assertNotFound();
    }

    public function test_unapproved_products_are_hidden_from_public_catalog(): void
    {
        $this->makeProduct('Visible Helm', 'approved');
        $this->makeProduct('Pending Helm', 'pending');
        $this->makeProduct('Rejected Helm', 'rejected');

        $this->get('/products?q=Helm')
            ->assertOk()->assertSee('Visible Helm')->assertDontSee('Pending Helm')->assertDontSee('Rejected Helm');

        $this->get('/')->assertOk()->assertDontSee('Pending Helm')->assertDontSee('Rejected Helm');
    }
}
