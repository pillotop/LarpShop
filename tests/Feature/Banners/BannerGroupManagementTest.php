<?php

namespace Tests\Feature\Banners;

use App\Models\BannerGroup;
use App\Models\User;
use App\Services\Banners\BannerCardImageProcessor;
use App\Services\Banners\BannerGroupManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class BannerGroupManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');
        config(['banners.cards_upload.max_dimension' => 320, 'banners.cards_upload.responsive_widths' => [80, 160]]);
        $this->actingAs(User::factory()->create(['role' => 'admin']));
    }

    private function image(int $width = 320, int $height = 480): UploadedFile
    {
        $chunk = fn ($type, $data) => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
        $png = "\x89PNG\r\n\x1a\n";
        $png .= $chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0));
        $png .= $chunk('IDAT', gzcompress(str_repeat("\0".str_repeat("\x44\x88\xaa", $width), $height)));
        $png .= $chunk('IEND', '');

        return UploadedFile::fake()->createWithContent('photo.png', $png);
    }

    private function payload(): array
    {
        $card = [
            'title' => 'Discover home', 'alt_text' => 'A furnished room', 'link_url' => '/products',
            'presentation' => 'bottom_caption', 'text_tone' => 'light', 'focal_x' => 50,
            'focal_y' => 25, 'is_active' => true, 'sort_order' => 0,
            'translations' => ['vi' => ['title' => 'Khám phá không gian sống']],
        ];

        return [
            'name' => 'Home picks', 'slug' => 'home-picks', 'layout' => 'duo', 'is_active' => true,
            'placements' => ['homepage_main'], 'placement_orders' => ['homepage_main' => 4],
            'cards' => [array_merge($card, ['image' => $this->image()]), array_merge($card, ['image' => $this->image(), 'sort_order' => 1])],
        ];
    }

    private function createGroup(): BannerGroup
    {
        $this->post(route('admin.banner-groups.store'), $this->payload())->assertSessionHasNoErrors()->assertRedirect();

        return BannerGroup::query()->where('slug', 'home-picks')->sole()->load('cards');
    }

    private function updatePayload(BannerGroup $group): array
    {
        $data = $this->payload();
        $data['cards'] = $group->cards->map(fn ($card) => [
            'id' => $card->getKey(), 'title' => $card->title, 'alt_text' => $card->alt_text,
            'link_url' => $card->link_url, 'presentation' => $card->presentation, 'text_tone' => $card->text_tone,
            'focal_x' => $card->focal_x, 'focal_y' => $card->focal_y,
            'is_active' => $card->is_active, 'sort_order' => $card->sort_order,
        ])->all();

        return $data;
    }

    public function test_admin_can_create_and_edit_with_real_responsive_image_processing(): void
    {
        $this->get(route('admin.banner-groups.create'))->assertOk()->assertSee('data-banner-editor', false);
        $group = $this->createGroup();
        $this->assertCount(2, $group->cards);
        $this->assertSame(4, $group->placements()->sole()->sort_order);
        $card = $group->cards->first();
        $this->assertSame('Khám phá không gian sống', $card->localizedText('title', 'vi'));
        $this->assertSame(213, $card->image_width);
        $this->assertSame(320, $card->image_height);
        $this->assertCount(2, $card->image_variants);
        foreach ($group->cards as $image) {
            Storage::disk('public')->assertExists([$image->image_path, ...array_column($image->image_variants, 'path')]);
            $size = getimagesize(Storage::disk('public')->path($image->image_path));
            $this->assertSame('image/webp', $size['mime']);
        }
        $this->get(route('admin.banner-groups.edit', $group))->assertOk()->assertSee('Home picks');
        $this->get(route('admin.banner-groups.index', ['q' => 'Home']))->assertOk()->assertSee('Home picks');
    }

    public function test_update_reorders_cards_without_replacing_images_and_removes_unselected_placements(): void
    {
        $group = $this->createGroup();
        $paths = $group->cards->pluck('image_path', 'banner_card_id')->all();
        $data = $this->updatePayload($group);
        $data['cards'][0]['sort_order'] = 1;
        $data['cards'][1]['sort_order'] = 0;
        $data['placements'] = [];
        $this->put(route('admin.banner-groups.update', $group), $data)->assertSessionHasNoErrors();
        $group->refresh();
        $this->assertSame(array_reverse(array_keys($paths)), $group->cards->modelKeys());
        $this->assertSame($paths, $group->cards->pluck('image_path', 'banner_card_id')->sortKeys()->all());
        $this->assertCount(0, $group->placements);
        $this->assertCount(6, Storage::disk('public')->allFiles());
    }

    public function test_replacement_removal_and_group_deletion_clean_up_owned_images_after_save(): void
    {
        $group = $this->createGroup();
        $old = $group->cards->first()->image_path;
        $removed = $group->cards->last()->image_path;
        $data = $this->updatePayload($group);
        $data['is_active'] = false;
        $data['cards'] = [array_merge($data['cards'][0], ['image' => $this->image()])];
        $this->put(route('admin.banner-groups.update', $group), $data)->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing([$old, $removed]);
        $this->assertCount(3, Storage::disk('public')->allFiles());
        $this->delete(route('admin.banner-groups.destroy', $group))->assertRedirect(route('admin.banner-groups.index'));
        $this->assertSame([], Storage::disk('public')->allFiles());
        $this->assertDatabaseCount('banner_cards', 0);
    }

    public function test_unauthorized_users_cannot_manage_groups(): void
    {
        $group = $this->createGroup();
        $this->actingAs(User::factory()->create(['role' => 'customer']));
        $this->get(route('admin.banner-groups.edit', $group))->assertForbidden();
        $this->putJson(route('admin.banner-groups.update', $group), [])->assertForbidden();
        $this->deleteJson(route('admin.banner-groups.destroy', $group))->assertForbidden();
        $this->get(route('admin.banner-groups.index'))->assertForbidden();
        $this->get(route('admin.banner-groups.create'))->assertForbidden();
        $this->postJson(route('admin.banner-groups.store'), $this->payload())->assertForbidden();
        $this->assertDatabaseCount('banner_groups', 1);
    }

    public function test_foreign_card_ids_and_injected_image_metadata_are_rejected(): void
    {
        $group = $this->createGroup();
        $other = BannerGroup::create(['slug' => 'other', 'name' => 'Other', 'layout' => 'duo']);
        $data = $this->updatePayload($group);
        $data['slug'] = 'other';
        $this->putJson(route('admin.banner-groups.update', $other), $data)->assertUnprocessable()->assertJsonValidationErrors('cards.0.id');
        $data = $this->updatePayload($group);
        $data['cards'][0]['image_path'] = 'injected.webp';
        $data['cards'][0]['image_disk'] = 'public_assets';
        $this->putJson(route('admin.banner-groups.update', $group), $data)->assertUnprocessable()->assertJsonValidationErrors('cards.0');
        $this->assertSame(2, $group->cards()->count());
    }

    public function test_invalid_content_schedule_duplicate_slug_and_incomplete_publication_are_rejected(): void
    {
        $group = $this->createGroup();
        $this->postJson(route('admin.banner-groups.store'), $this->payload())->assertUnprocessable()->assertJsonValidationErrors('slug');
        $data = $this->updatePayload($group);
        $data['starts_at'] = '2026-11-22T12:00';
        $data['expires_at'] = '2026-11-22T11:59';
        $data['cards'][0]['link_url'] = 'javascript:alert(1)';
        $data['cards'][0]['focal_x'] = 101;
        $data['cards'][0]['is_active'] = false;
        $this->putJson(route('admin.banner-groups.update', $group), $data)->assertUnprocessable()
            ->assertJsonValidationErrors(['expires_at', 'cards.0.link_url', 'cards.0.focal_x', 'cards.0.is_active']);
        $data = $this->updatePayload($group);
        array_pop($data['cards']);
        $this->putJson(route('admin.banner-groups.update', $group), $data)->assertUnprocessable()->assertJsonValidationErrors('cards');
    }

    public function test_empty_drafts_are_allowed_and_new_cards_require_valid_images(): void
    {
        $data = ['name' => 'Draft', 'slug' => 'draft', 'layout' => 'duo'];
        $this->post(route('admin.banner-groups.store'), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('banner_groups', ['slug' => 'draft', 'is_active' => false]);
        $data = $this->payload();
        unset($data['cards'][0]['image']);
        $this->postJson(route('admin.banner-groups.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('cards.0.image');
        $data = $this->payload();
        $data['cards'][0]['image'] = $this->image(20, 20);
        $this->postJson(route('admin.banner-groups.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('cards.0.image');
        $data['cards'][0]['image'] = UploadedFile::fake()->createWithContent('fake.png', 'not an image');
        $this->postJson(route('admin.banner-groups.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('cards.0.image');
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_editor_recovers_from_malformed_old_input_without_rendering_arrays(): void
    {
        $this->withSession(['_old_input' => [
            'name' => ['unexpected'], 'slug' => ['unexpected'], 'starts_at' => ['bad'],
            'placement_orders' => ['homepage_main' => ['bad']],
            'cards' => [['id' => ['bad'], 'title' => ['bad'], 'translations' => ['vi' => ['title' => ['bad']]]]],
        ]])->get(route('admin.banner-groups.create'))->assertOk();
    }

    public function test_pixel_budget_is_checked_before_decoding_the_image(): void
    {
        $file = $this->image();
        $contents = file_get_contents($file->getPathname());
        $header = pack('NNCCCCC', 4000, 4000, 8, 2, 0, 0, 0);
        $contents = substr_replace($contents, $header.pack('N', crc32('IHDR'.$header)), 16, 17);
        $data = $this->payload();
        $data['cards'][0]['image'] = UploadedFile::fake()->createWithContent('oversized.png', $contents);
        $this->postJson(route('admin.banner-groups.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('cards.0.image');
        $this->assertDatabaseCount('banner_groups', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_failure_after_an_upload_rolls_back_records_and_removes_new_files(): void
    {
        $real = new BannerCardImageProcessor;
        $calls = 0;
        $mock = $this->mock(BannerCardImageProcessor::class);
        $mock->shouldReceive('store')->twice()->andReturnUsing(function ($file, $field) use ($real, &$calls) {
            if (++$calls === 2) {
                throw new RuntimeException('Simulated storage failure.');
            }

            return $real->store($file, $field);
        });
        try {
            app(BannerGroupManagementService::class)->save($this->payload());
            $this->fail('Expected storage failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated storage failure.', $exception->getMessage());
        }
        $this->assertDatabaseCount('banner_groups', 0);
        $this->assertDatabaseCount('banner_cards', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
