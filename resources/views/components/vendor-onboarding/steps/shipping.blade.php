@props(['countries', 'addresses'])

<p class="fg-3">{{ __('onboarding.shipping_intro') }}</p>
@if ($addresses->isNotEmpty())
    <div class="border-bottom pb-4 mb-4">
        <label for="onboarding-saved-address" class="form-label fw-semibold">{{ __('onboarding.copy_address') }}</label>
        <div class="d-flex flex-wrap align-items-end gap-2">
            <select
                id="onboarding-saved-address"
                class="form-control rounded-2 flex-grow-1"
                x-model="selectedAddress"
                data-ui-only
            >
                <option value="">{{ __('onboarding.choose') }}</option>
                @foreach ($addresses as $address)
                    <option value="{{ $address->getKey() }}">
                        {{ $address->recipient_name }} — {{ $address->address_line_1 }}, {{ $address->city }}
                    </option>
                @endforeach
            </select>
            <button type="button" class="btn btn-outline theme-primary rounded-2" @click="useAddress()" :disabled="!selectedAddress">
                {{ __('onboarding.use_address') }}
            </button>
        </div>
        <p class="small fg-3 mt-2 mb-0">{{ __('onboarding.address_snapshot') }}</p>
    </div>
@endif

<div class="onboarding-fields">
    <x-vendor-onboarding.field name="pickup_recipient_name" :max="100" required />
    <x-vendor-onboarding.field name="pickup_phone_number" type="tel" :max="30" required />
    <x-vendor-onboarding.field name="pickup_country_code" type="select" :options="$countries" required />
    <x-vendor-onboarding.field name="pickup_postal_code" :max="30" />
    <x-vendor-onboarding.field name="pickup_address_line_1" :max="255" required />
    <x-vendor-onboarding.field name="pickup_address_line_2" :max="255" />
    <x-vendor-onboarding.field name="pickup_city" :max="100" required />
    <x-vendor-onboarding.field name="pickup_region" :max="100" />
    <div class="onboarding-full border-top pt-4 mt-2">
        <x-vendor-onboarding.field name="delivery_mode" type="select" :options="__('onboarding.delivery_modes')" required />
        <p class="small fg-3 mt-2">{{ __('onboarding.delivery_notice') }}</p>
    </div>
    <fieldset
        class="onboarding-full"
        x-show="form.delivery_mode === 'selected_countries'"
        x-cloak
    >
        <legend class="h6 fw-semibold">{{ str(__('validation.attributes.destination_countries'))->ucfirst() }}</legend>
        <input
            id="onboarding-destination_countries"
            type="search"
            class="form-control rounded-2 mb-2"
            x-model="countrySearch"
            placeholder="{{ __('onboarding.search_countries') }}"
            aria-label="{{ __('onboarding.search_countries') }}"
            aria-describedby="onboarding-destinations-error"
            data-ui-only
        >
        <p class="small fg-3" aria-live="polite">
            <span x-text="form.destination_countries.length"></span> {{ __('onboarding.countries_selected') }}
        </p>
        <div class="onboarding-countries border rounded-2 p-3">
            <template x-for="[code, label] in filteredCountries" :key="code">
                <label class="d-flex align-items-center gap-2 py-1">
                    <input type="checkbox" class="checkbox" :value="code" x-model="form.destination_countries">
                    <span x-text="label"></span>
                </label>
            </template>
            <p class="fg-3 mb-0" x-show="filteredCountries.length === 0">{{ __('onboarding.no_countries') }}</p>
        </div>
        <p
            id="onboarding-destinations-error"
            class="small text-danger mt-1"
            x-show="fieldError('destination_countries')"
            x-text="fieldError('destination_countries')"
        ></p>
    </fieldset>
</div>
