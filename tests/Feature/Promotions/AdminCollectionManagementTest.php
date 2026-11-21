<?php

namespace Tests\Feature\Promotions;

use App\Models\ProductCollection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class AdminCollectionManagementTest extends TestCase
{
    use CreatesCartFixtures, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_all_collection_management_routes_require_an_admin(): void
    {
        $collection = ProductCollection::factory()->create();
        $product = $this->product();
        $collection->products()->attach($product);
        $this->get(route('admin.collections.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create());
        foreach ([
            ['GET', 'admin.collections.index', []],
            ['GET', 'admin.collections.create', []],
            ['GET', 'admin.collections.edit', [$collection]],
            ['POST', 'admin.collections.store', []],
            ['PUT', 'admin.collections.update', [$collection]],
            ['DELETE', 'admin.collections.destroy', [$collection]],
            ['POST', 'admin.collections.products.store', [$collection]],
            ['PATCH', 'admin.collections.products.move', [$collection, $product]],
            ['DELETE', 'admin.collections.products.destroy', [$collection, $product]],
        ] as [$method, $route, $parameters]) {
            $this->json($method, route($route, $parameters))->assertForbidden();
        }
        $this->assertDatabaseCount('product_collections', 1);
        $this->assertDatabaseCount('product_collection_items', 1);
    }

    public function test_admin_creates_updates_and_unpublishes_metadata_without_overwriting_membership(): void
    {
        $this->actingAs($this->admin())->get(route('admin.collections.create'))->assertOk()
            ->assertDontSee('data-bs-theme="light"', false);
        $this->post(route('admin.collections.store'), [
            'name' => 'Holiday Picks', 'description' => 'Seasonal favorites', 'is_published' => 1,
            'product_id' => 99999, 'image_path' => 'untrusted.webp',
        ])->assertSessionHasNoErrors();
        $collection = ProductCollection::query()->sole();
        $this->assertSame('holiday-picks', $collection->slug);
        $this->assertTrue($collection->is_published);
        $this->assertDatabaseCount('product_collection_items', 0);
        $product = $this->product();
        $collection->products()->attach($product, ['sort_order' => 7]);

        $this->put(route('admin.collections.update', $collection), [
            'name' => 'Updated Picks', 'slug' => 'holiday-picks',
        ])->assertRedirect(route('admin.collections.edit', $collection))->assertSessionHasNoErrors();
        $this->assertSame('Updated Picks', $collection->fresh()->name);
        $this->assertFalse($collection->fresh()->is_published);
        $this->assertSame(7, $collection->products()->first()->pivot->sort_order);
    }

    public function test_metadata_search_and_membership_inputs_are_validated(): void
    {
        $collection = ProductCollection::factory()->create(['slug' => 'existing']);
        $this->actingAs($this->admin());
        $this->postJson(route('admin.collections.store'), [
            'name' => '', 'slug' => 'Bad/Slug', 'is_published' => 'yes',
        ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'slug', 'is_published']);
        $this->postJson(route('admin.collections.store'), ['name' => 'Duplicate', 'slug' => 'existing'])
            ->assertUnprocessable()->assertJsonValidationErrors('slug');
        $this->getJson(route('admin.collections.index', ['q' => ['bad']]))
            ->assertUnprocessable()->assertJsonValidationErrors('q');
        $this->getJson(route('admin.collections.edit', [$collection, 'page' => -1]))
            ->assertUnprocessable()->assertJsonValidationErrors('page');
        $this->postJson(route('admin.collections.products.store', $collection), ['product_id' => 999999])
            ->assertUnprocessable()->assertJsonValidationErrors('product_id');
        $this->assertDatabaseCount('product_collections', 1);
    }

    public function test_admin_index_and_product_picker_search_are_bounded_and_exclude_existing_or_ineligible_products(): void
    {
        $collection = ProductCollection::factory()->create(['name' => 'Seasonal collection']);
        $member = $this->product('Selected item');
        $candidate = $this->product('Searchable item', stock: 0);
        $hidden = $this->product('Hidden item');
        $hidden->update(['is_active' => false]);
        $suspended = $this->product('Suspended item');
        $suspended->vendor->update(['status' => 'suspended']);
        $collection->products()->attach($member);
        ProductCollection::factory()->create(['name' => 'Unrelated collection']);

        $this->actingAs($this->admin())->get(route('admin.collections.index', ['q' => 'Seasonal']))
            ->assertOk()->assertSee('Seasonal collection')->assertDontSee('Unrelated collection');
        $response = $this->get(route('admin.collections.edit', $collection))->assertOk()
            ->assertSee('Selected item')->assertSee('Price (USD)')->assertSee('Promo section preview');
        $this->assertSame([$candidate->getKey()], $response->viewData('candidates')->getCollection()->modelKeys());
        $this->get(route('admin.collections.edit', [$collection, 'q' => 'no-match']))
            ->assertOk()->assertSee('No matching eligible products');
    }

    public function test_adding_products_is_idempotent_and_respects_the_configured_limit(): void
    {
        config(['promotions.max_collection_products' => 2]);
        $collection = ProductCollection::factory()->create();
        $first = $this->product('First');
        $second = $this->product('Second');
        $third = $this->product('Third');
        $url = route('admin.collections.products.store', $collection);
        $this->actingAs($this->admin());
        foreach ([$first, $second, $first] as $product) {
            $this->post($url, ['product_id' => $product->getKey()])->assertRedirect()->assertSessionHasNoErrors();
        }
        $this->postJson($url, ['product_id' => $third->getKey()])
            ->assertUnprocessable()->assertJsonValidationErrors('product_id');
        $this->assertSame([$first->getKey(), $second->getKey()],
            $collection->products()->orderByPivot('sort_order')->get()->modelKeys());
    }

    public function test_ineligible_products_cannot_be_added_even_if_the_client_knows_their_ids(): void
    {
        $collection = ProductCollection::factory()->create();
        $pending = $this->product('Pending');
        $pending->update(['status' => 'pending']);
        $disabledStore = $this->product('Disabled store');
        $disabledStore->vendor->update(['status' => 'suspended']);
        $this->actingAs($this->admin());
        foreach ([$pending, $disabledStore] as $product) {
            $this->postJson(route('admin.collections.products.store', $collection), ['product_id' => $product->getKey()])
                ->assertUnprocessable()->assertJsonValidationErrors('product_id');
        }
        $this->assertDatabaseCount('product_collection_items', 0);
    }

    public function test_reordering_normalizes_ties_and_boundary_moves_preserve_the_order(): void
    {
        $collection = ProductCollection::factory()->create();
        $first = $this->product('First');
        $second = $this->product('Second');
        $third = $this->product('Third');
        $collection->products()->attach([$first->getKey(), $second->getKey(), $third->getKey()]);
        $this->actingAs($this->admin());
        $this->patch(route('admin.collections.products.move', [$collection, $third]), ['direction' => 'up'])
            ->assertRedirect();
        $expected = [$first->getKey(), $third->getKey(), $second->getKey()];
        $this->assertSame($expected, $collection->products()->orderByPivot('sort_order')->get()->modelKeys());
        $this->patch(route('admin.collections.products.move', [$collection, $first]), ['direction' => 'up'])
            ->assertRedirect();
        $this->patch(route('admin.collections.products.move', [$collection, $second]), ['direction' => 'down'])
            ->assertRedirect();
        $this->assertSame($expected, $collection->products()->orderByPivot('sort_order')->get()->modelKeys());
        $this->patchJson(route('admin.collections.products.move', [$collection, $first]), ['direction' => 'sideways'])
            ->assertUnprocessable()->assertJsonValidationErrors('direction');
    }

    public function test_member_actions_are_scoped_to_the_collection_and_removal_keeps_the_product(): void
    {
        $firstCollection = ProductCollection::factory()->create();
        $secondCollection = ProductCollection::factory()->create();
        $product = $this->product();
        $secondCollection->products()->attach($product);
        $this->actingAs($this->admin());
        $this->patchJson(route('admin.collections.products.move', [$firstCollection, $product]), ['direction' => 'up'])
            ->assertNotFound();
        $this->deleteJson(route('admin.collections.products.destroy', [$firstCollection, $product]))->assertNotFound();
        $this->assertDatabaseCount('product_collection_items', 1);
        $this->delete(route('admin.collections.products.destroy', [$secondCollection, $product]))->assertRedirect();
        $this->assertDatabaseCount('product_collection_items', 0);
        $this->assertModelExists($product);
    }

    public function test_deletion_is_blocked_when_used_by_a_section_and_otherwise_keeps_products(): void
    {
        $collection = ProductCollection::factory()->create();
        $product = $this->product();
        $collection->products()->attach($product);
        $section = $collection->sections()->create([]);
        $this->actingAs($this->admin())->deleteJson(route('admin.collections.destroy', $collection))
            ->assertUnprocessable()->assertJsonValidationErrors('collection');
        $this->assertModelExists($collection);
        $section->delete();
        $this->delete(route('admin.collections.destroy', $collection))->assertRedirect(route('admin.collections.index'));
        $this->assertModelMissing($collection);
        $this->assertModelExists($product);
        $this->assertDatabaseCount('product_collection_items', 0);
    }

    public function test_admin_views_escape_content_and_localize_the_interface(): void
    {
        $collection = ProductCollection::factory()->create(['name' => '<script>alert(1)</script>']);
        $this->actingAs($this->admin())->withSession(['locale' => 'vi'])
            ->get(route('admin.collections.edit', $collection))->assertOk()
            ->assertSee('Chỉnh sửa bộ sưu tập')->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }
}
