<?php

namespace Tests\Feature\Vendor;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VendorProductImageManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    private function createVendorAndProduct(): array
    {
        $user = User::factory()->create();
        $vendor = Vendor::create([
            'user_id' => $user->user_id,
            'store_name' => 'Armorer Guild '.uniqid(),
            'status' => 'active',
        ]);

        $category = Category::create([
            'name' => 'Helmets '.uniqid(),
            'slug' => 'helmets-'.uniqid(),
        ]);

        $product = Product::create([
            'vendor_id' => $vendor->vendor_id,
            'category_id' => $category->category_id,
            'name' => 'Steel Barbute Helmet',
            'slug' => 'steel-barbute-helmet-'.uniqid(),
            'price' => 149.99,
            'stock' => 10,
        ]);

        return [$user, $vendor, $product];
    }

    public function test_vendor_can_view_product_images_page(): void
    {
        [$user, $vendor, $product] = $this->createVendorAndProduct();

        ProductImage::create([
            'product_id' => $product->product_id,
            'path' => 'products/test1.jpg',
            'alt_text' => 'Front view',
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($user)->get(route('vendor.products.images.index', $product->product_id));

        $response->assertOk();
        $response->assertSee('Steel Barbute Helmet');
        $response->assertSee('Front view');
    }

    public function test_vendor_can_upload_images_for_product(): void
    {
        Storage::fake('public');
        [$user, $vendor, $product] = $this->createVendorAndProduct();

        $file1 = UploadedFile::fake()->create('helmet1.jpg', 600, 'image/jpeg');
        $file2 = UploadedFile::fake()->create('helmet2.png', 600, 'image/png');

        $response = $this->actingAs($user)->post(route('vendor.products.images.store', $product->product_id), [
            'images' => [$file1, $file2],
            'alt_text' => 'Side angle view',
        ]);

        $response->assertRedirect(route('vendor.products.images.index', $product->product_id));
        $response->assertSessionHas('success');

        $this->assertCount(2, $product->fresh()->images);
        $firstImage = $product->fresh()->images->first();
        Storage::disk('public')->assertExists($firstImage->path);
    }

    public function test_vendor_can_delete_product_image(): void
    {
        Storage::fake('public');
        [$user, $vendor, $product] = $this->createVendorAndProduct();

        $file = UploadedFile::fake()->create('to_delete.jpg', 300, 'image/jpeg');
        $path = $file->store('products', 'public');

        $image = ProductImage::create([
            'product_id' => $product->product_id,
            'path' => $path,
            'alt_text' => 'To be deleted',
            'sort_order' => 1,
        ]);

        Storage::disk('public')->assertExists($path);

        $response = $this->actingAs($user)->delete(route('vendor.products.images.destroy', [
            'product' => $product->product_id,
            'image' => $image->product_image_id,
        ]));

        $response->assertRedirect(route('vendor.products.images.index', $product->product_id));
        $this->assertDatabaseMissing('product_images', ['product_image_id' => $image->product_image_id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_vendor_can_set_primary_image(): void
    {
        [$user, $vendor, $product] = $this->createVendorAndProduct();

        $img1 = ProductImage::create([
            'product_id' => $product->product_id,
            'path' => 'products/img1.jpg',
            'sort_order' => 1,
        ]);

        $img2 = ProductImage::create([
            'product_id' => $product->product_id,
            'path' => 'products/img2.jpg',
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($user)->patch(route('vendor.products.images.primary', [
            'product' => $product->product_id,
            'image' => $img2->product_image_id,
        ]));

        $response->assertRedirect(route('vendor.products.images.index', $product->product_id));
        $img2->refresh();
        $this->assertSame(1, $img2->sort_order);
    }

    public function test_vendor_cannot_manage_other_vendor_product_images(): void
    {
        [$user1, $vendor1, $product1] = $this->createVendorAndProduct();
        [$user2, $vendor2, $product2] = $this->createVendorAndProduct();

        $response = $this->actingAs($user1)->get(route('vendor.products.images.index', $product2->product_id));

        $response->assertForbidden();
    }
}
