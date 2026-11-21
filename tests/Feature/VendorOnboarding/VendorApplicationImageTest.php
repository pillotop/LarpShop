<?php

namespace Tests\Feature\VendorOnboarding;

use App\Models\VendorApplication;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class VendorApplicationImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_photo_upload_replacement_and_removal_are_scoped_to_the_application(): void
    {
        $application = VendorApplication::factory()->create();
        $this->actingAs($application->user);
        $this->savePhoto($application);
        $old = $application->fresh()->brand_path;
        $this->assertStringStartsWith('vendor-applications/'.$application->getKey().'/logos/', $old);
        $this->savePhoto($application);
        Storage::disk('public')->assertMissing($old);
        $new = $application->fresh()->brand_path;
        Storage::disk('public')->assertExists($new);
        $this->patchJson(route('vendor-applications.update', $application), ['remove_brand_image' => true])->assertOk();
        $this->assertNull($application->fresh()->brand_path);
        Storage::disk('public')->assertMissing($new);
    }

    public function test_invalid_uploads_never_create_a_draft_or_write_a_file(): void
    {
        $application = VendorApplication::factory()->make();
        $this->actingAs($application->user);
        foreach ([
            UploadedFile::fake()->create('shell.php', 1, 'application/x-php'),
            UploadedFile::fake()->createWithContent('vector.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
            $this->image()->size(2049),
        ] as $file) {
            $this->postJson(route('vendor-applications.store'), ['brand_image' => $file])
                ->assertUnprocessable()->assertJsonValidationErrors('brand_image');
        }
        $this->assertDatabaseCount('vendor_applications', 0);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    public function test_a_logo_still_used_by_the_rejected_vendor_is_kept_until_resubmission(): void
    {
        $application = VendorApplication::factory()->complete()->create();
        $this->actingAs($application->user);
        $this->savePhoto($application);
        $old = $application->fresh()->brand_path;
        $this->postJson(route('vendor-applications.submit', $application), ['confirmation' => true])->assertOk();
        $application->refresh()->forceFill(['status' => 'rejected'])->save();
        $vendor = $application->vendor;
        $vendor->update(['status' => 'rejected']);
        $this->savePhoto($application);
        Storage::disk('public')->assertExists($old);
        $this->assertSame($old, $vendor->fresh()->brand_path);
        $this->postJson(route('vendor-applications.submit', $application), ['confirmation' => true])->assertOk();
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($vendor->fresh()->brand_path);
    }

    public function test_failed_database_save_removes_the_new_file_and_preserves_the_original(): void
    {
        $application = VendorApplication::factory()->create();
        $this->actingAs($application->user);
        $this->savePhoto($application);
        $path = $application->fresh()->brand_path;
        VendorApplication::updating(fn () => throw new \RuntimeException('Simulated save failure'));
        $this->postJson(route('vendor-applications.update', $application), [
            '_method' => 'PATCH', 'brand_image' => $this->image(),
        ])->assertServerError();
        $this->assertSame($path, $application->fresh()->brand_path);
        Storage::disk('public')->assertExists($path);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_storage_write_failure_rolls_back_the_new_draft(): void
    {
        $application = VendorApplication::factory()->make();
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('public')->andReturn($disk);
        $this->actingAs($application->user)->postJson(route('vendor-applications.store'), ['brand_image' => $this->image()])
            ->assertUnprocessable()->assertJsonValidationErrors('brand_image');
        $this->assertDatabaseCount('vendor_applications', 0);
    }

    private function savePhoto(VendorApplication $application): void
    {
        $this->postJson(route('vendor-applications.update', $application), [
            '_method' => 'PATCH', 'brand_image' => $this->image(),
        ])->assertOk();
    }

    private function image(): File
    {
        return UploadedFile::fake()->createWithContent('logo.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aN1sAAAAASUVORK5CYII='
        ));
    }
}
