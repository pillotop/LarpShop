@props(['order', 'canCreatePayment' => false])

@php
    $providers = [
        'paypal' => 'bi-paypal',
        'momo' => 'bi-wallet2',
        'zalopay' => 'bi-wallet2',
        'vnpay' => 'bi-bank',
    ];
    foreach (['momo', 'zalopay', 'vnpay'] as $provider) {
        if (config("payments.providers.{$provider}_demo.enabled", false)) {
            $providers[$provider.'_demo'] = $providers[$provider];
            unset($providers[$provider]);
        }
    }
    $available = array_values(array_filter(
        array_keys($providers),
        fn ($provider) => config("payments.providers.{$provider}.enabled", true),
    ));
    $selected = in_array(old('payment_option'), $available, true)
        ? old('payment_option')
        : ($available[0] ?? null);
@endphp

<form
    action="{{ route('payments.store', $order) }}"
    method="POST"
    class="wallet-payment-form d-grid gap-3"
>
    @csrf
    <fieldset>
        <legend class="h4 fw-bold mb-3">{{ __('checkout.wallet.title') }}</legend>
        <div class="wallet-provider-options">
            @foreach ($providers as $provider => $icon)
                @php
                    $enabled = in_array($provider, $available, true);
                    $label = config("payments.providers.{$provider}.label");
                @endphp
                <label class="wallet-provider rounded-2">
                    <input
                        type="radio"
                        name="payment_option"
                        value="{{ $provider }}"
                        class="radio wallet-provider-input"
                        required
                        @checked($selected === $provider)
                        @disabled(! $canCreatePayment || ! $enabled)
                    >
                    <i
                        class="bi {{ $icon }} fs-4"
                        aria-hidden="true"
                    ></i>
                    <span class="wallet-provider-brand">{{ $label }}</span>
                    @if (config("payments.providers.{$provider}.simulated", false))
                        <span class="small text-body-secondary">
                            {{ __('checkout.wallet.simulated') }}
                        </span>
                    @endif
                    @unless ($enabled)
                        <span class="small text-body-secondary">
                            {{ __('checkout.wallet.unavailable') }}
                        </span>
                    @endunless
                </label>
            @endforeach
        </div>
    </fieldset>

    <p class="small text-body-secondary mb-0">{{ __('checkout.wallet.selection_notice') }}</p>
    <p class="small text-body-secondary mb-0">{{ __('checkout.wallet.sandbox') }}</p>
    @if (collect(array_keys($providers))->contains(fn ($provider) => config("payments.providers.{$provider}.simulated", false)))
        <p class="small text-body-secondary mb-0">{{ __('checkout.wallet.simulation_notice') }}</p>
    @endif
    <button
        type="submit"
        class="btn btn-solid theme-primary rounded-2 w-100 py-3"
        @disabled(! $canCreatePayment || $selected === null)
    >
        {{ __('checkout.payment.continue') }}
    </button>
</form>
