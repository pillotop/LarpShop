<?php

namespace App\Queries\Promotions;

use App\Data\Promotions\PromoSectionData;
use App\Enums\PromoSource;
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

    public function findOptionalForPreview(int $id): ?PromoSection
    {
        return PromoSection::query()->with('collection')->find($id);
    }

    public function previewAutomatic(PromoSource $source, ?PromoSection $record = null): PromoSectionData
    {
        if ($source === PromoSource::Collection) {
            throw new InvalidArgumentException('Automatic previews require an automatic source.');
        }
        $section = $record ? clone $record : new PromoSection;
        $section->source = $source->value;
        $section->placement = config('promotions.broad_select.placement');
        $section->layout = 'row';
        $section->product_collection_id = null;
        $section->setRelation('collection', null);

        return new PromoSectionData($section, $this->products->automaticPromoProducts(
            $source, config('promotions.broad_select.product_limit'),
        ));
    }

    /** Only the admin preview flow may bypass collection publication and section scheduling. */
    public function preview(ProductCollection $collection, string $layout, ?PromoSection $source = null): PromoSectionData
    {
        if (! array_key_exists($layout, config('promotions.layouts'))) {
            throw new InvalidArgumentException('Unsupported promo layout.');
        }

        $section = $source ? clone $source : new PromoSection;
        $section->layout = $layout;
        $section->source = PromoSource::Collection->value;
        $section->product_collection_id = $collection->getKey();
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
        $broadSelect = $placement === config('promotions.broad_select.placement');
        if (! $broadSelect && ! in_array($placement, config('promotions.placements'), true)) {
            throw new InvalidArgumentException('Unsupported promo placement.');
        }

        $sections = PromoSection::query()->where('placement', $placement)
            ->whereIn('layout', array_keys(config('promotions.layouts')))
            ->active()->scheduledAt($at ?? now())
            ->where(fn (Builder $query) => $this->constrainSources($query, $broadSelect))
            ->with('collection')
            ->orderBy('sort_order')->orderBy('promo_section_id')
            ->limit($broadSelect ? config('promotions.broad_select.max_groups') : config('promotions.max_sections'))
            ->get();
        if ($sections->isEmpty()) {
            return collect();
        }

        $curated = $sections->where('source', PromoSource::Collection->value);
        $collections = new EloquentCollection($curated->pluck('collection')->unique('product_collection_id')->all());
        if ($collections->isNotEmpty()) {
            $limit = $curated->max(fn (PromoSection $section) => $this->productLimit($section));
            $this->products->loadPromoProducts($collections, $limit);
        }
        $byId = $collections->keyBy('product_collection_id');
        $automatic = $this->loadAutomaticProducts($sections);

        return $sections->map(function (PromoSection $section) use ($byId, $automatic) {
            $products = $section->source === PromoSource::Collection->value
                ? $byId[$section->product_collection_id]->products
                : $automatic[$section->source];

            return new PromoSectionData($section, $products->take($this->productLimit($section))->values());
        })->filter(fn (PromoSectionData $data) => $data->products->isNotEmpty())->values()->toBase();
    }

    private function constrainSources(Builder $query, bool $allowAutomatic): void
    {
        $query->where(fn (Builder $curated) => $curated
            ->where('source', PromoSource::Collection->value)
            ->whereHas('collection', fn (Builder $collection) => $collection->published()
                ->whereHas('products', fn (Builder $products) => $this->products
                    ->constrainCollectionVisibility($products, inStockOnly: true))));

        if (! $allowAutomatic) {
            return;
        }
        foreach ([PromoSource::TopRated, PromoSource::NewArrivals] as $source) {
            $query->orWhere(fn (Builder $automatic) => $automatic
                ->where('source', $source->value)->whereNull('product_collection_id')
                ->whereExists($this->products->automaticPromoCandidates($source)->selectRaw('1')->toBase()));
        }
    }

    /** Load each automatic source once, even when several sections use it. */
    private function loadAutomaticProducts(EloquentCollection $sections): array
    {
        $products = [];
        foreach ($sections->where('source', '!=', PromoSource::Collection->value)->groupBy('source') as $source => $group) {
            $limit = $group->max(fn (PromoSection $section) => $this->productLimit($section));
            $products[$source] = $this->products->automaticPromoProducts(PromoSource::from($source), $limit);
        }

        return $products;
    }

    private function productLimit(PromoSection $section): int
    {
        return $section->placement === config('promotions.broad_select.placement')
            ? max(1, (int) config('promotions.broad_select.product_limit'))
            : config('promotions.layouts')[$section->layout]['product_limit'];
    }
}
