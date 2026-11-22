<?php

namespace Database\Seeders;

use App\Models\BannerGroup;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SuppliedBannerSeeder extends Seeder
{
    public function run(): void
    {
        // User-supplied campaign artwork; copy stays editable rather than baked into images.
        $subjects = [
            'pastel-tech' => ['Little upgrades, brighter days', 'Nâng cấp nhỏ, ngày thêm vui', 'Headphones and a mouse on pastel display stands', 'Tai nghe và chuột trên bục trưng bày màu pastel', 'electronics'],
            'play-and-care' => ['Make time for your favorites', 'Dành thời gian cho điều yêu thích', 'Game controller, skincare bottles and small accessories', 'Tay cầm trò chơi, chai dưỡng da và phụ kiện nhỏ', null],
            'minimal-care' => ['Keep your routine simple', 'Chăm sóc mỗi ngày thật đơn giản', 'Neutral skincare bottles beside a vase of dried grasses', 'Chai dưỡng da màu trung tính bên bình cỏ khô', 'beauty-and-personal-care'],
            'colorful-discoveries' => ['A world of discoveries', 'Thế giới của những khám phá', 'Pink, blue and yellow geometric paper background', 'Nền giấy hình học màu hồng, xanh và vàng', null],
            'shopping-moments' => ['Find your next favorite', 'Tìm món yêu thích tiếp theo', 'Smiling shopper holding colorful shopping bags', 'Người mua hàng tươi cười với những túi mua sắm nhiều màu', null],
            'everyday-listening' => ['Set the mood for your day', 'Thêm giai điệu cho ngày mới', 'Man wearing headphones around his neck and holding a phone', 'Người đàn ông đeo tai nghe quanh cổ và cầm điện thoại', 'electronics'],
            'festive-botanicals' => ['A little seasonal self-care', 'Chăm sóc bản thân mùa lễ hội', 'Skincare bottles with flowers and evergreen branches', 'Chai dưỡng da cùng hoa và cành lá xanh', 'beauty-and-personal-care'],
            'festive-beauty' => ['A little sparkle to share', 'Chia sẻ chút lấp lánh', 'Perfume, lipstick and makeup with ribbon and gold ornaments', 'Nước hoa, son và mỹ phẩm cùng ruy băng và đồ trang trí vàng', 'beauty-and-personal-care'],
            'festive-sweets' => ['Sweet ideas for the season', 'Ý tưởng ngọt ngào mùa lễ hội', 'Chocolate gift boxes with holly, ribbon and gold ornaments', 'Hộp quà sô cô la cùng lá nhựa ruồi, ruy băng và đồ trang trí vàng', null],
            'soft-fragrance' => ['Small gifts, thoughtful moments', 'Quà nhỏ, gửi trọn quan tâm', 'Pink fragrance bottles with silver ribbon and ornaments', 'Chai nước hoa hồng cùng ruy băng bạc và đồ trang trí', 'beauty-and-personal-care'],
            'pink-care' => ['Wrap up a little care', 'Gói ghém chút quan tâm', 'Pink pump bottles on a gold stand with a red bow', 'Chai vòi bơm màu hồng trên bục vàng với nơ đỏ', 'beauty-and-personal-care'],
        ];
        $groups = [
            ['supplied-everyday-upgrades', 'Pastel everyday upgrades', 'trio', ['pastel-tech', 'play-and-care', 'minimal-care']],
            ['supplied-colorful-discoveries', 'Colorful shopping inspiration', 'feature', ['colorful-discoveries', 'shopping-moments', 'everyday-listening']],
            ['supplied-seasonal-gifting', 'Seasonal beauty and sweet gifts', 'trio', ['festive-botanicals', 'festive-beauty', 'festive-sweets']],
            ['supplied-thoughtful-care', 'Thoughtful care gifts', 'duo', ['soft-fragrance', 'pink-care']],
        ];

        foreach ($groups as [$slug, $name, $layout, $keys]) {
            DB::transaction(function () use ($slug, $name, $layout, $keys, $subjects) {
                $group = BannerGroup::firstOrCreate(['slug' => $slug], [
                    'name' => $name, 'layout' => $layout, 'is_active' => true,
                ]);
                if (! $group->wasRecentlyCreated) {
                    return;
                }
                foreach ($keys as $position => $key) {
                    [$title, $viTitle, $alt, $viAlt, $category] = $subjects[$key];
                    $destination = $category && Category::active()->where('slug', $category)->exists()
                        ? '/products?'.http_build_query(['category' => $category]) : '/products';
                    $group->cards()->create([
                        ...$this->image($key),
                        'title' => $title, 'alt_text' => $alt, 'cta_text' => 'Explore products',
                        'translations' => ['vi' => [
                            'title' => $viTitle, 'alt_text' => $viAlt, 'cta_text' => 'Khám phá sản phẩm',
                        ]],
                        'link_url' => $destination, 'presentation' => 'top_copy',
                        'text_tone' => $key === 'festive-sweets' ? 'light' : 'dark',
                        'focal_x' => in_array($key, ['shopping-moments', 'everyday-listening'], true) ? 70 : 50,
                        'focal_y' => 50, 'is_active' => true, 'sort_order' => $position,
                    ]);
                }
                // Import as reusable groups; never expand or overwrite an existing page arrangement.
            });
        }
    }

    private function image(string $key): array
    {
        $mainWidth = in_array($key, ['colorful-discoveries', 'shopping-moments', 'everyday-listening'], true) ? 1600 : 1024;
        $images = [];
        foreach ([480, 960, $mainWidth] as $width) {
            $path = "banners/supplied/{$key}-{$width}.webp";
            $size = @getimagesize(public_path('images/'.$path));
            if (! $size || $size[0] !== $width || $size['mime'] !== 'image/webp') {
                throw new RuntimeException('Missing or invalid supplied banner image: '.$path);
            }
            $images[] = ['path' => $path, 'width' => $size[0], 'height' => $size[1]];
        }
        $main = array_pop($images);

        return [
            'image_disk' => 'public_assets', 'image_path' => $main['path'],
            'image_width' => $main['width'], 'image_height' => $main['height'], 'image_variants' => $images,
        ];
    }
}
