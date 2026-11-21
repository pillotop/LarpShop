@props(['application', 'countries'])

@php
    $groups = [
        'seller' => ['seller_type', 'seller_country_code', 'business_name', 'contact_name', 'contact_email', 'contact_phone'],
        'store' => ['store_name', 'description'],
        'shipping' => ['pickup_recipient_name', 'pickup_phone_number', 'pickup_country_code', 'pickup_address_line_1', 'pickup_address_line_2', 'pickup_city', 'pickup_region', 'pickup_postal_code', 'delivery_mode'],
    ];
@endphp

<div class="border-bottom pb-3 mb-4">
    <p class="mb-1">{{ __('onboarding.status.'.$application->status->value) }}</p>
    <p class="small fg-3 mb-1">
        {{ __('management.submitted_at') }}:
        {{ $application->submitted_at?->format('d/m/Y H:i') ?? __('onboarding.client.empty') }}
    </p>
    @if ($application->reviewed_at)
        <p class="small fg-3 mb-1">
            {{ __('onboarding.review.last_review') }}:
            {{ $application->reviewed_at->format('d/m/Y H:i') }} ·
            {{ $application->reviewer?->username ?? __('onboarding.client.empty') }}
        </p>
    @endif
    @if ($application->rejection_reason)
        <p class="mb-0 text-break">
            {{ __('onboarding.review_feedback') }}: {{ $application->rejection_reason }}
        </p>
    @endif
</div>

@foreach ($groups as $group => $fields)
    <section class="border rounded-2 p-3 mb-3">
        <h2 class="h5 fw-semibold mb-3">{{ __('onboarding.steps.'.$group) }}</h2>
        @if ($group === 'store')
            <img
                src="{{ $application->profile_image_url }}"
                alt="{{ __('onboarding.logo_preview') }}"
                width="80"
                height="80"
                class="object-fit-cover rounded-2 mb-3"
            >
        @endif
        <dl class="row g-2 mb-0">
            @foreach ($fields as $field)
                @php
                    $value = $application->{$field};
                    if ($value instanceof \BackedEnum) {
                        $key = $field === 'seller_type' ? 'seller_types' : 'delivery_modes';
                        $value = __('onboarding.'.$key.'.'.$value->value);
                    } elseif (str_ends_with($field, '_country_code')) {
                        $value = $countries[$value] ?? $value;
                    }
                @endphp
                <dt class="md:col-4 fg-3 fw-normal">{{ str(__('validation.attributes.'.$field))->ucfirst() }}</dt>
                <dd class="md:col-8 text-break">{{ $value ?: __('onboarding.client.empty') }}</dd>
            @endforeach
            @if ($group === 'shipping')
                <dt class="md:col-4 fg-3 fw-normal">{{ str(__('validation.attributes.destination_countries'))->ucfirst() }}</dt>
                <dd class="md:col-8 text-break">
                    {{ $application->destinations->map(fn ($destination) => $countries[$destination->country_code] ?? $destination->country_code)->implode(', ') ?: __('onboarding.client.empty') }}
                </dd>
            @endif
        </dl>
        @if ($group === 'shipping')
            <p class="small fg-3 mt-3 mb-0">{{ __('onboarding.delivery_notice') }}</p>
        @endif
    </section>
@endforeach
