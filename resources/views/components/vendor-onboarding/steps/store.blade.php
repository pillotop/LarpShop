<p class="fg-3">{{ __('onboarding.store_intro') }}</p>
<div class="d-grid gap-4">
    <x-vendor-onboarding.field name="store_name" :max="100" required />
    <x-vendor-onboarding.field name="description" type="textarea" :max="1000" />
    <div>
        <label class="form-label fw-semibold" for="onboarding-brand_image">
            {{ str(__('validation.attributes.brand_image'))->ucfirst() }} ({{ __('onboarding.optional') }})
        </label>
        <div class="d-flex flex-wrap align-items-center gap-3">
            <img
                :src="photoUrl"
                src="{{ asset('images/default-vendor.svg') }}"
                alt="{{ __('onboarding.logo_preview') }}"
                class="onboarding-logo border rounded-2"
                width="88"
                height="88"
            >
            <div class="flex-grow-1">
                <input
                    id="onboarding-brand_image"
                    type="file"
                    name="brand_image"
                    accept="image/jpeg,image/png,image/webp"
                    x-ref="logoInput"
                    @change="chooseImage($event)"
                    class="form-control rounded-2"
                    aria-describedby="onboarding-logo-help onboarding-brand_image-error"
                    :aria-invalid="Boolean(fieldError('brand_image'))"
                >
                <p id="onboarding-logo-help" class="small fg-3 mt-2 mb-0">{{ __('onboarding.logo_help') }}</p>
                <button
                    type="button"
                    class="btn btn-link px-0 mt-1"
                    @click="removePhoto()"
                    x-show="hasPhoto"
                >
                    {{ __('onboarding.remove_logo') }}
                </button>
            </div>
        </div>
        <p
            id="onboarding-brand_image-error"
            class="small text-danger mt-2"
            x-show="fieldError('brand_image')"
            x-text="fieldError('brand_image')"
            x-cloak
        ></p>
    </div>
</div>
