<section
    class="border-top mt-4 pt-3"
    x-show="!!profiles[layout] || @js((bool) $section->image_path)"
>
    <h2 class="h5">{{ __('promotions.promotional_image') }}</h2>
    @if ($section->image_path)
        <div class="mb-3" style="max-width: 12rem;">
            <x-responsive-image
                :image="$section"
                :ratio="[$section->image_width ?? 4, $section->image_height ?? 5]"
                sizes="192px"
            />
        </div>
        <div class="d-flex align-items-center gap-2 mb-3">
            <input
                id="section-remove-image"
                name="remove_image"
                type="checkbox"
                value="1"
                class="check"
                @checked(old('remove_image', false))
            >
            <label for="section-remove-image" class="mb-0">{{ __('promotions.remove_image') }}</label>
        </div>
    @endif
    <p class="small fg-3">{{ __('promotions.upload_hint') }}</p>
    @foreach (config('promotions.image_profiles') as $profileKey => $profile)
        <fieldset
            x-show="profiles[layout] === @js($profileKey)"
            x-bind:disabled="profiles[layout] !== @js($profileKey)"
        >
            <x-image-cropper
                name="promo_image"
                :ratio="$profile['aspect_ratio']"
                :width="$profile['output_width']"
            />
        </fieldset>
    @endforeach
    <div class="mt-3" x-show="!!profiles[layout]">
        <label for="section-alt-text" class="form-label d-block">{{ __('promotions.alt_text') }}</label>
        <input
            id="section-alt-text"
            name="alt_text"
            type="text"
            maxlength="255"
            value="{{ old('alt_text', $section->alt_text) }}"
            class="form-control rounded-2"
            x-bind:disabled="!profiles[layout]"
            x-bind:required="!!profiles[layout]"
        >
    </div>
</section>
