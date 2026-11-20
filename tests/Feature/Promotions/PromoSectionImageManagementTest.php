<?php

namespace Tests\Feature\Promotions;

use App\Models\ProductCollection;
use App\Models\PromoSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class PromoSectionImageManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');
        config(['promotions.image_profiles.portrait.output_width' => 8,
            'promotions.image_profiles.portrait.responsive_widths' => [4]]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    private function image(int $height = 10): UploadedFile
    {
        $chunk = fn ($type, $data) => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
        $png = "\x89PNG\r\n\x1a\n";
        $png .= $chunk('IHDR', pack('NNCCCCC', 8, $height, 8, 2, 0, 0, 0));
        $png .= $chunk('IDAT', gzcompress(str_repeat("\0".str_repeat("\x44\x88\xaa", 8), $height)));
        $png .= $chunk('IEND', '');

        return UploadedFile::fake()->createWithContent('portrait.png', $png);
    }

    private function data(ProductCollection $collection, array $overrides = []): array
    {
        return array_replace([
            'product_collection_id' => $collection->getKey(), 'layout' => 'split',
            'placement' => 'homepage_main', 'alt_text' => 'Seasonal equipment',
        ], $overrides);
    }

    private function storedSection(): PromoSection
    {
        $collection = ProductCollection::factory()->create();
        $section = $collection->sections()->create([
            'layout' => 'split', 'alt_text' => 'Existing artwork',
            'image_path' => 'promotions/old.webp', 'image_width' => 8, 'image_height' => 10,
            'image_variants' => [['path' => 'promotions/old-4.webp', 'width' => 4, 'height' => 5]],
        ]);
        Storage::disk('public')->put('promotions/old.webp', 'old');
        Storage::disk('public')->put('promotions/old-4.webp', 'old variant');

        return $section;
    }

    public function test_wrong_crop_and_unsupported_layout_upload_do_not_write_files(): void
    {
        $collection = ProductCollection::factory()->create();
        $this->postJson(route('admin.sections.store'), $this->data($collection, ['promo_image' => $this->image(2)]))
            ->assertUnprocessable()->assertJsonValidationErrors('promo_image');
        $this->postJson(route('admin.sections.store'), $this->data($collection, ['layout' => 'grid', 'promo_image' => $this->image()]))
            ->assertUnprocessable()->assertJsonValidationErrors('promo_image');
        $this->assertDatabaseCount('promo_sections', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_upload_stores_responsive_images_and_replacement_and_delete_clean_up_files(): void
    {
        $collection = ProductCollection::factory()->create();
        $this->post(route('admin.sections.store'), $this->data($collection, ['promo_image' => $this->image()]))
            ->assertSessionHasNoErrors();
        $section = PromoSection::query()->sole();
        $old = [$section->image_path, ...array_column($section->image_variants, 'path')];
        Storage::disk('public')->assertExists($old);
        $this->get(route('admin.sections.edit', $section))->assertOk()->assertSee('Seasonal equipment');
        $this->put(route('admin.sections.update', $section), $this->data($collection, [
            'promo_image' => $this->image(), 'remove_image' => 1,
        ]))->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($old);
        $section->refresh();
        $current = [$section->image_path, ...array_column($section->image_variants, 'path')];
        Storage::disk('public')->assertExists($current);
        $this->delete(route('admin.sections.destroy', $section))->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing($current);
        $this->assertModelExists($collection);
    }

    public function test_image_is_retained_without_replacement_and_can_be_removed_after_changing_layout(): void
    {
        $section = $this->storedSection();
        $this->put(route('admin.sections.update', $section), $this->data($section->collection))
            ->assertSessionHasNoErrors();
        Storage::disk('public')->assertExists('promotions/old.webp');
        $this->assertSame('promotions/old.webp', $section->fresh()->image_path);
        $this->putJson(route('admin.sections.update', $section), $this->data($section->collection, ['remove_image' => 1]))
            ->assertUnprocessable()->assertJsonValidationErrors('promo_image');
        Storage::disk('public')->assertExists('promotions/old.webp');
        $this->put(route('admin.sections.update', $section), $this->data($section->collection, ['layout' => 'grid', 'remove_image' => 1]))
            ->assertSessionHasNoErrors();
        $this->assertNull($section->fresh()->image_path);
        Storage::disk('public')->assertMissing(['promotions/old.webp', 'promotions/old-4.webp']);
    }

    public function test_database_failure_removes_new_files_and_preserves_the_previous_image(): void
    {
        $section = $this->storedSection();
        $data = $this->data($section->collection, ['promo_image' => $this->image()]);
        PromoSection::saving(fn () => throw new RuntimeException('Simulated database failure'));
        $this->withoutExceptionHandling();
        try {
            $this->put(route('admin.sections.update', $section), $data);
            $this->fail('Expected database failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated database failure', $exception->getMessage());
        } finally {
            PromoSection::flushEventListeners();
        }
        $this->assertSame('promotions/old.webp', $section->fresh()->image_path);
        $this->assertEqualsCanonicalizing(['promotions/old.webp', 'promotions/old-4.webp'], Storage::disk('public')->allFiles());
    }

    public function test_enabling_incomplete_image_layout_is_rejected_but_disabling_is_allowed(): void
    {
        $section = PromoSection::factory()->create(['layout' => 'split', 'is_active' => true]);
        $this->patchJson(route('admin.sections.toggle', $section), ['is_active' => 1])
            ->assertUnprocessable()->assertJsonValidationErrors('promo_image');
        $this->patch(route('admin.sections.toggle', $section), ['is_active' => 0])->assertSessionHasNoErrors();
        $this->assertFalse($section->fresh()->is_active);
    }
}
