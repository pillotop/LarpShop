<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductDetailTest extends TestCase
{
    use RefreshDatabase;

    private function createVendor(string $storeName = 'Crafts & Forges'): int
    {
        $userId = DB::table('users')->insertGetId([
            'username' => 'vendor_'.uniqid(),
            'email' => uniqid().'@vendor.test',
            'password' => bcrypt('password123'),
            'role' => 'vendor',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'user_id');

        return DB::table('vendors')->insertGetId([
            'user_id' => $userId,
            'store_name' => $storeName,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'vendor_id');
    }

    private function createCategory(string $name, string $slug, ?int $parentId = null): int
    {
        return DB::table('categories')->insertGetId([
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => $slug,
            'description' => 'Category '.$name,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'category_id');
    }

    private function createProduct(
        string $name,
        string $slug,
        int $categoryId,
        int $vendorId,
        int $stock = 10,
        float $price = 120.00,
        ?string $imagePath = null
    ): int {
        $productId = DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'vendor_id' => $vendorId,
            'name' => $name,
            'slug' => $slug,
            'description' => 'Detailed description for '.$name,
            'price' => $price,
            'stock' => $stock,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'product_id');

        if ($imagePath !== null) {
            DB::table('product_images')->insert([
                'product_id' => $productId,
                'path' => $imagePath,
                'alt_text' => $name.' preview',
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $productId;
    }

    public function test_product_detail_page_loads_successfully_with_canonical_slug(): void
    {
        $vendorId = $this->createVendor('Elven Armory');
        $catId = $this->createCategory('Shields', 'shields');
        $prodId = $this->createProduct('Mithril Kite Shield', 'mithril-kite-shield', $catId, $vendorId);

        $response = $this->get("/products/{$prodId}/mithril-kite-shield");

        $response->assertOk();
        $response->assertSee('Mithril Kite Shield');
        $response->assertSee('Elven Armory');
        $response->assertSee('Shields');
    }

    public function test_product_detail_redirects_to_canonical_slug_if_mismatched(): void
    {
        $vendorId = $this->createVendor('Smiths Guild');
        $catId = $this->createCategory('Swords', 'swords');
        $prodId = $this->createProduct('Steel Longsword', 'steel-longsword', $catId, $vendorId);

        $response = $this->get("/products/{$prodId}/wrong-slug");

        $response->assertRedirect("/products/{$prodId}/steel-longsword");
    }

    public function test_product_detail_displays_vendor_store_and_stock_status(): void
    {
        $vendorId = $this->createVendor('Dragon Forges');
        $catId = $this->createCategory('Axes', 'axes');
        $prodId = $this->createProduct('Battleaxe', 'battleaxe', $catId, $vendorId, stock: 7);

        $response = $this->get("/products/{$prodId}/battleaxe");

        $response->assertOk();
        $response->assertSee('Dragon Forges');
        $response->assertSee('In stock (7)');
    }

    public function test_product_detail_displays_out_of_stock_badge(): void
    {
        $vendorId = $this->createVendor('Dragon Forges');
        $catId = $this->createCategory('Bows', 'bows');
        $prodId = $this->createProduct('Yew Longbow', 'yew-longbow', $catId, $vendorId, stock: 0);

        $response = $this->get("/products/{$prodId}/yew-longbow");

        $response->assertOk();
        $response->assertSee('Out of stock');
    }

    public function test_product_detail_handles_product_without_images(): void
    {
        $vendorId = $this->createVendor('Arcane Oddities');
        $catId = $this->createCategory('Potions', 'potions');
        $prodId = $this->createProduct('Mana Elixir', 'mana-elixir', $catId, $vendorId, stock: 15, imagePath: null);

        $response = $this->get("/products/{$prodId}/mana-elixir");

        $response->assertOk();
        $response->assertSee('product-placeholder.jpg');
    }

    public function test_product_detail_displays_similar_products_from_same_category(): void
    {
        $vendorId = $this->createVendor('Wizards Supply');
        $catId = $this->createCategory('Staves', 'staves');

        $mainProdId = $this->createProduct('Oak Staff', 'oak-staff', $catId, $vendorId);
        $this->createProduct('Elderwood Staff', 'elderwood-staff', $catId, $vendorId);
        $this->createProduct('Crystal Staff', 'crystal-staff', $catId, $vendorId);

        // Product from different category should NOT show up in similar products
        $otherCatId = $this->createCategory('Daggers', 'daggers');
        $this->createProduct('Shadow Dagger', 'shadow-dagger', $otherCatId, $vendorId);

        $response = $this->get("/products/{$mainProdId}/oak-staff");

        $response->assertOk();
        $response->assertSee('Elderwood Staff');
        $response->assertSee('Crystal Staff');
        $response->assertDontSee('Shadow Dagger');
    }

    public function test_product_detail_excludes_current_product_from_similar_products(): void
    {
        $vendorId = $this->createVendor('Alchemist Guild');
        $catId = $this->createCategory('Herbs', 'herbs');

        $mainProdId = $this->createProduct('Healing Herb', 'healing-herb', $catId, $vendorId);

        $response = $this->get("/products/{$mainProdId}/healing-herb");

        $response->assertOk();
        $response->assertSee('No similar products found in this category.');
    }

    public function test_product_detail_returns_404_for_non_existent_product(): void
    {
        $response = $this->get('/products/999999/not-found');

        $response->assertNotFound();
    }
}
