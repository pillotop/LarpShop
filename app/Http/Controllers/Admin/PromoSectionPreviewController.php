<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotions\PreviewPromoSectionRequest;
use App\Queries\Promotions\ProductCollectionQuery;
use App\Queries\Promotions\PromoSectionQuery;
use Illuminate\View\View;

class PromoSectionPreviewController extends Controller
{
    public function __invoke(
        PreviewPromoSectionRequest $request,
        ProductCollectionQuery $collections,
        PromoSectionQuery $sections,
    ): View {
        $input = $request->validated();
        $source = isset($input['section']) ? $sections->findForPreview((int) $input['section']) : null;
        $choices = $collections->recentChoices();
        $collection = isset($input['collection'])
            ? $collections->findForPreview((int) $input['collection'])
            : ($source?->collection ?? $choices->first());
        $layout = $input['layout'] ?? $source?->layout ?? 'grid';
        if (! array_key_exists($layout, config('promotions.layouts'))) {
            $layout = 'grid';
        }

        if ($collection && ! $choices->contains($collection->getKey())) {
            $choices->push($collection);
        }

        return view('admin.promotions.preview', [
            'collections' => $choices,
            'selectedCollection' => $collection,
            'layout' => $layout,
            'source' => $source,
            'preview' => $collection ? $sections->preview($collection, $layout, $source) : null,
        ]);
    }
}
