<form
    action="{{ route('admin.promotions.preview') }}"
    method="GET"
    class="row g-3 align-items-end border rounded-2 p-3 mb-4"
    x-data="promoSourceForm(@js([
        'source' => $productSource,
        'placement' => $placement,
        'layout' => $layout,
        'broadPlacement' => config('promotions.broad_select.placement'),
    ]))"
>
    @if ($source)
        <input type="hidden" name="section" value="{{ $source->getKey() }}">
    @endif
    <div class="col-12 md:col-4">
        <label for="preview-placement" class="form-label d-block">{{ __('promotions.placement') }}</label>
        <select
            id="preview-placement"
            name="placement"
            class="form-control rounded-2"
            x-model="placement"
        >
            @foreach (config('promotions.placements') as $value)
                <option value="{{ $value }}" @selected($placement === $value)>{{ __('promotions.placements.'.$value) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 md:col-4">
        <label for="preview-source" class="form-label d-block">{{ __('promotions.source') }}</label>
        <select
            id="preview-source"
            name="source"
            class="form-control rounded-2"
            x-model="source"
        >
            @foreach (App\Enums\PromoSource::cases() as $value)
                <option
                    value="{{ $value->value }}"
                    @selected($productSource === $value->value)
                    @if ($value !== App\Enums\PromoSource::Collection)
                        x-bind:disabled="!isBroad"
                    @endif
                >{{ $value->label() }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 md:col-4">
        <label for="preview-layout" class="form-label d-block">{{ __('promotions.layout') }}</label>
        <select
            id="preview-layout"
            name="layout"
            class="form-control rounded-2"
            x-model="layout"
        >
            @foreach (array_keys(config('promotions.layouts')) as $value)
                <option
                    value="{{ $value }}"
                    @selected($layout === $value)
                    @if ($value !== 'row')
                        x-bind:disabled="isBroad"
                    @endif
                >{{ __('promotions.layouts.'.$value) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 md:col-9" x-show="needsCollection">
        <label for="preview-collection" class="form-label d-block">{{ __('promotions.collection') }}</label>
        <select
            id="preview-collection"
            name="collection"
            class="form-control rounded-2"
            x-bind:disabled="!needsCollection"
        >
            @foreach ($collections as $collection)
                <option
                    value="{{ $collection->getKey() }}"
                    @selected($selectedCollection?->is($collection))
                >{{ $collection->name }}{{ $collection->is_published ? '' : ' · '.__('promotions.draft') }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 md:col-3">
        <button type="submit" class="btn btn-solid theme-primary rounded-2 w-100">
            {{ __('promotions.apply_preview') }}
        </button>
    </div>
</form>
