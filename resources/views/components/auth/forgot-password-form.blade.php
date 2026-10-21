<div id="auth-form" class="blur position-relative shadow border rounded-2 p-4 bg-white">
    <h3 class="primary-text" id="login-form-header">
        Forgot your password?
    </h3>

    <p id="login-form-email-text" class="text-muted">
        Enter your email address and we'll send you a password reset link.
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
                    Email address
                </label>

                <input
                    class="form-control @error('email') is-invalid @enderror"
                    id="email-input"
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="Email address"
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
        <button
            type="submit"
            id="login-form-primary-button"
            class="w-100 border-0 rounded-2 p-2 my-2"
        >
            Send reset link
        </button>

        <a
            href="{{ route('login') }}"
            id="login-form-secondary-button"
            class="btn btn-link"
        >
            Back to sign in
        </a>
    </form>
</div>
