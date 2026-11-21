<?php

namespace App\View;

use App\Http\Controllers\Controller;
use App\Services\Promotions\BroadSelectService;
use Illuminate\Http\Response;

class HomeBroadSelectController extends Controller
{
    public function __invoke(BroadSelectService $broadSelect): Response
    {
        $groups = $broadSelect->forHomepage();

        // Cards contain session-specific wishlist state and CSRF tokens.
        if ($groups->isEmpty()) {
            return response('', 204)->header('Cache-Control', 'private, no-store');
        }

        return response()->view('products.partials.home-broad-select', [
            'broadSelectGroups' => $groups,
        ])->header('Cache-Control', 'private, no-store');
    }
}
