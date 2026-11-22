@props(['order', 'canCreatePayment' => false])

<form
    action="{{ route('payments.store', $order) }}"
    method="POST"
>
    @csrf
    <h2 class="h4 fw-bold mb-3">{{ __('checkout.payment.card') }}</h2>
    <p class="fg-3">{{ __('checkout.card.stripe_notice') }}</p>
    <p class="small fg-3">{{ __('checkout.card.sandbox') }}</p>
    <input
        type="hidden"
        name="payment_option"
        value="stripe_card"
    >
    @unless (config('payments.providers.stripe.enabled'))
        <p role="status">{{ __('checkout.card.not_configured') }}</p>
    @endunless
    <button
        type="submit"
        class="btn btn-solid theme-primary rounded-2 w-100 py-3"
        @disabled(! $canCreatePayment || ! config('payments.providers.stripe.enabled'))
    >
        {{ __('checkout.card.continue') }}
    </button>
</form>
