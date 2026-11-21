@props(['countries'])

<p class="fg-3">{{ __('onboarding.seller_intro') }}</p>
<div class="onboarding-fields">
    <x-vendor-onboarding.field
        name="seller_type"
        type="select"
        :options="__('onboarding.seller_types')"
        required
    />
    <x-vendor-onboarding.field
        name="seller_country_code"
        type="select"
        :options="$countries"
        required
    />
    <div class="onboarding-full" x-show="form.seller_type === 'business'" x-cloak>
        <x-vendor-onboarding.field name="business_name" :max="150" required />
    </div>
    <x-vendor-onboarding.field name="contact_name" :max="150" autocomplete="name" required />
    <x-vendor-onboarding.field name="contact_email" type="email" :max="100" autocomplete="email" required />
    <x-vendor-onboarding.field name="contact_phone" type="tel" :max="30" autocomplete="tel" required />
</div>
