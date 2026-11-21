@props(['editable' => true])

@php
    $groups = [
        'seller' => ['seller_type', 'seller_country_code', 'business_name', 'contact_name', 'contact_email', 'contact_phone'],
        'store' => ['store_name', 'description'],
        'shipping' => ['pickup_recipient_name', 'pickup_phone_number', 'pickup_country_code', 'pickup_address_line_1', 'pickup_address_line_2', 'pickup_city', 'pickup_region', 'pickup_postal_code', 'delivery_mode', 'destination_countries'],
    ];
@endphp

<div class="d-grid gap-3">
    @foreach ($groups as $step => $fields)
        <section class="border rounded-2 p-3">
            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                <h3 class="h6 fw-bold mb-0">{{ __('onboarding.steps.'.$step) }}</h3>
                @if ($editable)
                    <button
                        type="button"
                        class="btn btn-link p-0"
                        @click="backTo({{ $loop->index }})"
                    >
                        {{ __('onboarding.edit') }}
                        <span class="visually-hidden">{{ __('onboarding.steps.'.$step) }}</span>
                    </button>
                @endif
            </div>
            @if ($step === 'store')
                <img
                    :src="photoUrl"
                    src="{{ asset('images/default-vendor.svg') }}"
                    class="onboarding-logo rounded-2 mb-3"
                    alt="{{ __('onboarding.logo_preview') }}"
                    width="88"
                    height="88"
                >
            @endif
            <dl class="mb-0">
                @foreach ($fields as $field)
                    <div
                        class="onboarding-summary-row"
                        @if ($field === 'business_name') x-show="form.seller_type === 'business'" @endif
                        @if ($field === 'destination_countries') x-show="form.delivery_mode === 'selected_countries'" @endif
                    >
                        <dt class="small fw-normal fg-3">{{ str(__('validation.attributes.'.$field))->ucfirst() }}</dt>
                        <dd class="mb-0" x-text="displayValue('{{ $field }}')"></dd>
                    </div>
                @endforeach
            </dl>
        </section>
    @endforeach
</div>
