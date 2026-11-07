@props(['order', 'canCreatePayment' => false])

<form
    action="{{ route('payments.store', $order) }}"
    method="POST"
>
    @csrf
    <h2 class="h4 fw-bold mb-3">PayPal</h2>
    <p>{{ __('checkout.wallet.notice') }}</p>
    <input
        type="hidden"
        name="payment_option"
        value="paypal"
    >
    <button
        type="submit"
        class="btn btn-solid theme-primary w-100 py-3"
        @disabled(! $canCreatePayment)
    >
        {{ __('checkout.wallet.continue') }}
    </button>
</form>
