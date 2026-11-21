<div class="col-12 md:col-4">
    <label for="section-placement" class="form-label d-block">{{ __('promotions.placement') }}</label>
    <select
        id="section-placement"
        name="placement"
        class="form-control rounded-2"
        x-model="placement"
        required
    >
        @foreach (config('promotions.placements') as $placement)
            <option value="{{ $placement }}" @selected(old('placement', $section->placement) === $placement)>{{ __('promotions.placements.'.$placement) }}</option>
        @endforeach
    </select>
</div>
<div class="col-12 md:col-4">
    <label for="section-source" class="form-label d-block">{{ __('promotions.source') }}</label>
    <select
        id="section-source"
        name="source"
        class="form-control rounded-2"
        x-model="source"
        required
    >
        @foreach (App\Enums\PromoSource::cases() as $source)
            <option
                value="{{ $source->value }}"
                @selected(old('source', $section->source) === $source->value)
                @if ($source !== App\Enums\PromoSource::Collection)
                    x-bind:disabled="!isBroad"
                @endif
            >{{ $source->label() }}</option>
        @endforeach
    </select>
    <p class="small fg-3 mt-2 mb-0" x-show="!isBroad">{{ __('promotions.source_hint') }}</p>
</div>
<div class="col-12 md:col-4">
    <label for="section-layout" class="form-label d-block">{{ __('promotions.layout') }}</label>
    <select
        id="section-layout"
        name="layout"
        class="form-control rounded-2"
        x-model="layout"
        required
    >
        @foreach (config('promotions.layouts') as $key => $definition)
            <option
                value="{{ $key }}"
                @selected(old('layout', $section->layout) === $key)
                @if ($key !== 'row')
                    x-bind:disabled="isBroad"
                @endif
            >{{ __('promotions.layouts.'.$key) }}</option>
        @endforeach
    </select>
</div>
<div class="col-12" x-show="isBroad">
    <p class="small fg-3 mb-0">{{ __('promotions.broad_select_hint', ['count' => config('promotions.broad_select.max_groups'), 'reviews' => config('promotions.broad_select.minimum_reviews')]) }}</p>
</div>
<div class="col-12" x-show="needsCollection">
    <label for="section-collection" class="form-label d-block">{{ __('promotions.collection') }}</label>
    <select
        id="section-collection"
        name="product_collection_id"
        class="form-control rounded-2"
        aria-describedby="section-collection-hint"
        x-bind:disabled="!needsCollection"
        x-bind:required="needsCollection"
    >
        <option value="">{{ __('promotions.choose_collection') }}</option>
        @foreach ($collections as $collection)
            <option
                value="{{ $collection->getKey() }}"
                @selected((string) old('product_collection_id', $section->product_collection_id) === (string) $collection->getKey())
            >{{ $collection->name }}{{ $collection->is_published ? '' : ' — '.__('promotions.draft') }}</option>
        @endforeach
    </select>
    <p id="section-collection-hint" class="small fg-3 mt-2">{{ __('promotions.collection_choice_hint') }}</p>
    @if ($collections->isEmpty())
        <p class="small fg-3">{{ __('promotions.no_collections') }}</p>
        <a href="{{ route('admin.collections.create') }}">{{ __('promotions.create_collection') }}</a>
    @endif
</div>
