<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Categories\CategoryPreviewService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CategoryPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function category(string $slug, ?Category $parent = null): Category
    {
        return Category::create(['name' => $slug, 'slug' => $slug, 'parent_id' => $parent?->getKey(), 'is_active' => true]);
    }

    private function product(Category $category, array $attributes = [], array $paths = ['products/sample.jpg']): Product
    {
        $vendor = Vendor::create(['user_id' => User::factory()->create()->getKey(), 'store_name' => uniqid('Store '), 'status' => 'approved']);
        $product = Product::create(array_merge([
            'category_id' => $category->getKey(), 'vendor_id' => $vendor->getKey(),
            'name' => 'Sample product', 'slug' => uniqid('sample-'), 'price' => 10, 'stock' => 5,
            'status' => Product::STATUS_APPROVED, 'is_active' => true,
        ], $attributes));
        foreach ($paths as $index => $path) {
            $product->images()->create(['path' => $path, 'sort_order' => $index, 'alt_text' => 'Image '.$index]);
        }

        return $product;
    }

    public function test_leaf_product_images_propagate_to_root_in_one_bounded_query(): void
    {
        $root = $this->category('root');
        $branch = $this->category('branch', $root);
        $leaf = $this->category('leaf', $branch);
        $this->product($leaf, paths: ['one.jpg', 'two.jpg', 'three.jpg', 'four.jpg']);
        $categories = Category::all();
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $previews = app(CategoryPreviewService::class)->forCategories($categories);
            $this->assertCount(1, DB::getQueryLog());
        } finally {
            DB::disableQueryLog();
        }
        $this->assertCount(3, $previews[$leaf->getKey()]);
        $this->assertSame($previews[$leaf->getKey()], $previews[$branch->getKey()]);
        $this->assertSame($previews[$leaf->getKey()], $previews[$root->getKey()]);
        $this->assertSame(asset('storage/one.jpg'), $previews[$root->getKey()][0]['url']);
    }

    public function test_curated_images_override_products_and_are_inherited(): void
    {
        $root = $this->category('root');
        $leaf = $this->category('leaf', $root);
        $this->product($leaf);
        config(['category-previews.images' => ['leaf' => ['categories/leaf.webp', 'https://example.test/leaf.jpg']]]);
        $previews = app(CategoryPreviewService::class)->forCategories(Category::all());
        $this->assertSame(asset('storage/categories/leaf.webp'), $previews[$leaf->getKey()][0]['url']);
        $this->assertSame($previews[$leaf->getKey()], $previews[$root->getKey()]);
        $this->assertSame('https://example.test/leaf.jpg', $previews[$root->getKey()][1]['url']);
        config(['category-previews.images.root' => ['categories/root.webp']]);
        $previews = app(CategoryPreviewService::class)->forCategories(Category::all());
        $this->assertCount(1, $previews[$root->getKey()]);
        $this->assertSame(asset('storage/categories/root.webp'), $previews[$root->getKey()][0]['url']);
    }

    public function test_hidden_unapproved_inactive_category_and_unrelated_images_are_excluded(): void
    {
        $root = $this->category('root');
        $leaf = $this->category('leaf', $root);
        $inactive = $this->category('inactive', $root);
        $inactive->update(['is_active' => false]);
        $other = $this->category('other');
        $this->product($leaf, ['is_active' => false]);
        $this->product($leaf, ['status' => Product::STATUS_PENDING]);
        $this->product($inactive);
        $this->product($other);
        $previews = app(CategoryPreviewService::class)->forCategories(Category::all());
        $this->assertSame([], $previews[$root->getKey()]);
        $this->assertSame([], $previews[$leaf->getKey()]);
        $this->assertArrayNotHasKey($inactive->getKey(), $previews);
        $this->assertCount(1, $previews[$other->getKey()]);
    }

    public function test_inheritance_is_bounded_deduplicated_and_tolerates_cycles(): void
    {
        $root = $this->category('root');
        foreach (['a', 'b', 'c', 'd'] as $slug) {
            $child = $this->category($slug, $root);
            config(['category-previews.images.'.$slug => ['shared.jpg', $slug.'.jpg']]);
        }
        $previews = app(CategoryPreviewService::class)->forCategories(Category::all());
        $this->assertCount(3, $previews[$root->getKey()]);
        $this->assertCount(3, array_unique(array_column($previews[$root->getKey()], 'url')));
        $a = $this->category('cycle-a');
        $b = $this->category('cycle-b', $a);
        $a->update(['parent_id' => $b->getKey()]);
        $previews = app(CategoryPreviewService::class)->forCategories(Category::all());
        $this->assertSame([], $previews[$a->getKey()]);
        $this->assertSame([], $previews[$b->getKey()]);
    }
}
