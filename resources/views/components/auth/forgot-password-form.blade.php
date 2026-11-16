<div id="auth-form" class="blur position-relative shadow border rounded-4 p-4 bg-white">
    @include('auth.partials.messages')

    <h3 class="primary-text" id="login-form-header">
        {{ __('auth.recovery.forgot_your_password') }}
    </h3>

    <p id="login-form-email-text" class="fg-3">
        {{ __('auth.recovery.enter_your_email_address_and_we_ll_send_you_a_password_reset_link') }}
    </p>

    <div
        id="login-form-status-text"
        class="form-text"
        role="status"
        aria-live="polite"
    >
        {{ session('status') }}
    </div>

    <form
        id="login-form"
        method="POST"
        data-form-state="forgot-password"
        data-forgot-password-url="{{ route('password.email') }}"
    >
        @csrf

        {{-- Forgot-password state --}}
        <fieldset id="email-state" class="border-0 p-0">
            <div class="my-2">
                <label for="email-input" class="form-label">
                    {{ __('auth.recovery.email_address') }}
                </label>

                <input
                    class="form-control @error('email') is-invalid @enderror"
                    id="email-input"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="{{ __('auth.recovery.email_address') }}"
                    autocomplete="email"
                    maxlength="100"
                    aria-describedby="email-input-error"
                    required
                    autofocus
                >

                <div
                    id="email-error"
                    class="d-block invalid-feedback"
                >
                    @error('email')
                        {{ $message }}
                    @enderror
                </div>
            </div>
        </fieldset>

        {{-- Shared controls --}}
        <div id="forgot-spinner" class="d-none text-center my-3">
            <div class="spinner-border text-danger spinner-border-sm" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <span class="small ms-2 fg-3" id="forgot-spinner-text"></span>
        </div>

        <button
            type="submit"
            id="login-form-primary-button"
            class="w-100 border-0 rounded-4 p-2 my-2"
        >
            {{ __('auth.recovery.send_reset_link') }}
        </button>

        <a
            href="{{ route('login') }}"
            id="login-form-secondary-button"
            class="btn btn-link"
        >
            {{ __('auth.recovery.back_to_sign_in') }}
        </a>
    </form>
</div>
