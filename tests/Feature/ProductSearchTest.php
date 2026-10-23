<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductSearchTest extends TestCase
{
    use RefreshDatabase;

    private function createProduct(
        string $name,
        string $description = '',
        int $stock = 10
    ): int {
        $userId = DB::table('users')->insertGetId([
            'username' => 'vendor_'.uniqid(),
            'email' => uniqid().'@example.test',
            'password' => bcrypt('TestPassword123!'),
            'role' => 'customer',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'user_id');

        $vendorId = DB::table('vendors')->insertGetId([
            'user_id' => $userId,
            'store_name' => 'Test Store',
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ], 'vendor_id');

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Electronics',
            'slug' => 'electronics-'.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ], 'category_id');

        return DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'vendor_id' => $vendorId,
            'name' => $name,
            'slug' => 'test-product-'.uniqid(),
            'description' => $description,
            'price' => 99.99,
            'stock' => $stock,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'product_id');
    }

    public function test_products_page_loads_successfully(): void
    {
        $response = $this->get('/products');

        $response->assertOk();
        $response->assertSee('Explore Products');
        $response->assertSee('id="dropdown-portal"', false);
    }

    public function test_search_finds_matching_product_name(): void
    {
        $this->createProduct('Smart Home Hub');
        $this->createProduct('Gaming Keyboard');

        $response = $this->get('/products?q=smart');

        $response->assertOk();
        $response->assertSee('Smart Home Hub');
        $response->assertDontSee('Gaming Keyboard');
    }

    public function test_search_finds_matching_description(): void
    {
        $this->createProduct(
            'Home Controller',
            'Smart automation device'
        );

        $response = $this->get('/products?q=automation');

        $response->assertOk();
        $response->assertSee('Home Controller');
    }

    public function test_search_returns_no_results_message(): void
    {
        $this->createProduct('Wireless Mouse');

        $response = $this->get('/products?q=notfound123');

        $response->assertOk();
        $response->assertSee('No products found');
        $response->assertDontSee('Wireless Mouse');
    }

    public function test_empty_keyword_returns_products(): void
    {
        $this->createProduct('Laptop Stand');

        $response = $this->get('/products?q=');

        $response->assertOk();
        $response->assertSee('Laptop Stand');
    }

    public function test_long_keyword_is_rejected(): void
    {
        $response = $this->get('/products?q='.str_repeat('a', 101));

        $response->assertSessionHasErrors('q');
    }

    public function test_search_keyword_is_escaped_in_html(): void
    {
        $keyword = '<script>alert(1)</script>';

        $response = $this->get('/products?q='.urlencode($keyword));

        $response->assertOk();
        $response->assertDontSee($keyword, false);
        $response->assertSee(e($keyword), false);
    }

    public function test_search_displays_out_of_stock_badge(): void
    {
        $this->createProduct('Sold Out Device', 'Description here', 0);

        $response = $this->get('/products?q=Sold');

        $response->assertOk();
        $response->assertSee('Sold Out Device');
        $response->assertSee('Out of Stock');
    }

    public function test_clear_search_link_is_visible_when_keyword_present(): void
    {
        $response = $this->get('/products?q=test');

        $response->assertOk();
        $response->assertSee('Clear Search');
    }

    public function test_whitespace_only_keyword_shows_full_catalog(): void
    {
        $this->createProduct('Desk Lamp');

        $response = $this->get('/products?q='.urlencode('   '));

        $response->assertOk();
        $response->assertSee('Desk Lamp');
        $response->assertDontSee('Clear Search');
    }

    public function test_search_is_case_insensitive(): void
    {
        $this->createProduct('Mechanical Keyboard');

        $response = $this->get('/products?q=KEYBOARD');

        $response->assertOk();
        $response->assertSee('Mechanical Keyboard');
    }

    public function test_keyword_stays_in_search_input(): void
    {
        $response = $this->get('/products?q=lamp');

        $response->assertOk();
        $response->assertSee('name="q"', false);
        $response->assertSee('value="lamp"', false);
    }

    public function test_array_keyword_is_rejected(): void
    {
        $response = $this->get('/products?q[]=lamp');

        $response->assertSessionHasErrors('q');
    }
}
