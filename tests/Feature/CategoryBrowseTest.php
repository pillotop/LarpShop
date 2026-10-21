<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CategoryBrowseTest extends TestCase
{
    use RefreshDatabase;

    private function createCategory(string $name, string $slug, ?int $parentId = null): int
    {
        return DB::table('categories')->insertGetId([
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => $slug,
            'description' => 'Category description for '.$name,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'category_id');
    }

    private function createProduct(
        string $name,
        int $categoryId,
        int $stock = 10
    ): int {
        $userId = DB::table('users')->insertGetId([
            'username' => 'vendor_'.uniqid(),
            'email' => uniqid().'@example.test',
            'password' => bcrypt('password123'),
            'role' => 'vendor',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'user_id');

        $vendorId = DB::table('vendors')->insertGetId([
            'user_id' => $userId,
            'store_name' => 'Category Test Store',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'vendor_id');

        return DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'vendor_id' => $vendorId,
            'name' => $name,
            'slug' => 'prod-'.uniqid(),
            'description' => 'Description of '.$name,
            'price' => 79.50,
            'stock' => $stock,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'product_id');
    }

    public function test_category_page_loads_successfully_by_slug(): void
    {
        $catId = $this->createCategory('Helmets', 'helmets');
        $this->createProduct('Iron Helmet', $catId);

        $response = $this->get('/categories/helmets');

        $response->assertOk();
        $response->assertSee('Helmets');
        $response->assertSee('Iron Helmet');
    }

    public function test_category_page_returns_404_for_non_existent_category(): void
    {
        $response = $this->get('/categories/non-existent-category');

        $response->assertNotFound();
    }

    public function test_category_displays_products_from_subcategories(): void
    {
        $parentCatId = $this->createCategory('Armor', 'armor');
        $childCatId = $this->createCategory('Chainmail', 'chainmail', parentId: $parentCatId);

        $this->createProduct('Reinforced Chainmail Shirt', $childCatId);

        // Browsing parent category should include child products via getAllCategoryIds()
        $response = $this->get('/categories/armor');

        $response->assertOk();
        $response->assertSee('Reinforced Chainmail Shirt');
    }

    public function test_category_displays_empty_state_when_no_products(): void
    {
        $this->createCategory('Empty Category', 'empty-category');

        $response = $this->get('/categories/empty-category');

        $response->assertOk();
        $response->assertSee('No products found in this category');
    }

    public function test_category_renders_subcategories_and_links(): void
    {
        $parentCatId = $this->createCategory('Costumes', 'costumes');
        $this->createCategory('Elven Robes', 'elven-robes', parentId: $parentCatId);

        $response = $this->get('/categories/costumes');

        $response->assertOk();
        $response->assertSee('Elven Robes');
        $response->assertSee(route('categories.show', ['slug' => 'elven-robes']));
    }
}
