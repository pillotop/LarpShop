<?php

namespace Tests\Feature\Vendor;

use App\Models\Banner;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class VendorBannerManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Vendor $vendor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
        Storage::fake('public');
        config(['banners.zones.vendor_header.output_width' => 8, 'banners.zones.vendor_header.responsive_widths' => [4]]);
        $this->owner = User::factory()->create();
        $this->vendor = $this->owner->vendors()->create(['store_name' => 'Banner Store', 'status' => 'active']);
    }

    private function image(): UploadedFile
    {
        return UploadedFile::fake()->image('crop.png', 8, 2);
    }

    private function fields(): array
    {
        return [
            'title' => 'Collection', 'alt_text' => 'Armor collection', 'zone' => 'vendor_header',
            'sort_order' => 5, 'is_active' => '1',
            'starts_at' => '2026-11-20T12:00', 'expires_at' => '2026-11-21T12:00',
            'link_url' => 'https://example.com/collection',
        ];
    }

    private function banner(): Banner
    {
        return $this->vendor->banners()->create(array_merge($this->fields(), [
            'image_disk' => 'public', 'image_path' => 'banners/old.webp', 'image_width' => 8, 'image_height' => 2,
        ]));
    }

    public function test_owner_can_create_banner_with_real_processed_image(): void
    {
        $this->actingAs($this->owner)->post(route('vendor.store.banners.store', $this->vendor), [
            ...$this->fields(), 'banner_image' => $this->image(), 'vendor_id' => 999,
            'image_path' => 'forged.png', 'image_disk' => 's3',
        ])->assertSessionHasNoErrors()->assertRedirect(route('vendors.show', $this->vendor->vendor_id));

        $banner = $this->vendor->banners()->sole();
        $this->assertSame('public', $banner->image_disk);
        $this->assertStringEndsWith('.webp', $banner->image_path);
        Storage::disk('public')->assertExists($banner->image_path);
        $this->assertSame('2026-11-20 12:00', $banner->starts_at->format('Y-m-d H:i'));
    }

    public function test_owner_can_update_metadata_without_replacing_image(): void
    {
        $banner = $this->banner();
        $this->actingAs($this->owner)->patch(route('vendor.store.banners.update', [$this->vendor, $banner]), [
            ...$this->fields(), 'title' => 'Updated', 'starts_at' => '', 'expires_at' => '',
        ])->assertSessionHasNoErrors();
        $banner->refresh();
        $this->assertSame('Updated', $banner->title);
        $this->assertNull($banner->starts_at);
        $this->assertNull($banner->expires_at);
        $this->assertSame('banners/old.webp', $banner->image_path);
    }

    public function test_replacement_deletes_old_file_and_delete_removes_record_and_image(): void
    {
        $banner = $this->banner();
        $banner->update(['image_variants' => [['path' => 'banners/old-4.webp', 'width' => 4, 'height' => 1]]]);
        Storage::disk('public')->put('banners/old-4.webp', 'old variant');
        Storage::disk('public')->put($banner->image_path, 'old');
        $this->actingAs($this->owner)->patch(route('vendor.store.banners.update', [$this->vendor, $banner]), [
            ...$this->fields(), 'banner_image' => $this->image(),
        ])->assertSessionHasNoErrors();
        Storage::disk('public')->assertMissing('banners/old.webp');
        Storage::disk('public')->assertMissing('banners/old-4.webp');
        $path = $banner->fresh()->image_path;
        Storage::disk('public')->assertExists($path);

        $this->delete(route('vendor.store.banners.destroy', [$this->vendor, $banner]))->assertRedirect();
        $this->assertModelMissing($banner);
        Storage::disk('public')->assertMissing($path);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_active_toggle_is_explicit_and_idempotent(): void
    {
        $banner = $this->banner();
        foreach ([0, 0, 1] as $active) {
            $this->actingAs($this->owner)->patch(route('vendor.store.banners.toggle', [$this->vendor, $banner]), [
                'is_active' => $active,
            ])->assertRedirect();
            $this->assertSame((bool) $active, $banner->fresh()->is_active);
        }
    }

    public function test_guest_and_other_owner_cannot_manage_banner(): void
    {
        $banner = $this->banner();
        $this->post(route('vendor.store.banners.store', $this->vendor), [])->assertRedirect(route('login'));
        $other = User::factory()->create();
        $other->vendors()->create(['store_name' => 'Other', 'status' => 'active']);
        $this->actingAs($other)->post(route('vendor.store.banners.store', $this->vendor), [])->assertForbidden();
        $this->patch(route('vendor.store.banners.update', [$this->vendor, $banner]), [])->assertForbidden();
        $this->patch(route('vendor.store.banners.toggle', [$this->vendor, $banner]), ['is_active' => 0])->assertForbidden();
        $this->delete(route('vendor.store.banners.destroy', [$this->vendor, $banner]))->assertForbidden();
        $this->assertModelExists($banner);
    }

    public function test_banner_binding_cannot_cross_stores_even_for_same_owner(): void
    {
        $banner = $this->banner();
        $second = $this->owner->vendors()->create(['store_name' => 'Second', 'status' => 'active']);
        $this->actingAs($this->owner)->delete(route('vendor.store.banners.destroy', [$second, $banner]))->assertNotFound();
        $this->assertModelExists($banner);
    }

    public function test_invalid_zone_link_schedule_crop_and_priority_are_rejected(): void
    {
        $cases = [
            ['zone', 'homepage_hero'], ['link_url', 'javascript:alert(1)'],
            ['expires_at', '2026-11-20T11:59'], ['sort_order', -1], ['title', ''],
        ];
        foreach ($cases as [$field, $value]) {
            $this->actingAs($this->owner)->post(route('vendor.store.banners.store', $this->vendor), [
                ...$this->fields(), $field => $value, 'banner_image' => $this->image(),
            ])->assertSessionHasErrors($field);
        }
        $this->post(route('vendor.store.banners.store', $this->vendor), [
            ...$this->fields(), 'banner_image' => UploadedFile::fake()->image('wrong.png', 8, 3),
        ])->assertSessionHasErrors('banner_image');
        $this->assertDatabaseCount('banners', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_create_requires_image_and_validation_restores_correct_editor(): void
    {
        $this->actingAs($this->owner)->from(route('vendors.show', $this->vendor->vendor_id))
            ->post(route('vendor.store.banners.store', $this->vendor), $this->fields())
            ->assertSessionHasErrors('banner_image')
            ->assertSessionHas('_old_input.banner_editor', 'new')
            ->assertSessionHas('banner_settings_open', true);
    }

    public function test_inactive_store_cannot_manage_banners_even_if_owner_has_another_active_store(): void
    {
        $banner = $this->banner();
        $this->owner->vendors()->create(['store_name' => 'Active Alternative', 'status' => 'active']);
        $this->vendor->update(['status' => 'suspended']);
        $this->actingAs($this->owner)->patch(route('vendor.store.banners.toggle', [$this->vendor, $banner]), [
            'is_active' => 0,
        ])->assertForbidden();
        $this->assertTrue($banner->fresh()->is_active);
    }

    public function test_failed_database_save_cleans_new_file_and_preserves_old_banner(): void
    {
        $banner = $this->banner();
        Storage::disk('public')->put($banner->image_path, 'old');
        Banner::saving(fn () => throw new RuntimeException('Simulated database failure'));
        $this->withoutExceptionHandling();
        try {
            $this->actingAs($this->owner)->patch(route('vendor.store.banners.update', [$this->vendor, $banner]), [
                ...$this->fields(), 'banner_image' => $this->image(),
            ]);
            $this->fail('Expected failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated database failure', $exception->getMessage());
        } finally {
            Banner::flushEventListeners();
        }
        $this->assertSame(['banners/old.webp'], Storage::disk('public')->allFiles());
        $this->assertSame('banners/old.webp', $banner->fresh()->image_path);
    }

    public function test_management_forms_are_only_rendered_for_owner(): void
    {
        $this->withoutVite();
        $banner = $this->banner();
        $this->get(route('vendors.show', $this->vendor->vendor_id))->assertOk()
            ->assertDontSee(route('vendor.store.banners.store', $this->vendor), false);
        $this->actingAs($this->owner)->get(route('vendors.show', $this->vendor->vendor_id))->assertOk()
            ->assertSee(route('vendor.store.banners.store', $this->vendor), false)
            ->assertSee('Armor collection');
    }
}
