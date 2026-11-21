<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PromoSource;
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
        $source = $request->previewSection();
        $productSource = PromoSource::from($input['source']);
        $choices = $collections->recentChoices();
        $collection = $productSource === PromoSource::Collection
            ? (isset($input['collection']) ? $collections->findForPreview((int) $input['collection']) : $choices->first())
            : null;
        $layout = $input['layout'];

        if ($collection && ! $choices->contains($collection->getKey())) {
            $choices->push($collection);
        }

        return view('admin.promotions.preview', [
            'collections' => $choices,
            'selectedCollection' => $collection,
            'layout' => $layout,
            'productSource' => $productSource->value,
            'placement' => $input['placement'],
            'source' => $source,
            'preview' => $productSource !== PromoSource::Collection
                ? $sections->previewAutomatic($productSource, $source)
                : ($collection ? $sections->preview($collection, $layout, $source) : null),
        ]);
    }
}
