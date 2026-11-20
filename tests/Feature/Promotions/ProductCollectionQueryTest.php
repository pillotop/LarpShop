<?php

namespace Tests\Feature\Promotions;

use App\Models\ProductCollection;
use App\Models\ProductReview;
use App\Models\User;
use App\Queries\Products\ProductCatalogQuery;
use App\Queries\Promotions\ProductCollectionQuery;
use Database\Seeders\PromoSectionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class ProductCollectionQueryTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    public function test_only_published_collections_are_publicly_resolved(): void
    {
        $published = ProductCollection::factory()->published()->create();
        $draft = ProductCollection::factory()->create();
        $query = app(ProductCollectionQuery::class);
        $this->assertTrue($published->is($query->publishedBySlug($published->slug)));

        $this->expectException(ModelNotFoundException::class);
        $query->publishedBySlug($draft->slug);
    }

    public function test_membership_order_is_stable_and_card_data_is_eager_loaded(): void
    {
        $collection = ProductCollection::factory()->published()->create();
        $first = $this->product('First');
        $second = $this->product('Second');
        $third = $this->product('Third');
        $this->product('Not in collection');
        $collection->products()->attach([
            $first->getKey() => ['sort_order' => 10],
            $second->getKey() => ['sort_order' => 0],
            $third->getKey() => ['sort_order' => 10],
        ]);
        ProductReview::forceCreate([
            'product_id' => $second->getKey(), 'user_id' => User::factory()->create()->getKey(),
            'rating' => 4, 'comment' => 'A useful product.',
        ]);

        $products = app(ProductCatalogQuery::class)->collectionProducts($collection);
        $this->assertSame([$second->getKey(), $first->getKey(), $third->getKey()], $products->modelKeys());
        $this->assertSame(1, $products->first()->total_reviews);
        $this->assertEquals(4, $products->first()->average_rating);
        foreach ($products as $product) {
            foreach (['category', 'images', 'vendor'] as $relation) {
                $this->assertTrue($product->relationLoaded($relation));
            }
            $this->assertFalse($product->relationLoaded('reviews'));
        }
    }

    public function test_collection_visibility_excludes_moderated_products_and_inactive_vendors(): void
    {
        $collection = ProductCollection::factory()->published()->create();
        $visible = $this->product('Visible');
        $approvedVendor = $this->product('Approved vendor');
        $approvedVendor->vendor->update(['status' => 'approved']);
        $pending = $this->product('Pending');
        $pending->update(['status' => 'pending']);
        $rejected = $this->product('Rejected');
        $rejected->update(['status' => 'rejected']);
        $hidden = $this->product('Hidden');
        $hidden->update(['is_active' => false]);
        $suspended = $this->product('Suspended vendor');
        $suspended->vendor->update(['status' => 'suspended']);
        $soldOut = $this->product('Sold out', stock: 0);
        $collection->products()->attach([
            $visible->getKey(), $approvedVendor->getKey(), $pending->getKey(),
            $rejected->getKey(), $hidden->getKey(), $suspended->getKey(), $soldOut->getKey(),
        ]);

        $catalog = app(ProductCatalogQuery::class);
        $this->assertSame([$visible->getKey(), $approvedVendor->getKey()],
            $catalog->collectionProducts($collection)->modelKeys());
        $page = $catalog->paginateCollectionProducts($collection);
        $this->assertSame([$visible->getKey(), $approvedVendor->getKey(), $soldOut->getKey()],
            $page->getCollection()->modelKeys());
    }

    public function test_collection_pages_are_paginated_and_section_reads_have_a_hard_limit(): void
    {
        config(['promotions.collection_page_size' => 2]);
        $collection = ProductCollection::factory()->published()->create();
        $ids = [];
        for ($index = 0; $index < 14; $index++) {
            $ids[] = $this->product('Item '.$index)->getKey();
        }
        $collection->products()->attach($ids);
        $catalog = app(ProductCatalogQuery::class);
        $page = $catalog->paginateCollectionProducts($collection);
        $this->assertSame(14, $page->total());
        $this->assertSame(array_slice($ids, 0, 2), $page->getCollection()->modelKeys());
        $this->assertSame(array_slice($ids, 0, 12), $catalog->collectionProducts($collection, 1000)->modelKeys());
    }

    public function test_collection_slugs_are_unique(): void
    {
        ProductCollection::factory()->create(['slug' => 'holiday-picks']);

        $this->expectException(QueryException::class);
        ProductCollection::factory()->create(['slug' => 'holiday-picks']);
    }

    public function test_deleting_an_unreferenced_collection_removes_its_memberships_but_keeps_products(): void
    {
        $collection = ProductCollection::factory()->create();
        $product = $this->product();
        $collection->products()->attach($product);
        $collection->delete();

        $this->assertDatabaseCount('product_collection_items', 0);
        $this->assertModelExists($product);
    }

    public function test_duplicate_membership_is_rejected_by_the_database(): void
    {
        $collection = ProductCollection::factory()->create();
        $product = $this->product();
        $collection->products()->attach($product);

        $this->expectException(QueryException::class);
        $collection->products()->attach($product);
    }

    public function test_product_deletion_removes_membership_without_deleting_the_collection(): void
    {
        $collection = ProductCollection::factory()->create();
        $product = $this->product();
        $collection->products()->attach($product);
        $product->delete();

        $this->assertDatabaseCount('product_collection_items', 0);
        $this->assertModelExists($collection);
    }

    public function test_collection_referenced_by_a_section_cannot_be_deleted(): void
    {
        $collection = ProductCollection::factory()->create();
        $collection->sections()->create([]);

        $this->expectException(QueryException::class);
        $collection->delete();
    }

    public function test_demo_seeder_is_safe_on_empty_catalog_and_preserves_existing_admin_edits(): void
    {
        $this->seed(PromoSectionSeeder::class);
        $this->assertDatabaseCount('product_collections', 0);
        $product = $this->product();
        $this->seed(PromoSectionSeeder::class);
        $collection = ProductCollection::query()->sole();
        $this->assertSame([$product->getKey()], $collection->products->modelKeys());
        $collection->update(['name' => 'Admin collection', 'is_published' => false]);
        $collection->products()->detach();
        $collection->sections()->first()->update(['is_active' => false]);

        $this->seed(PromoSectionSeeder::class);
        $this->assertDatabaseCount('product_collections', 1);
        $this->assertDatabaseCount('promo_sections', 1);
        $this->assertSame('Admin collection', $collection->fresh()->name);
        $this->assertFalse($collection->fresh()->is_published);
        $this->assertFalse($collection->sections()->first()->is_active);
        $this->assertDatabaseCount('product_collection_items', 0);
    }
}
