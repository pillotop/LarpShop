<?php

namespace App\View;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Queries\Banners\BannerGroupQuery;
use App\Queries\Promotions\PromoSectionQuery;
use App\Services\Promotions\HomepageLayoutService;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(BannerGroupQuery $banners, PromoSectionQuery $sections, HomepageLayoutService $layout): View
    {
        $at = now();
        $bannerGroups = $banners->visibleForPlacement('homepage_main', $at);
        $promos = $sections->visibleRecordsForPlacement('homepage_main', $at);
        $blocks = $layout->sort($bannerGroups->map(fn ($group) => [
            'type' => 'banner', 'id' => $group->placement_id, 'group' => $group,
            'position' => $group->homepage_position, 'legacy_order' => $group->placement_order,
        ])->toBase()->concat($promos->map(fn ($section) => [
            'type' => 'promo', 'id' => $section->getKey(),
            'position' => $section->homepage_position, 'legacy_order' => $section->sort_order,
        ]))->concat($layout->systemBlocks()));

        return view('homepage', [
            'productCategories' => Category::query()->get(),
            'homepageBlocks' => $blocks,
        ]);
    }
}
