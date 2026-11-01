<div>
    <h2 class="h4 fw-bold mb-3">{{ __('checkout.payment.card') }}</h2>

    <p class="small fg-3">
        {{ __('checkout.card.unavailable') }}
    </p>

    <fieldset
        disabled
        class="row g-3 mb-4"
        aria-label="{{ __('checkout.card.preview') }}"
    >
        <div
            class="col-12"
        >
            <label
                for="demo-card-name"
                class="form-label"
            >{{ __('checkout.card.name') }}</label>
            <input
                id="demo-card-name"
                class="form-control"
                value="Demo Customer"
            >
        </div>
        <div
            class="col-12"
        >
            <label
                for="demo-card-number"
                class="form-label"
            >{{ __('checkout.card.number') }}</label>
            <input
                id="demo-card-number"
                class="form-control"
                value="4242 4242 4242 4242"
            >
        </div>
        <div
            class="col-6"
        >
            <label
                for="demo-card-expiry"
                class="form-label"
            >{{ __('checkout.card.expiry') }}</label>
            <input
                id="demo-card-expiry"
                class="form-control"
                value="12/30"
            >
        </div>
        <div
            class="col-6"
        >
            <label
                for="demo-card-cvc"
                class="form-label"
            >{{ __('checkout.card.security') }}</label>
            <input
                id="demo-card-cvc"
                class="form-control"
                value="123"
            >
        </div>
    </fieldset>

    <button
        type="button"
        class="btn btn-solid theme-secondary w-100 py-3"
        disabled
    >
        {{ __('checkout.card.soon') }}
    </button>
</div>
