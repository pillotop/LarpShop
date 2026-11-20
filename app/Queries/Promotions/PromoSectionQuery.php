<?php

namespace App\Queries\Promotions;

use App\Data\Promotions\PromoSectionData;
use App\Models\ProductCollection;
use App\Models\PromoSection;
use App\Queries\Products\ProductCatalogQuery;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class PromoSectionQuery
{
    public function __construct(private ProductCatalogQuery $products) {}

    public function forAdmin(): LengthAwarePaginator
    {
        return PromoSection::query()->with('collection')
            ->orderBy('placement')->orderBy('sort_order')->orderBy('promo_section_id')
            ->paginate(15);
    }

    public function findForPreview(int $id): PromoSection
    {
        return PromoSection::query()->with('collection')->findOrFail($id);
    }

    /** Only the admin preview flow may bypass collection publication and section scheduling. */
    public function preview(ProductCollection $collection, string $layout, ?PromoSection $source = null): PromoSectionData
    {
        if (! array_key_exists($layout, config('promotions.layouts'))) {
            throw new InvalidArgumentException('Unsupported promo layout.');
        }

        $section = $source ? clone $source : new PromoSection;
        $section->layout = $layout;
        $section->setRelation('collection', $collection);

        return $this->prepare($section);
    }

    private function prepare(PromoSection $section): PromoSectionData
    {
        return new PromoSectionData(
            $section,
            $this->products->collectionProducts(
                $section->collection,
                config('promotions.layouts')[$section->layout]['product_limit'],
            ),
        );
    }

    /** @return Collection<int, PromoSectionData> */
    public function visibleForPlacement(string $placement, ?CarbonInterface $at = null): Collection
    {
        if (! in_array($placement, config('promotions.placements'), true)) {
            throw new InvalidArgumentException('Unsupported promo placement.');
        }

        $layouts = config('promotions.layouts');
        $sections = PromoSection::query()->where('placement', $placement)
            ->whereIn('layout', array_keys($layouts))
            ->active()->scheduledAt($at ?? now())
            ->whereHas('collection', fn (Builder $collection) => $collection->published()
                ->whereHas('products', fn (Builder $products) => $this->products
                    ->constrainCollectionVisibility($products, inStockOnly: true)))
            ->with('collection')
            ->orderBy('sort_order')->orderBy('promo_section_id')
            ->limit(config('promotions.max_sections'))->get();

        $collections = new EloquentCollection($sections->pluck('collection')->unique('product_collection_id')->all());
        $this->products->loadPromoProducts($collections);
        $byId = $collections->keyBy('product_collection_id');

        return $sections->map(function (PromoSection $section) use ($byId, $layouts) {
            $products = $byId[$section->product_collection_id]->products
                ->take($layouts[$section->layout]['product_limit'])->values();

            return new PromoSectionData($section, $products);
        })->filter(fn (PromoSectionData $data) => $data->products->isNotEmpty())->values()->toBase();
    }
}
