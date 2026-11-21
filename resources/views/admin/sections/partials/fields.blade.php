<div class="row g-3">
    @include('admin.sections.partials.source-fields')
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
