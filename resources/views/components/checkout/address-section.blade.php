@props(['order', 'addresses', 'canChangeAddress' => false])
@php($shipping = $order->addresses->firstWhere('type', 'shipping'))
<section aria-labelledby="checkout-address-title">
    <h2 id="checkout-address-title" class="h4 fw-bold mb-3">Shipping address</h2>
    @if ($shipping)
        <address class="border rounded-3 p-3 bg-light">
            <strong>{{ $shipping->recipient_name }}</strong><br>
            {{ $shipping->address_line_1 }}<br>
            @if ($shipping->address_line_2) {{ $shipping->address_line_2 }}<br> @endif
            {{ $shipping->city }} {{ $shipping->region }} {{ $shipping->postal_code }}<br>
            {{ $shipping->country_code }}
            @if ($shipping->phone_number)<br>{{ $shipping->phone_number }}@endif
        </address>
    @else
        <p class="text-muted">No shipping address has been selected.</p>
    @endif
    @if ($canChangeAddress && $addresses->isNotEmpty())
        <form action="{{ route('checkout.address.update', $order) }}" method="POST">
            @csrf
            @method('PUT')
            <label for="checkout-address" class="form-label">Switch to a saved address</label>
            <select id="checkout-address" name="address_id" class="form-select @error('address_id') is-invalid @enderror" required>
                <option value="">Choose an address</option>
                @foreach ($addresses as $address)
                    <option value="{{ $address->getKey() }}" @selected((string) old('address_id') === (string) $address->getKey())>
                        {{ $address->recipient_name }} — {{ $address->address_line_1 }}, {{ $address->city }}, {{ $address->country_code }}{{ $address->is_default ? ' (Default)' : '' }}
                    </option>
                @endforeach
            </select>
            @error('address_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            <button type="submit" class="btn btn-outline-primary mt-3">Use this address</button>
        </form>
    @elseif (!$canChangeAddress)
        <p class="small text-muted">The address is locked once payment starts or the order closes.</p>
    @endif
    <a class="d-inline-block mt-3 small" href="{{ route('profile') }}#pane-billing">Manage saved addresses</a>
</section>
