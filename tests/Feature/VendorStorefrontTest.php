<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VendorStorefrontTest extends TestCase
{
    use RefreshDatabase;

    private function createVendor(array $attributes = []): Vendor
    {
        $user = User::factory()->create();

        $vendorId = DB::table('vendors')->insertGetId(array_merge([
            'user_id' => $user->user_id,
            'store_name' => 'Medieval Armory',
            'description' => 'Fine LARP armors and crafts.',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        return Vendor::find($vendorId);
    }

    private function createCategory(string $name, string $slug): Category
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => $name,
            'slug' => $slug,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Category::find($categoryId);
    }

    private function createProduct(Vendor $vendor, Category $category, array $attributes = []): Product
    {
        $productId = DB::table('products')->insertGetId(array_merge([
            'vendor_id' => $vendor->vendor_id,
            'category_id' => $category->category_id,
            'name' => 'Steel Broadsword',
            'slug' => 'steel-broadsword',
            'description' => 'A well-balanced combat steel sword.',
            'price' => 75.00,
            'stock' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));

        return Product::find($productId);
    }

    public function test_can_view_active_vendor_storefront(): void
    {
        $vendor = $this->createVendor(['store_name' => 'Mythic Crafts']);
        $category = $this->createCategory('Weapons', 'weapons');

        $this->createProduct($vendor, $category, ['name' => 'Elven Longbow', 'slug' => 'elven-longbow']);
        $this->createProduct($vendor, $category, ['name' => 'Dwarven War Axe', 'slug' => 'dwarven-war-axe']);

        $response = $this->get(route('vendors.show', ['vendor' => $vendor->vendor_id]));

        $response->assertOk();
        $response->assertSee('Mythic Crafts');
        $response->assertSee('Elven Longbow');
        $response->assertSee('Dwarven War Axe');
    }

    public function test_storefront_does_not_display_products_from_other_vendors(): void
    {
        $vendorA = $this->createVendor(['store_name' => 'Vendor Alpha']);
        $vendorB = $this->createVendor(['store_name' => 'Vendor Beta']);
        $category = $this->createCategory('Armor', 'armor');

        $this->createProduct($vendorA, $category, ['name' => 'Leather Gauntlets', 'slug' => 'leather-gauntlets']);
        $this->createProduct($vendorB, $category, ['name' => 'Plate Greaves', 'slug' => 'plate-greaves']);

        $response = $this->get(route('vendors.show', ['vendor' => $vendorA->vendor_id]));

        $response->assertOk();
        $response->assertSee('Leather Gauntlets');
        $response->assertDontSee('Plate Greaves');
    }

    public function test_pending_or_inactive_vendor_returns_404(): void
    {
        $pendingVendor = $this->createVendor(['status' => 'pending']);

        $response = $this->get(route('vendors.show', ['vendor' => $pendingVendor->vendor_id]));

        $response->assertNotFound();
    }

    public function test_non_existent_vendor_returns_404(): void
    {
        $response = $this->get(route('vendors.show', ['vendor' => 99999]));

        $response->assertNotFound();
    }

    public function test_can_filter_vendor_products_by_category(): void
    {
        $vendor = $this->createVendor();
        $categorySwords = $this->createCategory('Swords', 'swords');
        $categoryShields = $this->createCategory('Shields', 'shields');

        $this->createProduct($vendor, $categorySwords, ['name' => 'Knight Sword', 'slug' => 'knight-sword']);
        $this->createProduct($vendor, $categoryShields, ['name' => 'Tower Shield', 'slug' => 'tower-shield']);

        $response = $this->get(route('vendors.show', [
            'vendor' => $vendor->vendor_id,
            'category_id' => $categorySwords->category_id,
        ]));

        $response->assertOk();
        $response->assertSee('Knight Sword');
        $response->assertDontSee('Tower Shield');
    }

    public function test_can_search_products_within_storefront(): void
    {
        $vendor = $this->createVendor();
        $category = $this->createCategory('Potions', 'potions');

        $this->createProduct($vendor, $category, ['name' => 'Health Elixir', 'slug' => 'health-elixir']);
        $this->createProduct($vendor, $category, ['name' => 'Mana Draught', 'slug' => 'mana-draught']);

        $response = $this->get(route('vendors.show', [
            'vendor' => $vendor->vendor_id,
            'q' => 'Health',
        ]));

        $response->assertOk();
        $response->assertSee('Health Elixir');
        $response->assertDontSee('Mana Draught');
    }
}
