<?php

namespace App\Http\Controllers\Admin;

use App\Data\Banners\BannerPreviewData;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BannerGroupPreviewController extends Controller
{
    public function __invoke(Request $request, BannerPreviewData $samples): View
    {
        $data = $request->validate([
            'layout' => ['sometimes', 'required', Rule::in(array_keys(config('banners.layouts')))],
            'presentation' => ['sometimes', 'required', Rule::in(config('banners.presentations'))],
            'tone' => ['sometimes', 'required', Rule::in(config('banners.text_tones'))],
            'width' => ['sometimes', 'required', Rule::in(['full', 'tablet', 'mobile'])],
        ]);
        $layout = $data['layout'] ?? 'mosaic_4';
        $presentation = $data['presentation'] ?? 'bottom_caption';
        $tone = $data['tone'] ?? 'light';
        $width = $data['width'] ?? 'full';
        $group = $samples->group($layout, $presentation, $tone);

        return view('admin.banners.preview', compact('layout', 'presentation', 'tone', 'width', 'group'));
    }
}
