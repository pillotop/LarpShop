<?php

namespace Tests\Feature\Vendor;

use App\Services\Banners\BannerImageProcessor;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Tests\TestCase;

class BannerImageProcessorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        config(['banners.zones.vendor_header.output_width' => 8, 'banners.zones.vendor_header.responsive_widths' => [4]]);
    }

    private function image(int $width = 8, int $height = 2): UploadedFile
    {
        // A real PNG fixture generated without GD so validation tests run everywhere.
        $chunk = fn ($type, $data) => pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
        $png = "\x89PNG\r\n\x1a\n";
        $png .= $chunk('IHDR', pack('NNCCCCC', $width, $height, 8, 2, 0, 0, 0));
        $png .= $chunk('IDAT', gzcompress(str_repeat("\0".str_repeat("\x44\x88\xaa", $width), $height)));
        $png .= $chunk('IEND', '');

        return UploadedFile::fake()->createWithContent('crop.png', $png);
    }

    public function test_unknown_zone_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(BannerImageProcessor::class)->store($this->image(), 'unknown');
    }

    public function test_wrong_dimensions_are_rejected_without_storing_files(): void
    {
        try {
            app(BannerImageProcessor::class)->store($this->image(8, 3), 'vendor_header');
            $this->fail('Invalid crop accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('banner_image', $exception->errors());
            $this->assertSame([], Storage::disk('public')->allFiles());
        }
    }

    public function test_fake_image_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(BannerImageProcessor::class)->store(
            UploadedFile::fake()->createWithContent('crop.png', '<svg></svg>'), 'vendor_header',
        );
    }

    public function test_oversized_file_is_rejected(): void
    {
        config(['banners.upload.max_cropped_bytes' => 1]);
        $this->expectException(ValidationException::class);
        app(BannerImageProcessor::class)->store($this->image(), 'vendor_header');
    }

    public function test_partial_variant_write_failure_removes_every_new_file(): void
    {
        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('PHP GD with WebP support is required.');
        }
        $realDisk = Storage::disk('public');
        $adapter = \Mockery::mock(FilesystemAdapter::class);
        $writes = 0;
        $adapter->shouldReceive('put')->andReturnUsing(function ($path, $stream) use ($realDisk, &$writes) {
            $writes++;
            if ($writes === 2) {
                return false;
            }

            return $realDisk->put($path, $stream);
        });
        $adapter->shouldReceive('delete')->once()->andReturnUsing(fn ($paths) => $realDisk->delete($paths));
        Storage::shouldReceive('disk')->with('public')->andReturn($adapter);

        try {
            app(BannerImageProcessor::class)->store($this->image(), 'vendor_header');
            $this->fail('Expected storage failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Unable to store banner image.', $exception->getMessage());
        }
        $this->assertSame([], $realDisk->allFiles());
    }

    public function test_valid_crop_is_reencoded_as_webp_with_generated_path(): void
    {
        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('PHP GD with WebP support is required.');
        }
        $result = app(BannerImageProcessor::class)->store($this->image(), 'vendor_header');
        Storage::disk('public')->assertExists($result['image_path']);
        $size = getimagesize(Storage::disk('public')->path($result['image_path']));
        $this->assertSame('image/webp', $size['mime']);
        $this->assertSame(8, $result['image_width']);
        $this->assertSame(2, $result['image_height']);
        $this->assertStringStartsWith('banners/', $result['image_path']);
        $this->assertStringEndsWith('.webp', $result['image_path']);
        $this->assertCount(1, $result['image_variants']);
        $variant = $result['image_variants'][0];
        Storage::disk('public')->assertExists($variant['path']);
        $size = getimagesize(Storage::disk('public')->path($variant['path']));
        $this->assertSame(4, $size[0]);
        $this->assertSame(1, $size[1]);
        $this->assertSame('image/webp', $size['mime']);
    }
}
