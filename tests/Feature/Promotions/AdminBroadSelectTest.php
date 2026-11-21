<?php

namespace Tests\Feature\Promotions;

use App\Models\ProductCollection;
use App\Models\PromoSection;
use App\Models\User;
use App\Services\Promotions\BroadSelectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesCartFixtures;
use Tests\TestCase;

class AdminBroadSelectTest extends TestCase
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

    private function automatic(array $overrides = []): array
    {
        return array_replace([
            'placement' => 'homepage_broad_select', 'layout' => 'row',
            'source' => 'new_arrivals', 'heading' => 'New arrivals', 'is_active' => true,
        ], $overrides);
    }

    public function test_editor_is_available_without_collections_and_restores_automatic_source(): void
    {
        $this->actingAs($this->admin())->get(route('admin.sections.create'))
            ->assertOk()->assertSee('name="source"', false)->assertSee('homepage_broad_select');
        $this->post(route('admin.sections.store'), $this->automatic())->assertSessionHasNoErrors();
        $section = PromoSection::query()->sole();
        $this->get(route('admin.sections.edit', $section))->assertOk()
            ->assertViewHas('section', fn ($record) => $record->source === 'new_arrivals' && $record->product_collection_id === null)
            ->assertSee('name="source"', false);
        $this->assertDatabaseCount('product_collections', 0);
    }

    public function test_automatic_sources_can_be_saved_and_scheduled_without_collection_membership(): void
    {
        $this->actingAs($this->admin())->post(route('admin.sections.store'), $this->automatic([
            'starts_at' => '2026-12-01T08:00', 'expires_at' => '2026-12-02T08:00',
        ]))->assertSessionHasNoErrors();
        $section = PromoSection::query()->sole();
        $this->assertSame('new_arrivals', $section->source);
        $this->assertNull($section->product_collection_id);
        $this->assertTrue($section->is_active);
        $this->assertSame('2026-12-01 08:00', $section->starts_at->format('Y-m-d H:i'));
        $this->put(route('admin.sections.update', $section), $this->automatic(['source' => 'top_rated']))
            ->assertSessionHasNoErrors();
        $this->assertSame('top_rated', $section->fresh()->source);
        $this->assertDatabaseCount('product_collection_items', 0);
    }

    public function test_switching_between_collection_and_automatic_sources_associates_and_clears_the_foreign_key(): void
    {
        $collection = ProductCollection::factory()->published()->create();
        $this->actingAs($this->admin())->post(route('admin.sections.store'), $this->automatic())
            ->assertSessionHasNoErrors();
        $section = PromoSection::query()->sole();
        $this->put(route('admin.sections.update', $section), $this->automatic([
            'source' => 'collection', 'product_collection_id' => $collection->getKey(),
        ]))->assertSessionHasNoErrors();
        $this->assertTrue($section->fresh()->collection->is($collection));
        $this->put(route('admin.sections.update', $section), $this->automatic(['source' => 'top_rated']))
            ->assertSessionHasNoErrors();
        $this->assertNull($section->fresh()->product_collection_id);
        $this->assertModelExists($collection);
    }

    public function test_incompatible_sources_placements_layouts_and_collection_payloads_are_rejected(): void
    {
        $collection = ProductCollection::factory()->create();
        $cases = [
            [['source' => 'unknown'], 'source'],
            [['source' => ['new_arrivals']], 'source'],
            [['placement' => 'homepage_main'], 'source'],
            [['layout' => 'grid'], 'layout'],
            [['product_collection_id' => $collection->getKey()], 'product_collection_id'],
            [['source' => 'collection'], 'product_collection_id'],
            [['expires_at' => '2026-12-01T08:00', 'starts_at' => '2026-12-02T08:00'], 'expires_at'],
        ];
        $this->actingAs($this->admin());
        foreach ($cases as [$overrides, $field]) {
            $this->postJson(route('admin.sections.store'), $this->automatic($overrides))
                ->assertUnprocessable()->assertJsonValidationErrors($field);
        }
        $this->assertDatabaseCount('promo_sections', 0);
    }

    public function test_existing_move_toggle_and_limit_rules_apply_within_the_broad_placement(): void
    {
        $this->product('Visible arrival');
        $this->actingAs($this->admin());
        $this->post(route('admin.sections.store'), $this->automatic(['heading' => 'First']))->assertSessionHasNoErrors();
        $this->post(route('admin.sections.store'), $this->automatic(['heading' => 'Second']))->assertSessionHasNoErrors();
        [$first, $second] = PromoSection::query()->orderBy('sort_order')->get()->all();
        $this->patch(route('admin.sections.move', $second), ['direction' => 'up'])->assertSessionHasNoErrors();
        $this->assertSame('Second', app(BroadSelectService::class)->forHomepage()->first()->label);
        $this->patch(route('admin.sections.toggle', $second), ['is_active' => 0])->assertSessionHasNoErrors();
        $this->assertSame('First', app(BroadSelectService::class)->forHomepage()->first()->label);
        config(['promotions.max_managed_sections_per_placement' => 2]);
        $this->postJson(route('admin.sections.store'), $this->automatic())
            ->assertUnprocessable()->assertJsonValidationErrors('placement');
        $collection = ProductCollection::factory()->create();
        $other = $collection->sections()->create(['placement' => 'homepage_main']);
        $this->putJson(route('admin.sections.update', $other), $this->automatic())
            ->assertUnprocessable()->assertJsonValidationErrors('placement');
        $this->assertSame('homepage_main', $other->fresh()->placement);
        $this->assertModelExists($first);
    }

    public function test_automatic_preview_works_without_collections_and_does_not_change_saved_configuration(): void
    {
        $product = $this->product('Public new arrival');
        $hidden = $this->product('Hidden arrival');
        $hidden->update(['is_active' => false]);
        $this->actingAs($this->admin())->post(route('admin.sections.store'), $this->automatic([
            'is_active' => false, 'starts_at' => '2027-01-01T00:00',
        ]))->assertSessionHasNoErrors();
        $section = PromoSection::query()->sole();
        $before = $section->getAttributes();
        $this->get(route('admin.promotions.preview', ['section' => $section->getKey()]))
            ->assertOk()->assertSee($product->name)->assertDontSee($hidden->name)
            ->assertSee('data-promo-layout="row"', false)
            ->assertViewHas('productSource', 'new_arrivals')
            ->assertViewHas('selectedCollection', null);
        $this->assertSame($before, $section->fresh()->getAttributes());
        $this->assertCount(0, app(BroadSelectService::class)->forHomepage());
        $this->assertDatabaseCount('product_collections', 0);
        $this->assertDatabaseCount('promo_sections', 1);
    }

    public function test_unsaved_preview_supports_automatic_and_empty_sources_and_rejects_incompatible_inputs(): void
    {
        $this->actingAs($this->admin());
        $this->get(route('admin.promotions.preview', ['source' => 'top_rated']))
            ->assertOk()->assertSee('no eligible products in stock');
        foreach ([
            ['source' => 'unknown'],
            ['source' => 'new_arrivals', 'placement' => 'homepage_main'],
            ['source' => 'new_arrivals', 'layout' => 'split'],
        ] as $input) {
            $this->getJson(route('admin.promotions.preview', $input))->assertUnprocessable();
        }
        $this->assertDatabaseCount('promo_sections', 0);
    }

    public function test_non_admins_cannot_use_automatic_source_management_or_preview(): void
    {
        $this->postJson(route('admin.sections.store'), $this->automatic())->assertUnauthorized();
        $this->actingAs(User::factory()->create());
        $this->postJson(route('admin.sections.store'), $this->automatic())->assertForbidden();
        $this->getJson(route('admin.promotions.preview', ['source' => 'new_arrivals']))->assertForbidden();
        $this->assertDatabaseCount('promo_sections', 0);
    }
}
