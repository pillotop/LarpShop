@props(['address' => null])

@if ($address)
    <address class="mb-0">
        <strong>{{ $address->recipient_name }}</strong><br>
        {{ $address->address_line_1 }}<br>
        @if ($address->address_line_2) {{ $address->address_line_2 }}<br> @endif
        {{ $address->city }} {{ $address->region }} {{ $address->postal_code }}<br>
        {{ $address->country_code }}
        @if ($address->phone_number)<br>{{ $address->phone_number }}@endif
    </address>
@else
    <p class="fg-3 mb-0">{{ __('checkout.address.none') }}</p>
@endif
