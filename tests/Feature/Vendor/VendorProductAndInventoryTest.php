<?php

namespace Tests\Feature\Vendor;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorProductAndInventoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    private function createVendorAndCategory(): array
    {
        $user = User::factory()->create();
        $vendor = Vendor::create([
            'user_id' => $user->user_id,
            'store_name' => 'Ironforge Workshop '.uniqid(),
            'description' => 'Custom LARP weaponry and armor',
            'status' => 'active',
        ]);

        $category = Category::create([
            'name' => 'Weaponry '.uniqid(),
            'slug' => 'weaponry-'.uniqid(),
            'is_active' => true,
        ]);

        return [$user, $vendor, $category];
    }

    public function test_vendor_can_create_new_product_listing_with_images(): void
    {
        Storage::fake('public');
        [$user, $vendor, $category] = $this->createVendorAndCategory();

        $this->actingAs($user)
            ->get(route('vendor.products.create'))
            ->assertOk()
            ->assertSee('Create New Product Listing');

        $image = UploadedFile::fake()->create('sword.jpg', 500, 'image/jpeg');

        $response = $this->actingAs($user)->post(route('vendor.products.store'), [
            'name' => 'Runeblade Greatsword',
            'category_id' => $category->category_id,
            'price' => 189.50,
            'stock' => 15,
            'description' => 'Hand-painted foam greatsword with fiberglass core.',
            'is_active' => '1',
            'images' => [$image],
            'alt_text' => 'Runeblade front view',
        ]);

        $response->assertRedirect(route('vendor.products.index'));
        $response->assertSessionHas('success');

        $product = Product::where('name', 'Runeblade Greatsword')->first();
        $this->assertNotNull($product);
        $this->assertSame($vendor->vendor_id, $product->vendor_id);
        $this->assertSame('runeblade-greatsword', $product->slug);
        $this->assertSame(15, $product->stock);
        $this->assertTrue($product->is_active);
        $this->assertCount(1, $product->images);
    }

    public function test_vendor_can_update_own_product_listing(): void
    {
        [$user, $vendor, $category] = $this->createVendorAndCategory();

        $product = Product::create([
            'vendor_id' => $vendor->vendor_id,
            'category_id' => $category->category_id,
            'name' => 'Old Dagger',
            'slug' => 'old-dagger',
            'price' => 25.00,
            'stock' => 5,
            'status' => Product::STATUS_APPROVED,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('vendor.products.edit', $product->product_id))
            ->assertOk()
            ->assertSee('Old Dagger');

        $response = $this->actingAs($user)->put(route('vendor.products.update', $product->product_id), [
            'name' => 'Masterwork Assassin Dagger',
            'category_id' => $category->category_id,
            'price' => 39.99,
            'stock' => 20,
            'description' => 'Balanced throwing and parrying dagger.',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('vendor.products.index'));

        $product->refresh();
        $this->assertSame('Masterwork Assassin Dagger', $product->name);
        $this->assertSame('39.99', (string) $product->price);
        $this->assertSame(20, $product->stock);
    }

    public function test_vendor_can_hide_and_show_product_from_public_catalog(): void
    {
        [$user, $vendor, $category] = $this->createVendorAndCategory();

        $product = Product::create([
            'vendor_id' => $vendor->vendor_id,
            'category_id' => $category->category_id,
            'name' => 'Stealth Cloak of Shadows',
            'slug' => 'stealth-cloak-of-shadows',
            'price' => 75.00,
            'stock' => 8,
            'status' => Product::STATUS_APPROVED,
            'is_active' => true,
        ]);

        // Initially visible on public search and vendor storefront
        $this->get(route('products.index', ['q' => 'Stealth Cloak']))
            ->assertOk()
            ->assertSee('Stealth Cloak of Shadows');

        // Hide product (UC-31 / UC-33)
        $this->actingAs($user)
            ->patch(route('vendor.products.toggle-active', $product->product_id))
            ->assertRedirect(route('vendor.products.index'));

        $this->assertFalse($product->fresh()->is_active);

        // Hidden product does not appear in public search, storefront, or product detail page
        $this->get(route('products.index', ['q' => 'Stealth Cloak']))
            ->assertOk()
            ->assertDontSee('Stealth Cloak of Shadows');

        $this->get(route('vendors.show', $vendor->vendor_id))
            ->assertOk()
            ->assertDontSee('Stealth Cloak of Shadows');

        $this->get(route('products.show', [$product->product_id, $product->slug]))
            ->assertNotFound();

        // Re-enable visibility
        $this->actingAs($user)
            ->patch(route('vendor.products.toggle-active', $product->product_id))
            ->assertRedirect(route('vendor.products.index'));

        $this->assertTrue($product->fresh()->is_active);

        $this->get(route('products.index', ['q' => 'Stealth Cloak']))
            ->assertOk()
            ->assertSee('Stealth Cloak of Shadows');
    }

    public function test_vendor_can_update_product_inventory_stock_and_filter_availability(): void
    {
        [$user, $vendor, $category] = $this->createVendorAndCategory();

        $product = Product::create([
            'vendor_id' => $vendor->vendor_id,
            'category_id' => $category->category_id,
            'name' => 'Steel Buckler',
            'slug' => 'steel-buckler',
            'price' => 59.00,
            'stock' => 12,
            'status' => Product::STATUS_APPROVED,
            'is_active' => true,
        ]);

        // Update stock to 0 (Out of stock)
        $this->actingAs($user)
            ->patch(route('vendor.products.update-stock', $product->product_id), [
                'stock' => 0,
            ])
            ->assertRedirect(route('vendor.products.index'));

        $this->assertSame(0, $product->fresh()->stock);

        // Filter by out_of_stock shows the product
        $this->actingAs($user)
            ->get(route('vendor.products.index', ['availability' => 'out_of_stock']))
            ->assertOk()
            ->assertSee('Steel Buckler');

        // Filter by in_stock does not show the out-of-stock product
        $this->actingAs($user)
            ->get(route('vendor.products.index', ['availability' => 'in_stock']))
            ->assertOk()
            ->assertDontSee('Steel Buckler');
    }

    public function test_vendor_cannot_modify_other_vendors_product_or_stock(): void
    {
        [$user1] = $this->createVendorAndCategory();
        [, $vendor2, $category2] = $this->createVendorAndCategory();

        $otherProduct = Product::create([
            'vendor_id' => $vendor2->vendor_id,
            'category_id' => $category2->category_id,
            'name' => 'Protected Armor',
            'slug' => 'protected-armor',
            'price' => 250.00,
            'stock' => 10,
            'status' => Product::STATUS_APPROVED,
            'is_active' => true,
        ]);

        $this->actingAs($user1)
            ->get(route('vendor.products.edit', $otherProduct->product_id))
            ->assertForbidden();

        $this->actingAs($user1)
            ->put(route('vendor.products.update', $otherProduct->product_id), [
                'name' => 'Hacked Armor',
                'category_id' => $category2->category_id,
                'price' => 1.00,
                'stock' => 0,
            ])
            ->assertForbidden();

        $this->actingAs($user1)
            ->patch(route('vendor.products.toggle-active', $otherProduct->product_id))
            ->assertForbidden();

        $this->actingAs($user1)
            ->patch(route('vendor.products.update-stock', $otherProduct->product_id), [
                'stock' => 0,
            ])
            ->assertForbidden();

        $otherProduct->refresh();
        $this->assertSame('Protected Armor', $otherProduct->name);
        $this->assertSame(10, $otherProduct->stock);
        $this->assertTrue($otherProduct->is_active);
    }
}
