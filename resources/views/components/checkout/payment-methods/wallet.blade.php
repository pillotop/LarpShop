@props(['order', 'canCreatePayment' => false])

<form
    action="{{ route('payments.store', $order) }}"
    method="POST"
>
    @csrf
    <h2 class="h4 fw-bold mb-3">PayPal</h2>
    <p>You will approve your payment on PayPal, then return here to confirm it.</p>
    <input
        type="hidden"
        name="payment_option"
        value="paypal"
    >
    <button
        type="submit"
        class="btn btn-primary w-100 py-3"
        @disabled(! $canCreatePayment)
    >
        Continue to PayPal
    </button>
</form>
