@props([
    'token',
    'email' => '',
])

<div id="auth-form" class="blur position-relative shadow border rounded-4 p-4">
    @include('auth.partials.messages')

    <h3 class="primary-text" id="login-form-header">
        {{ __('auth.recovery.reset_your_password') }}
    </h3>

    <p id="login-form-email-text" class="fg-3">
        {{ __('auth.recovery.enter_a_new_password_for_your_account') }}
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
                    {{ __('auth.recovery.email_address') }}
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
                    {{ __('auth.recovery.new_password') }}
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
                    {{ __('auth.recovery.confirm_new_password') }}
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
            class="w-100 border-0 rounded-4 p-2 my-2"
        >
            {{ __('auth.recovery.reset_password') }}
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
