<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductSearchSuggestionTest extends TestCase
{
    use RefreshDatabase;

    private function category(string $name, ?Category $parent = null): Category
    {
        return Category::create([
            'name' => $name, 'slug' => 'category-'.uniqid(),
            'parent_id' => $parent?->getKey(), 'is_active' => true,
        ]);
    }

    private function product(string $name, ?Category $category = null, array $attributes = []): Product
    {
        $vendor = Vendor::create([
            'user_id' => User::factory()->create()->getKey(),
            'store_name' => 'Store '.uniqid(), 'status' => 'approved',
        ]);

        return Product::create([
            'name' => $name, 'slug' => 'product-'.uniqid(), 'price' => '12.50',
            'category_id' => ($category ?? $this->category('Equipment'))->getKey(),
            'vendor_id' => $vendor->getKey(), 'stock' => 5,
            'status' => 'approved', 'is_active' => true, ...$attributes,
        ]);
    }

    public function test_guest_receives_bounded_products_images_prices_and_full_search_link(): void
    {
        foreach (range(1, 7) as $number) {
            $product = $this->product('Camera '.$number);
            $product->images()->create(['path' => 'camera.jpg', 'sort_order' => 0]);
        }
        $this->product('Unrelated');

        $response = $this->getJson(route('products.search-suggestions', ['q' => 'Camera']))->assertOk()
            ->assertJsonPath('count', 5);
        $html = $response->json('html');
        $this->assertStringContainsString('Camera 1', $html);
        $this->assertStringNotContainsString('Camera 6', $html);
        $this->assertStringNotContainsString('Unrelated', $html);
        $this->assertStringContainsString('$12.50', $html);
        $this->assertStringContainsString('/storage/camera.jpg', $html);
        $this->assertStringContainsString(route('products.index', ['q' => 'Camera']), $html);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_unpublished_disabled_and_inactive_store_products_are_not_suggested(): void
    {
        $this->product('Camera public');
        $this->product('Camera pending', attributes: ['status' => 'pending']);
        $this->product('Camera disabled', attributes: ['is_active' => false]);
        $this->product('Camera suspended')->vendor->update(['status' => 'suspended']);
        $this->product('Camera inactive category')->category->update(['is_active' => false]);

        $this->getJson(route('products.search-suggestions', ['q' => 'Camera']))
            ->assertOk()->assertJsonPath('count', 1);
    }

    public function test_category_scope_includes_descendants_by_slug_or_custom_key(): void
    {
        $parent = $this->category('Cameras');
        $child = $this->category('Camera accessories', $parent);
        $leaf = $this->category('Camera bags', $child);
        $this->product('Travel camera bag', $leaf);
        $this->product('Other camera bag');

        foreach ([$parent->slug, (string) $parent->getKey()] as $scope) {
            $response = $this->getJson(route('products.search-suggestions', ['q' => 'bag', 'category' => $scope]))
                ->assertOk()->assertJsonPath('count', 2);
            $this->assertStringContainsString('Travel camera bag', $response->json('html'));
            $this->assertStringNotContainsString('Other camera bag', $response->json('html'));
            $this->assertStringContainsString('category='.$scope, $response->json('html'));
        }

        $this->getJson(route('products.search-suggestions', ['q' => 'bag', 'category' => 'missing']))
            ->assertOk()->assertJsonPath('count', 0);
    }

    public function test_categories_are_bounded_active_and_scoped(): void
    {
        $parent = $this->category('Root');
        foreach (range(1, 5) as $number) {
            $this->category('Camera '.$number, $parent);
        }
        $this->category('Camera hidden', $parent)->update(['is_active' => false]);
        $this->category('Camera outside');

        $response = $this->getJson(route('products.search-suggestions', ['q' => 'Camera', 'category' => $parent->slug]))
            ->assertOk()->assertJsonPath('count', 3);
        $this->assertStringNotContainsString('Camera hidden', $response->json('html'));
        $this->assertStringNotContainsString('Camera outside', $response->json('html'));
    }

    public function test_short_and_blank_queries_do_not_list_the_catalog(): void
    {
        $this->product('Camera');
        foreach (['', ' ', 'a'] as $keyword) {
            $this->getJson(route('products.search-suggestions', ['q' => $keyword]))
                ->assertOk()->assertExactJson(['html' => '', 'count' => 0]);
        }
    }

    public function test_invalid_input_is_rejected_as_json(): void
    {
        foreach ([['q' => ['camera']], ['q' => str_repeat('a', 101)], ['q' => 'camera', 'category' => ['1']]] as $input) {
            $this->getJson(route('products.search-suggestions', $input))->assertUnprocessable();
        }
    }

    public function test_query_and_product_names_are_escaped_and_description_matches_are_supported(): void
    {
        $this->product('<script>camera</script>', attributes: ['description' => 'Travel optics']);
        $response = $this->getJson(route('products.search-suggestions', ['q' => 'optics']))->assertOk();
        $this->assertStringContainsString('&lt;script&gt;camera&lt;/script&gt;', $response->json('html'));
        $this->assertStringNotContainsString('<script>camera</script>', $response->json('html'));
        $response = $this->getJson(route('products.search-suggestions', ['q' => '<script>']))->assertOk();
        $this->assertStringNotContainsString('<script>', $response->json('html'));
    }

    public function test_suggestions_use_the_selected_display_currency_and_locale(): void
    {
        Http::preventStrayRequests();
        Http::fake(['open.er-api.com/*' => Http::response([
            'result' => 'success', 'base_code' => 'USD', 'rates' => ['VND' => 25000],
        ])]);
        $this->product('Camera');
        $response = $this->withSession(['display_currency' => 'VND', 'locale' => 'vi'])
            ->getJson(route('products.search-suggestions', ['q' => 'Camera']))->assertOk();
        $this->assertStringContainsString('312.500 ₫', $response->json('html'));
        $this->assertStringContainsString('Xem tất cả kết quả', $response->json('html'));
    }
}
