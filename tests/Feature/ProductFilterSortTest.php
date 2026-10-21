<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductFilterSortTest extends TestCase
{
    use RefreshDatabase;

    private function createCategory(string $name, ?string $slug = null, ?int $parentId = null): int
    {
        return DB::table('categories')->insertGetId([
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => $slug ?? strtolower(str_replace(' ', '-', $name)).'-'.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ], 'category_id');
    }

    private function createVendorUser(string $role = 'vendor'): int
    {
        return DB::table('users')->insertGetId([
            'username' => 'user_'.uniqid(),
            'email' => uniqid().'@example.test',
            'password' => bcrypt('TestPassword123!'),
            'role' => $role,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'user_id');
    }

    private function createProductRecord(array $overrides = []): int
    {
        $userId = $this->createVendorUser();

        $vendorId = DB::table('vendors')->insertGetId([
            'user_id' => $userId,
            'store_name' => 'Store '.uniqid(),
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'vendor_id');

        $categoryId = $overrides['category_id'] ?? $this->createCategory('Default Category');

        return DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'vendor_id' => $vendorId,
            'name' => $overrides['name'] ?? 'Product '.uniqid(),
            'slug' => $overrides['slug'] ?? 'product-'.uniqid(),
            'description' => $overrides['description'] ?? 'Standard description',
            'price' => $overrides['price'] ?? 50.00,
            'stock' => $overrides['stock'] ?? 10,
            'created_at' => $overrides['created_at'] ?? now(),
            'updated_at' => $overrides['updated_at'] ?? now(),
        ], 'product_id');
    }

    public function test_filter_by_category_slug_includes_child_categories(): void
    {
        $electronicsId = $this->createCategory('Electronics', 'electronics');
        $camerasId = $this->createCategory('Cameras', 'cameras', $electronicsId);
        $fashionId = $this->createCategory('Fashion', 'fashion');

        $this->createProductRecord([
            'name' => 'Smart TV 55',
            'category_id' => $electronicsId,
        ]);
        $this->createProductRecord([
            'name' => 'Mirrorless Camera',
            'category_id' => $camerasId,
        ]);
        $this->createProductRecord([
            'name' => 'Denim Jacket',
            'category_id' => $fashionId,
        ]);

        $response = $this->get('/products?category=electronics');

        $response->assertOk();
        $response->assertSee('Smart TV 55');
        $response->assertSee('Mirrorless Camera');
        $response->assertDontSee('Denim Jacket');
    }

    public function test_filter_by_unknown_category_returns_empty_state_safely(): void
    {
        $this->createProductRecord(['name' => 'Existing Product']);

        $response = $this->get('/products?category=non-existent-category-slug');

        $response->assertOk();
        $response->assertSee('No products found');
        $response->assertDontSee('Existing Product');
    }

    public function test_filter_by_min_and_max_price_range(): void
    {
        $this->createProductRecord(['name' => 'Budget Cable', 'price' => 15.00]);
        $this->createProductRecord(['name' => 'Midrange Headset', 'price' => 75.00]);
        $this->createProductRecord(['name' => 'Luxury Laptop', 'price' => 1200.00]);

        $response = $this->get('/products?min_price=50&max_price=200');

        $response->assertOk();
        $response->assertSee('Midrange Headset');
        $response->assertDontSee('Budget Cable');
        $response->assertDontSee('Luxury Laptop');
    }

    public function test_filter_by_in_stock_excludes_out_of_stock_products(): void
    {
        $this->createProductRecord(['name' => 'Available Speaker', 'stock' => 5]);
        $this->createProductRecord(['name' => 'Unavailable Speaker', 'stock' => 0]);

        $response = $this->get('/products?in_stock=1');

        $response->assertOk();
        $response->assertSee('Available Speaker');
        $response->assertDontSee('Unavailable Speaker');
    }

    public function test_sort_by_price_ascending_and_descending(): void
    {
        $this->createProductRecord(['name' => 'Item Medium', 'price' => 50.00]);
        $this->createProductRecord(['name' => 'Item Cheap', 'price' => 10.00]);
        $this->createProductRecord(['name' => 'Item Expensive', 'price' => 200.00]);

        $ascResponse = $this->get('/products?sort=price_asc');
        $ascResponse->assertOk();
        $ascResponse->assertSeeInOrder(['Item Cheap', 'Item Medium', 'Item Expensive']);

        $descResponse = $this->get('/products?sort=price_desc');
        $descResponse->assertOk();
        $descResponse->assertSeeInOrder(['Item Expensive', 'Item Medium', 'Item Cheap']);
    }

    public function test_sort_by_name_ascending_and_descending(): void
    {
        $this->createProductRecord(['name' => 'Bravo Gadget']);
        $this->createProductRecord(['name' => 'Alpha Gadget']);
        $this->createProductRecord(['name' => 'Charlie Gadget']);

        $ascResponse = $this->get('/products?sort=name_asc');
        $ascResponse->assertOk();
        $ascResponse->assertSeeInOrder(['Alpha Gadget', 'Bravo Gadget', 'Charlie Gadget']);

        $descResponse = $this->get('/products?sort=name_desc');
        $descResponse->assertOk();
        $descResponse->assertSeeInOrder(['Charlie Gadget', 'Bravo Gadget', 'Alpha Gadget']);
    }

    public function test_sort_by_rating_descending(): void
    {
        $lowRatedId = $this->createProductRecord(['name' => 'Low Rated Item']);
        $highRatedId = $this->createProductRecord(['name' => 'High Rated Item']);

        $customerId = $this->createVendorUser('customer');

        DB::table('product_reviews')->insert([
            [
                'product_id' => $lowRatedId,
                'user_id' => $customerId,
                'rating' => 2,
                'comment' => 'Average',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $highRatedId,
                'user_id' => $customerId,
                'rating' => 5,
                'comment' => 'Excellent',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->get('/products?sort=rating_desc');

        $response->assertOk();
        $response->assertSeeInOrder(['High Rated Item', 'Low Rated Item']);
    }

    public function test_combined_search_filter_and_sort_work_together(): void
    {
        $audioCatId = $this->createCategory('Audio', 'audio');
        $otherCatId = $this->createCategory('Other', 'other');

        $this->createProductRecord([
            'name' => 'Pro Studio Microphone',
            'category_id' => $audioCatId,
            'price' => 150.00,
            'stock' => 8,
        ]);
        $this->createProductRecord([
            'name' => 'Entry Studio Microphone',
            'category_id' => $audioCatId,
            'price' => 60.00,
            'stock' => 12,
        ]);
        $this->createProductRecord([
            'name' => 'Sold Out Studio Microphone',
            'category_id' => $audioCatId,
            'price' => 80.00,
            'stock' => 0,
        ]);
        $this->createProductRecord([
            'name' => 'Studio Camera Stand',
            'category_id' => $otherCatId,
            'price' => 70.00,
            'stock' => 5,
        ]);

        $response = $this->get('/products?q=Studio&category=audio&min_price=50&max_price=200&in_stock=1&sort=price_asc');

        $response->assertOk();
        $response->assertSeeInOrder(['Entry Studio Microphone', 'Pro Studio Microphone']);
        $response->assertDontSee('Sold Out Studio Microphone');
        $response->assertDontSee('Studio Camera Stand');
        $response->assertSee('name="q" value="Studio"', false);
    }

    public function test_invalid_sort_value_is_rejected_by_allowlist(): void
    {
        $response = $this->get('/products?sort=price;DROP TABLE products--');

        $response->assertSessionHasErrors('sort');
    }

    public function test_negative_price_and_inverted_price_range_are_rejected(): void
    {
        $negativeResponse = $this->get('/products?min_price=-10');
        $negativeResponse->assertSessionHasErrors('min_price');

        $invertedResponse = $this->get('/products?min_price=200&max_price=50');
        $invertedResponse->assertSessionHasErrors('max_price');
    }
}
