<?php

namespace Tests\Feature\Promotions;

use App\Models\ProductCollection;
use App\Models\PromoSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPromoSectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function data(ProductCollection $collection, array $overrides = []): array
    {
        return array_replace([
            'product_collection_id' => $collection->getKey(),
            'layout' => 'grid', 'placement' => 'homepage_main',
        ], $overrides);
    }

    public function test_all_management_actions_require_admin_access(): void
    {
        $section = PromoSection::factory()->create();
        $this->get(route('admin.sections.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create());
        foreach ([
            ['GET', 'index', []], ['GET', 'create', []], ['GET', 'edit', [$section]],
            ['POST', 'store', []], ['PUT', 'update', [$section]],
            ['PATCH', 'toggle', [$section]], ['PATCH', 'move', [$section]], ['DELETE', 'destroy', [$section]],
        ] as [$method, $action, $parameters]) {
            $this->json($method, route('admin.sections.'.$action, $parameters))->assertForbidden();
        }
        $this->assertDatabaseCount('promo_sections', 1);
    }

    public function test_editor_supports_empty_recent_and_older_collections_and_escaped_content(): void
    {
        $this->actingAs($this->admin())->get(route('admin.sections.create'))->assertOk()
            ->assertSee(route('admin.collections.create'), false);
        $older = ProductCollection::factory()->create(['name' => '<script>unsafe</script>']);
        ProductCollection::factory()->count(51)->create();
        $this->get(route('admin.sections.create', ['collection' => $older->getKey()]))->assertOk()
            ->assertSee('&lt;script&gt;unsafe&lt;/script&gt;', false)
            ->assertDontSee('<script>unsafe</script>', false);
        $section = $older->sections()->create(['layout' => 'grid', 'placement' => 'homepage_main']);
        $this->get(route('admin.sections.edit', $section))->assertOk()->assertDontSee('data-bs-theme="light"', false);
        $this->get(route('admin.sections.index'))->assertOk()->assertSee(route('admin.sections.edit', $section), false);
    }

    public function test_create_update_and_toggle_do_not_accept_storage_or_position_fields(): void
    {
        $collection = ProductCollection::factory()->create();
        $other = ProductCollection::factory()->create();
        $this->actingAs($this->admin())->post(route('admin.sections.store'), $this->data($collection, [
            'heading' => 'Holiday picks', 'introduction' => 'Seasonal products', 'is_active' => 1,
            'starts_at' => '2026-12-01T08:00', 'expires_at' => '2026-12-31T08:00',
            'sort_order' => 999, 'image_path' => 'untrusted.webp', 'image_disk' => 'private',
        ]))->assertSessionHasNoErrors();
        $section = PromoSection::query()->sole();
        $this->assertTrue($section->is_active);
        $this->assertSame('2026-12-01 08:00', $section->starts_at->format('Y-m-d H:i'));
        $this->assertSame(1, $section->sort_order);
        $this->assertNull($section->image_path);
        $this->assertSame('public', $section->image_disk);
        $this->put(route('admin.sections.update', $section), $this->data($other, [
            'layout' => 'row', 'heading' => '', 'introduction' => '',
        ]))->assertRedirect(route('admin.sections.edit', $section))->assertSessionHasNoErrors();
        $section->refresh();
        $this->assertSame($other->getKey(), $section->product_collection_id);
        $this->assertSame('row', $section->layout);
        $this->assertFalse($section->is_active);
        $this->assertNull($section->heading);
        $this->assertNull($section->starts_at);
        $this->patch(route('admin.sections.toggle', $section), ['is_active' => 1])->assertSessionHasNoErrors();
        $this->assertTrue($section->fresh()->is_active);
    }

    public function test_invalid_configuration_and_schedule_are_rejected_without_writes(): void
    {
        $collection = ProductCollection::factory()->create();
        $this->actingAs($this->admin())->postJson(route('admin.sections.store'), $this->data($collection, [
            'layout' => 'arbitrary-template', 'placement' => 'other-page',
            'is_active' => 'yes', 'product_collection_id' => 999999,
            'starts_at' => 'not-a-date', 'expires_at' => 'invalid',
        ]))->assertUnprocessable()->assertJsonValidationErrors([
            'layout', 'placement', 'is_active', 'product_collection_id', 'starts_at', 'expires_at',
        ]);
        $this->postJson(route('admin.sections.store'), $this->data($collection, [
            'starts_at' => '2026-12-01T08:00', 'expires_at' => '2026-12-01T08:00',
        ]))->assertUnprocessable()->assertJsonValidationErrors('expires_at');
        $this->postJson(route('admin.sections.store'), $this->data($collection, ['layout' => 'split']))
            ->assertUnprocessable()->assertJsonValidationErrors(['promo_image', 'alt_text']);
        $this->assertDatabaseCount('promo_sections', 0);
    }

    public function test_invalid_old_collection_does_not_break_validation_redirect(): void
    {
        $this->actingAs($this->admin());
        ProductCollection::factory()->create();
        $this->from(route('admin.sections.create'))->post(route('admin.sections.store'), [
            'product_collection_id' => 999999, 'layout' => 'grid', 'placement' => 'homepage_main',
        ])->assertRedirect(route('admin.sections.create'))->assertSessionHasErrors('product_collection_id');
        $this->get(route('admin.sections.create'))->assertOk();
    }

    public function test_ordering_is_stable_with_ties_and_bounds_and_bad_input_is_rejected(): void
    {
        $collection = ProductCollection::factory()->create();
        $first = PromoSection::factory()->for($collection, 'collection')->create(['sort_order' => 0]);
        $second = PromoSection::factory()->for($collection, 'collection')->create(['sort_order' => 0]);
        $third = PromoSection::factory()->for($collection, 'collection')->create(['sort_order' => 4]);
        $this->actingAs($this->admin())->patch(route('admin.sections.move', $second), ['direction' => 'up'])
            ->assertSessionHasNoErrors();
        $ordered = fn () => PromoSection::query()->orderBy('sort_order')->orderBy('promo_section_id')->pluck('promo_section_id')->all();
        $this->assertSame([$second->getKey(), $first->getKey(), $third->getKey()], $ordered());
        $this->patch(route('admin.sections.move', $second), ['direction' => 'up'])->assertSessionHasNoErrors();
        $this->assertSame([$second->getKey(), $first->getKey(), $third->getKey()], $ordered());
        $this->patchJson(route('admin.sections.move', $second), ['direction' => 'sideways'])
            ->assertUnprocessable()->assertJsonValidationErrors('direction');
        $this->patchJson(route('admin.sections.toggle', $second), ['is_active' => 'yes'])
            ->assertUnprocessable()->assertJsonValidationErrors('is_active');
    }

    public function test_section_limit_allows_updates_and_delete_keeps_the_collection(): void
    {
        config(['promotions.max_managed_sections_per_placement' => 1]);
        $collection = ProductCollection::factory()->create();
        $section = PromoSection::factory()->for($collection, 'collection')->create();
        $this->actingAs($this->admin())->postJson(route('admin.sections.store'), $this->data($collection))
            ->assertUnprocessable()->assertJsonValidationErrors('placement');
        $this->put(route('admin.sections.update', $section), $this->data($collection))->assertSessionHasNoErrors();
        $this->delete(route('admin.sections.destroy', $section))->assertRedirect(route('admin.sections.index'));
        $this->assertModelExists($collection);
        $this->assertModelMissing($section);
    }
}
