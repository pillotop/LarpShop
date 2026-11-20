<div class="row g-3">
    <div class="col-12 md:col-6">
        <label for="section-collection" class="form-label d-block">{{ __('promotions.collection') }}</label>
        <select
            id="section-collection"
            name="product_collection_id"
            class="form-control rounded-2"
            aria-describedby="section-collection-hint"
            required
        >
            <option value="">{{ __('promotions.choose_collection') }}</option>
            @foreach ($collections as $collection)
                <option
                    value="{{ $collection->getKey() }}"
                    @selected((string) old('product_collection_id', $section->collection?->getKey()) === (string) $collection->getKey())
                >{{ $collection->name }}{{ $collection->is_published ? '' : ' — '.__('promotions.draft') }}</option>
            @endforeach
        </select>
        <p id="section-collection-hint" class="small fg-3 mt-2">{{ __('promotions.collection_choice_hint') }}</p>
    </div>
    <div class="col-12 md:col-3">
        <label for="section-layout" class="form-label d-block">{{ __('promotions.layout') }}</label>
        <select id="section-layout" name="layout" class="form-control rounded-2" x-model="layout" required>
            @foreach (config('promotions.layouts') as $key => $definition)
                <option value="{{ $key }}">{{ __('promotions.layouts.'.$key) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 md:col-3">
        <label for="section-placement" class="form-label d-block">{{ __('promotions.placement') }}</label>
        <select id="section-placement" name="placement" class="form-control rounded-2" required>
            @foreach (config('promotions.placements') as $placement)
                <option value="{{ $placement }}" @selected(old('placement', $section->placement) === $placement)>{{ __('promotions.placements.'.$placement) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12">
        <label for="section-heading" class="form-label d-block">{{ __('promotions.heading') }}</label>
        <input
            id="section-heading"
            name="heading"
            type="text"
            maxlength="150"
            value="{{ old('heading', $section->heading) }}"
            class="form-control rounded-2"
            aria-describedby="section-heading-hint"
        >
        <p id="section-heading-hint" class="small fg-3 mt-2">{{ __('promotions.heading_hint') }}</p>
    </div>
    <div class="col-12">
        <label for="section-introduction" class="form-label d-block">{{ __('promotions.introduction') }}</label>
        <textarea
            id="section-introduction"
            name="introduction"
            rows="3"
            maxlength="5000"
            class="form-control rounded-2"
        >{{ old('introduction', $section->introduction) }}</textarea>
    </div>
</div>
