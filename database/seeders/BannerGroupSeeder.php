<?php

namespace Database\Seeders;

use App\Models\BannerGroup;
use App\Models\BannerGroupPlacement;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BannerGroupSeeder extends Seeder
{
    public function run(): void
    {
        // Reuse repository-owned photography; never download or write upload files.
        $subjects = [
            'care' => ['A little care, every day', 'Chăm chút mỗi ngày', 'Skincare cream with green leaves', 'Kem dưỡng da bên những chiếc lá xanh', 'beauty-and-personal-care'],
            'tech' => ['Capture your next adventure', 'Lưu giữ hành trình tiếp theo', 'Vintage camera on a pink background', 'Máy ảnh cổ điển trên nền hồng', 'electronics'],
            'kitchen' => ['Make room for good food', 'Thêm cảm hứng vào bếp', 'Wooden kitchen utensils', 'Dụng cụ nhà bếp bằng gỗ', 'home-and-living'],
            'beauty' => ['Find your everyday favorites', 'Tìm món yêu thích mỗi ngày', 'Red lipstick against a dark background', 'Son đỏ trên nền tối', 'beauty-and-personal-care'],
            'fabric' => ['Create something of your own', 'Sáng tạo theo cách của bạn', 'Colorful fabric textures', 'Những lớp vải nhiều màu', 'arts-crafts-and-sewing'],
        ];
        $definitions = [
            ['demo-home-discoveries', 'Everyday discoveries', 'mosaic_4', ['care', 'tech', 'kitchen', 'fabric'], true],
            ['demo-care-duo', 'Care and beauty', 'duo', ['care', 'beauty'], false],
            ['demo-everyday-trio', 'Everyday essentials', 'trio', ['tech', 'kitchen', 'care'], false],
            ['demo-creative-feature', 'Creative living', 'feature', ['fabric', 'kitchen', 'care'], false],
            ['demo-discovery-mosaic', 'Discover the catalog', 'mosaic_5', ['tech', 'care', 'kitchen', 'beauty', 'fabric'], false],
        ];

        foreach ($definitions as [$slug, $name, $layout, $keys, $homepage]) {
            DB::transaction(function () use ($slug, $name, $layout, $keys, $homepage, $subjects) {
                $group = BannerGroup::firstOrCreate(['slug' => $slug], [
                    'name' => $name, 'layout' => $layout, 'is_active' => true,
                ]);
                // Existing groups, including incomplete drafts and detached placements, belong to admin.
                if (! $group->wasRecentlyCreated) {
                    return;
                }
                foreach ($keys as $position => $key) {
                    [$title, $viTitle, $alt, $viAlt, $category] = $subjects[$key];
                    $image = $this->image($key);
                    $destination = Category::active()->where('slug', $category)->exists()
                        ? '/products?'.http_build_query(['category' => $category]) : '/products';
                    $group->cards()->create([
                        ...$image,
                        'title' => $title, 'alt_text' => $alt,
                        'eyebrow' => 'Everyday inspiration', 'cta_text' => 'Explore collection',
                        'badge_text' => $position === 0 ? 'Discover more' : null,
                        'translations' => ['vi' => [
                            'title' => $viTitle, 'alt_text' => $viAlt,
                            'eyebrow' => 'Cảm hứng mỗi ngày', 'cta_text' => 'Khám phá bộ sưu tập',
                            'badge_text' => $position === 0 ? 'Khám phá thêm' : null,
                        ]],
                        'link_url' => $destination, 'presentation' => $layout === 'duo' ? 'top_copy' : 'bottom_caption',
                        'text_tone' => 'light', 'focal_x' => 50, 'focal_y' => 50,
                        'sort_order' => $position, 'is_active' => true,
                    ]);
                }
                if ($homepage && ! BannerGroupPlacement::where('placement', 'homepage_main')->exists()) {
                    $group->placements()->create(['placement' => 'homepage_main', 'sort_order' => 0]);
                }
            });
        }
    }

    private function image(string $key): array
    {
        $variants = [];
        foreach ([480, 960, 1600] as $width) {
            // Portrait source photography has a 1067px main width after bounding its long edge.
            $width = $key === 'beauty' && $width === 1600 ? 1067 : $width;
            $path = "banners/{$key}-{$width}.webp";
            $size = @getimagesize(public_path('images/'.$path));
            if (! $size || $size[0] !== $width || $size['mime'] !== 'image/webp') {
                throw new RuntimeException('Missing or invalid bundled banner image: '.$path);
            }
            $variants[] = ['path' => $path, 'width' => $size[0], 'height' => $size[1]];
        }
        $main = array_pop($variants);

        return [
            'image_disk' => 'public_assets', 'image_path' => $main['path'],
            'image_width' => $main['width'], 'image_height' => $main['height'], 'image_variants' => $variants,
        ];
    }
}
