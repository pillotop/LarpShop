<?php

namespace Tests\Feature\Vendor;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorProductVariantTest extends TestCase
{
    use RefreshDatabase;

    private User $vendorOwner;

    private Vendor $vendor;

    private Category $category;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vendorOwner = User::factory()->create([
            'role' => 'customer',
        ]);

        $this->vendor = Vendor::create([
            'user_id' => $this->vendorOwner->user_id,
            'store_name' => 'Gamer Zone',
            'status' => 'approved',
        ]);

        $this->category = Category::create([
            'name' => 'Peripherals',
            'slug' => 'peripherals',
        ]);

        $this->product = Product::create([
            'vendor_id' => $this->vendor->vendor_id,
            'category_id' => $this->category->category_id,
            'name' => 'Custom Keyboard Case',
            'slug' => 'custom-keyboard-case',
            'description' => 'Aluminum case',
            'price' => 120.00,
            'stock' => 10,
            'status' => Product::STATUS_APPROVED,
        ]);
    }

    public function test_vendor_can_view_product_variants_page(): void
    {
        ProductVariant::create([
            'product_id' => $this->product->product_id,
            'name' => 'Anodized Red',
            'sku' => 'CASE-RED-01',
            'price' => 135.00,
            'stock' => 5,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->vendorOwner)
            ->get(route('vendor.products.variants.index', $this->product));

        $response->assertOk();
        $response->assertViewIs('vendor.products.variants.index');
        $response->assertSee('Anodized Red');
        $response->assertSee('CASE-RED-01');
        $response->assertSee('$135.00');
    }

    public function test_vendor_cannot_view_or_manage_variants_of_other_vendor_product(): void
    {
        $otherOwner = User::factory()->create(['role' => 'customer']);
        $otherVendor = Vendor::create([
            'user_id' => $otherOwner->user_id,
            'store_name' => 'Other Store',
            'status' => 'approved',
        ]);
        $otherProduct = Product::create([
            'vendor_id' => $otherVendor->vendor_id,
            'category_id' => $this->category->category_id,
            'name' => 'Other Keyboard',
            'slug' => 'other-keyboard',
            'price' => 99.00,
            'stock' => 5,
            'status' => Product::STATUS_APPROVED,
        ]);

        $response = $this->actingAs($this->vendorOwner)
            ->get(route('vendor.products.variants.index', $otherProduct));

        $response->assertForbidden();

        $postResponse = $this->actingAs($this->vendorOwner)
            ->post(route('vendor.products.variants.store', $otherProduct), [
                'name' => 'Hacked Variant',
                'price' => 10.00,
                'stock' => 10,
            ]);

        $postResponse->assertForbidden();
    }

    public function test_vendor_can_create_variant_for_owned_product(): void
    {
        $response = $this->actingAs($this->vendorOwner)
            ->post(route('vendor.products.variants.store', $this->product), [
                'name' => 'Midnight Blue',
                'sku' => 'CASE-BLUE-01',
                'price' => 140.00,
                'stock' => 8,
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('vendor.products.variants.index', $this->product));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $this->product->product_id,
            'name' => 'Midnight Blue',
            'sku' => 'CASE-BLUE-01',
            'price' => 140.00,
            'stock' => 8,
            'is_active' => true,
        ]);
    }

    public function test_variant_validation_rejects_missing_required_fields(): void
    {
        $response = $this->actingAs($this->vendorOwner)
            ->post(route('vendor.products.variants.store', $this->product), [
                'name' => '',
                'price' => -5,
                'stock' => -1,
            ]);

        $response->assertSessionHasErrors(['name', 'price', 'stock']);
    }

    public function test_vendor_can_update_product_variant(): void
    {
        $variant = ProductVariant::create([
            'product_id' => $this->product->product_id,
            'name' => 'Old Variant Name',
            'price' => 100.00,
            'stock' => 4,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->vendorOwner)
            ->put(route('vendor.products.variants.update', [$this->product, $variant]), [
                'name' => 'Updated Variant Name',
                'sku' => 'UPD-SKU-99',
                'price' => 115.50,
                'stock' => 12,
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('vendor.products.variants.index', $this->product));

        $this->assertDatabaseHas('product_variants', [
            'variant_id' => $variant->variant_id,
            'name' => 'Updated Variant Name',
            'sku' => 'UPD-SKU-99',
            'price' => 115.50,
            'stock' => 12,
        ]);
    }

    public function test_vendor_can_delete_product_variant(): void
    {
        $variant = ProductVariant::create([
            'product_id' => $this->product->product_id,
            'name' => 'To Be Deleted',
            'price' => 50.00,
            'stock' => 1,
        ]);

        $response = $this->actingAs($this->vendorOwner)
            ->delete(route('vendor.products.variants.destroy', [$this->product, $variant]));

        $response->assertRedirect(route('vendor.products.variants.index', $this->product));

        $this->assertDatabaseMissing('product_variants', [
            'variant_id' => $variant->variant_id,
        ]);
    }

    public function test_product_detail_page_renders_active_variants_for_customers(): void
    {
        ProductVariant::create([
            'product_id' => $this->product->product_id,
            'name' => 'Stealth Matte Black',
            'price' => 125.00,
            'stock' => 3,
            'is_active' => true,
        ]);

        $response = $this->get(route('products.show', [$this->product->product_id, $this->product->slug]));

        $response->assertOk();
        $response->assertSee('Stealth Matte Black');
        $response->assertSee('$125.00');
        $response->assertSee('3 left');
    }
}
