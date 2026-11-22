<?php

namespace App\Data\Banners;

use App\Models\BannerCard;
use App\Models\BannerGroup;
use Illuminate\Database\Eloquent\Collection;

class BannerPreviewData
{
    public function group(string $layout, string $presentation, string $tone): BannerGroup
    {
        $group = new BannerGroup(['name' => __('banner_groups.region'), 'layout' => $layout]);
        $images = [
            ['aestheticjourney-cream-1x1.jpg', 1920, 1920, 'care'],
            ['17067741-camera-1x1.jpg', 3856, 3856, 'tech'],
            ['viarami-product-5222331_150.jpg', 1920, 1280, 'kitchen'],
            ['8618939-makeup-150.jpg', 1280, 1920, 'beauty'],
            ['engin_akyurt-fabric-2405110_66.jpg', 3888, 2592, 'fabric'],
        ];
        $cards = new Collection;
        foreach (array_slice($images, 0, config('banners.layouts')[$layout]['card_count']) as $index => [$path, $width, $height, $key]) {
            $cards->push(new BannerCard([
                'title' => __('banner_groups.samples.'.$key.'.title'),
                'alt_text' => __('banner_groups.samples.'.$key.'.alt'),
                'eyebrow' => __('banner_groups.sample_eyebrow'),
                'cta_text' => __('banner_groups.explore'),
                'badge_text' => $index === 0 ? __('banner_groups.sample_badge') : null,
                'image_disk' => 'public_assets', 'image_path' => $path,
                'image_width' => $width, 'image_height' => $height,
                'presentation' => $presentation, 'text_tone' => $tone,
                'link_url' => route('products.index'), 'focal_x' => 50, 'focal_y' => 50,
                'is_active' => true, 'sort_order' => $index,
            ]));
        }

        return $group->setRelation('cards', $cards);
    }
}
