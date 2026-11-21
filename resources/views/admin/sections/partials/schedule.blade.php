<fieldset class="border-top mt-4 pt-3">
    <legend class="h5">{{ __('promotions.schedule') }}</legend>
    <p class="small fg-3">{{ __('promotions.schedule_hint', ['timezone' => config('app.timezone')]) }}</p>
    <div class="row g-3">
        @foreach (['starts_at', 'expires_at'] as $field)
            <div class="col-12 md:col-6">
                <label for="section-{{ $field }}" class="form-label d-block">{{ __('promotions.'.$field) }}</label>
                <input
                    id="section-{{ $field }}"
                    name="{{ $field }}"
                    type="datetime-local"
                    value="{{ old($field, $section->{$field}?->timezone(config('app.timezone'))->format('Y-m-d\TH:i')) }}"
                    class="form-control "
                >
            </div>
        @endforeach
    </div>
    <div class="d-flex align-items-center gap-2 mt-3">
        <input
            id="section-active"
            name="is_active"
            type="checkbox"
            value="1"
            class="check"
            @checked(old('is_active', $section->is_active))
        >
        <label for="section-active" class="mb-0">{{ __('promotions.enable_section') }}</label>
    </div>
    <p class="small fg-3 mt-2">{{ __('promotions.visibility_hint', ['count' => config('promotions.max_sections')]) }}</p>
</fieldset>
