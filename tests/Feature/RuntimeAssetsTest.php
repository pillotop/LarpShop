<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class RuntimeAssetsTest extends TestCase
{
    private string $temporaryPublic;

    private string $originalPublic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalPublic = public_path();
        $this->temporaryPublic = sys_get_temp_dir().'/larpshop-assets-'.Str::uuid();
        File::ensureDirectoryExists($this->temporaryPublic);
        $this->app->usePublicPath($this->temporaryPublic);
        Storage::fake('public');
        config([
            'filesystems.disks.public.root' => Storage::disk('public')->path(''),
            'filesystems.links' => [public_path('storage') => Storage::disk('public')->path('')],
        ]);
    }

    protected function tearDown(): void
    {
        $link = $this->temporaryPublic.'/storage';
        if (is_link($link)) {
            unlink($link);
        }
        File::deleteDirectory($this->temporaryPublic);
        $this->app->usePublicPath($this->originalPublic);

        parent::tearDown();
    }

    public function test_a_fresh_runtime_publishes_bundled_images_and_links_the_configured_disk(): void
    {
        $this->artisan('assets:prepare')->assertSuccessful();

        $this->assertTrue(is_link(public_path('storage')));
        $this->assertSame(realpath(Storage::disk('public')->path('')), realpath(public_path('storage')));
        $this->assertSame(
            file_get_contents(base_path('database/seeders/assets/products/dumbbell-gym.jpg')),
            file_get_contents(public_path('storage/demo-products/dumbbell-gym.jpg')),
        );
    }

    public function test_repeated_preparation_preserves_uploaded_files_and_repairs_a_stale_symlink(): void
    {
        Storage::disk('public')->put('products/owner-upload.jpg', 'existing upload');
        $oldRoot = $this->temporaryPublic.'/old-storage';
        File::ensureDirectoryExists($oldRoot);
        File::put($oldRoot.'/keep.txt', 'preserve old storage');
        symlink($oldRoot, public_path('storage'));

        $this->artisan('assets:prepare')->assertSuccessful();
        $this->artisan('assets:prepare')->assertSuccessful();

        $this->assertSame('existing upload', file_get_contents(public_path('storage/products/owner-upload.jpg')));
        $this->assertSame('preserve old storage', File::get($oldRoot.'/keep.txt'));
    }

    public function test_an_existing_public_storage_directory_is_preserved_instead_of_deleted(): void
    {
        File::ensureDirectoryExists(public_path('storage'));
        File::put(public_path('storage/upload.jpg'), 'do not remove');

        $this->artisan('assets:prepare')->assertFailed();

        $this->assertFalse(is_link(public_path('storage')));
        $this->assertSame('do not remove', File::get(public_path('storage/upload.jpg')));
    }

    public function test_bundled_banner_urls_do_not_depend_on_the_configured_app_hostname(): void
    {
        config(['app.url' => 'https://wrong-host.example.test']);

        $this->assertSame('/images/banners/care-960.webp', Storage::disk('public_assets')->url('banners/care-960.webp'));
    }
}
