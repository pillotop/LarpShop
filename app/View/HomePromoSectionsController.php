<?php

namespace App\View;

use App\Http\Controllers\Controller;
use App\Queries\Promotions\PromoSectionQuery;
use Illuminate\Http\Response;

class HomePromoSectionsController extends Controller
{
    public function __invoke(PromoSectionQuery $sections): Response
    {
        $promos = $sections->visibleForPlacement('homepage_main');

        if ($promos->isEmpty()) {
            return response('', 204)->header('Cache-Control', 'private, no-store');
        }

        return response()->view('products.partials.home-promo-sections', [
            'promoSections' => $promos,
        ])->header('Cache-Control', 'private, no-store');
    }
}
