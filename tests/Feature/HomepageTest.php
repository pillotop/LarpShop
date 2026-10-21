<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use RefreshDatabase;

    private function createCategory(string $name, ?int $parentId = null): int
    {
        return DB::table('categories')->insertGetId([
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => 'cat-'.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ], 'category_id');
    }

    private function createProduct(
        string $name,
        int $categoryId,
        int $stock = 10,
        ?string $imagePath = null
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
            'store_name' => 'Larp Store',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'vendor_id');

        $productId = DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'vendor_id' => $vendorId,
            'name' => $name,
            'slug' => 'slug-'.uniqid(),
            'description' => 'A featured item description',
            'price' => 49.99,
            'stock' => $stock,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'product_id');

        if ($imagePath !== null) {
            DB::table('product_images')->insert([
                'product_id' => $productId,
                'path' => $imagePath,
                'alt_text' => $name,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $productId;
    }

    public function test_homepage_loads_successfully(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }

    public function test_homepage_renders_categories_and_in_stock_products(): void
    {
        $catId = $this->createCategory('Armor & Shields');
        $this->createProduct('Knight Steel Plate', $catId, stock: 5);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Armor & Shields');
        $response->assertSee('Knight Steel Plate');
    }

    public function test_homepage_excludes_out_of_stock_products(): void
    {
        $catId = $this->createCategory('Weapons');
        $this->createProduct('In Stock Broadsword', $catId, stock: 4);
        $this->createProduct('Out of Stock Dagger', $catId, stock: 0);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('In Stock Broadsword');
        $response->assertDontSee('Out of Stock Dagger');
    }

    public function test_homepage_renders_product_without_image_gracefully(): void
    {
        $catId = $this->createCategory('Accessories');
        $this->createProduct('Imageless Magic Ring', $catId, stock: 3, imagePath: null);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Imageless Magic Ring');
        $response->assertSee('product-placeholder.jpg');
    }
}
