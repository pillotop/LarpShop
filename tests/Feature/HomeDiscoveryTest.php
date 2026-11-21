<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Queries\Products\ProductCatalogQuery;
use App\View\Components\Grid;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class HomeDiscoveryTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    private function another(Product $source, string $name, array $attributes = []): Product
    {
        $product = $source->replicate()->fill([
            'name' => $name, 'slug' => str($name)->slug()->toString(), ...$attributes,
        ]);
        $product->save();

        return $product;
    }

    public function test_initial_homepage_only_renders_the_discovery_shell(): void
    {
        $product = $this->product('Deferred discovery sword');
        $this->partialMock(ProductCatalogQuery::class)->shouldNotReceive('discoveryProducts');
        $this->get(route('home'))->assertOk()
            ->assertSee('data-category-discovery', false)
            ->assertSee(route('home.discovery'), false)
            ->assertSee('All Categories')
            ->assertSee('Equipment')
            ->assertDontSee($product->name);
    }

    public function test_default_and_expanded_results_have_fixed_limits_and_stable_order(): void
    {
        $product = $this->product('Product 0');
        for ($index = 1; $index < 28; $index++) {
            $this->another($product, "Product {$index}");
        }
        $collapsed = $this->get(route('home.discovery', ['limit' => 1000]))->assertOk()
            ->assertSee('data-has-more="true"', false)
            ->assertSee('data-expanded="false"', false);
        $this->assertCount(12, $collapsed->viewData('products'));
        $this->assertSame('Product 27', $collapsed->viewData('products')->first()->name);
        $expanded = $this->get(route('home.discovery', ['expanded' => 1]))->assertOk()
            ->assertSee('data-expanded="true"', false);
        $this->assertCount(24, $expanded->viewData('products'));
        $this->assertSame(
            $collapsed->viewData('products')->modelKeys(),
            $expanded->viewData('products')->take(12)->modelKeys(),
        );
    }

    public function test_category_selection_includes_descendants_and_excludes_ineligible_products(): void
    {
        $product = $this->product('Parent product');
        $child = Category::create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $product->category_id]);
        $this->another($product, 'Child product', ['category_id' => $child->getKey()]);
        $this->another($product, 'Hidden product', ['is_active' => false]);
        $this->another($product, 'Pending product', ['status' => Product::STATUS_PENDING]);
        $this->another($product, 'Sold out', ['stock' => 0]);
        $other = $this->product('Unrelated product');
        $this->another($product, 'Inactive store product', ['vendor_id' => $other->vendor_id]);
        $other->vendor->update(['status' => 'suspended']);

        $response = $this->get(route('home.discovery', ['category' => $product->category_id]))->assertOk()
            ->assertSee('Parent product')->assertSee('Child product')
            ->assertDontSee('Hidden product')->assertDontSee('Pending product')
            ->assertDontSee('Sold out')->assertDontSee('Inactive store product')
            ->assertDontSee('Unrelated product');
        $this->assertCount(2, $response->viewData('products'));
    }

    public function test_fragment_preserves_locale_customer_actions_and_private_cache_headers(): void
    {
        $product = $this->product();
        $customer = User::factory()->create();
        $this->actingAs($customer)->post(route('wishlist.store', $product))->assertRedirect();
        $response = $this->withSession(['locale' => 'vi'])->get(route('home.discovery'))->assertOk()
            ->assertSee('Thêm vào giỏ hàng')
            ->assertSee(route('wishlist.destroy', $product), false)
            ->assertSee('data-add-to-cart', false)
            ->assertSee('product-card-compare-checkbox', false)
            ->assertDontSee('<script', false)->assertDontSee('/build/', false);
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_empty_results_and_invalid_inputs_are_handled(): void
    {
        $this->withSession(['locale' => 'vi'])->get(route('home.discovery'))->assertOk()
            ->assertSee('Danh mục này chưa có sản phẩm còn hàng.')
            ->assertSee('data-has-more="false"', false);
        foreach ([['category' => 999999], ['category' => ['1']], ['expanded' => 2], ['expanded' => ['1']]] as $input) {
            $this->getJson(route('home.discovery', $input))->assertUnprocessable();
        }
    }

    public function test_grid_uses_bootstrap_six_responsive_classes(): void
    {
        $grid = new Grid(columns: ['default' => 2, 'md' => 3, 'lg' => 4, 'xxl' => 6]);
        $this->assertSame('row-cols-2 md:row-cols-3 lg:row-cols-4 2xl:row-cols-6 g-3', $grid->gridClasses());
    }
}
