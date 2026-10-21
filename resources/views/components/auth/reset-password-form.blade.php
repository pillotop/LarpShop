@props([
    'token',
    'email' => '',
])

<div id="auth-form" class="blur position-relative shadow border rounded-2 p-4">
    <h3 class="primary-text" id="login-form-header">
        Reset your password
    </h3>

    <p id="login-form-email-text" class="text-muted">
        Enter a new password for your account.
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
        action="{{ route('password.update') }}"
        data-form-state="reset-password"
        data-reset-password-url="{{ route('password.update') }}"
    >
        @csrf

        <input
            type="hidden"
            name="token"
            value="{{ $token }}"
        >

        {{-- Reset-password state --}}
        <fieldset id="reset-password-state" class="border-0 p-0">
            <div class="my-2">
                <label for="email-input" class="form-label">
                    Email address
                </label>

                <input
                    class="form-control @error('email') is-invalid @enderror"
                    id="email-input"
                    type="email"
                    name="email"
                    value="{{ old('email', $email) }}"
                    autocomplete="email"
                    maxlength="100"
                    aria-describedby="email-input-error"
                    required
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

            <div class="my-2">
                <label for="new-password-input" class="form-label">
                    New password
                </label>

                <input
                    class="form-control @error('password') is-invalid @enderror"
                    id="new-password-input"
                    type="password"
                    name="password"
                    minlength="8"
                    autocomplete="new-password"
                    aria-describedby="password-error"
                    required
                >

                <div
                    id="password-error"
                    class="d-block invalid-feedback"
                >
                    @error('password')
                        {{ $message }}
                    @enderror
                </div>
            </div>

            <div class="my-2">
                <label for="confirm-password-input" class="form-label">
                    Confirm new password
                </label>

                <input
                    class="form-control @error('password_confirmation') is-invalid @enderror"
                    id="confirm-password-input"
                    type="password"
                    name="password_confirmation"
                    minlength="8"
                    autocomplete="new-password"
                    aria-describedby="password-confirmation-error"
                    required
                >

                <div
                    id="password-confirmation-error"
                    class="d-block invalid-feedback"
                >
                    @error('password_confirmation')
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
            Reset password
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
