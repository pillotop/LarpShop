@props(['order', 'addresses', 'canChangeAddress' => false])
@php($shipping = $order->addresses->firstWhere('type', 'shipping'))
<section aria-labelledby="checkout-address-title">
    <h2 id="checkout-address-title" class="h4 fw-bold mb-3">{{ __('checkout.address.title') }}</h2>
    @if ($shipping)
        <address class="border rounded-5 p-3 bg-1">
            <strong>{{ $shipping->recipient_name }}</strong><br>
            {{ $shipping->address_line_1 }}<br>
            @if ($shipping->address_line_2) {{ $shipping->address_line_2 }}<br> @endif
            {{ $shipping->city }} {{ $shipping->region }} {{ $shipping->postal_code }}<br>
            {{ $shipping->country_code }}
            @if ($shipping->phone_number)<br>{{ $shipping->phone_number }}@endif
        </address>
    @else
        <p class="fg-3">{{ __('checkout.address.none') }}</p>
    @endif
    @if ($canChangeAddress && $addresses->isNotEmpty())
        <form action="{{ route('checkout.address.update', $order) }}" method="POST">
            @csrf
            @method('PUT')
            <label for="checkout-address" class="form-label">{{ __('checkout.address.switch') }}</label>
            <select id="checkout-address" name="address_id" class="form-control @error('address_id') is-invalid @enderror" required>
                <option value="">{{ __('checkout.address.choose') }}</option>
                @foreach ($addresses as $address)
                    <option value="{{ $address->getKey() }}" @selected((string) old('address_id') === (string) $address->getKey())>
                        {{ $address->recipient_name }} — {{ $address->address_line_1 }}, {{ $address->city }}, {{ $address->country_code }}{{ $address->is_default ? __('checkout.address.default') : '' }}
                    </option>
                @endforeach
            </select>
            @error('address_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <button type="submit" class="btn btn-outline theme-primary mt-3">{{ __('checkout.address.use') }}</button>
        </form>
    @elseif (!$canChangeAddress)
        <p class="small fg-3">{{ __('checkout.address.locked') }}</p>
    @endif
    <a class="d-inline-block mt-3 small" href="{{ route('profile') }}#pane-billing">{{ __('checkout.address.manage_saved') }}</a>
</section>
