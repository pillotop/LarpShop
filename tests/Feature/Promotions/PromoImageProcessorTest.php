<?php

namespace Tests\Feature\Promotions;

use App\Services\Promotions\PromoImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Tests\TestCase;

class PromoImageProcessorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        config(['promotions.image_profiles.portrait.output_width' => 8,
            'promotions.image_profiles.portrait.responsive_widths' => [4]]);
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

    public function test_unknown_profile_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        app(PromoImageProcessor::class)->store($this->image(), 'vendor_header');
    }

    public function test_invalid_crop_reports_the_promo_field_without_writing_files(): void
    {
        try {
            app(PromoImageProcessor::class)->store($this->image(2), 'portrait');
            $this->fail('Invalid portrait accepted.');
        } catch (ValidationException $error) {
            $this->assertArrayHasKey('promo_image', $error->errors());
            $this->assertSame([], Storage::disk('public')->allFiles());
        }
    }

    public function test_profile_has_its_own_upload_limit(): void
    {
        config(['promotions.upload.max_cropped_bytes' => 1]);
        $this->expectException(ValidationException::class);
        app(PromoImageProcessor::class)->store($this->image(), 'portrait');
    }

    public function test_valid_crop_uses_promo_storage_and_creates_responsive_webp_variants(): void
    {
        if (! function_exists('imagewebp')) {
            $this->markTestSkipped('PHP GD with WebP support is required.');
        }
        $result = app(PromoImageProcessor::class)->store($this->image(), 'portrait');
        $this->assertStringStartsWith('promotions/', $result['image_path']);
        $this->assertSame(10, $result['image_height']);
        $this->assertCount(1, $result['image_variants']);
        foreach ([$result, ...$result['image_variants']] as $image) {
            $path = $image['image_path'] ?? $image['path'];
            Storage::disk('public')->assertExists($path);
            $size = getimagesize(Storage::disk('public')->path($path));
            $this->assertSame('image/webp', $size['mime']);
            $this->assertSame($size[0] * 5, $size[1] * 4);
        }
    }
}
