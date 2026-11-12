<div id="auth-form" class="position-relative p-2">
    @include('auth.partials.messages')

    <h3 class="primary-text fw-bold mb-2" id="login-form-header">
        {{ __('auth.form.heading') }}
    </h3>

    <p id="login-form-email-text" class="fg-3"></p>
    <div id="login-form-status-text" class="form-text" role="status" aria-live="polite"></div>

    @if ($errors->has('google'))
        <div class="alert alert-danger py-2 px-3 my-2 small" role="alert">
            {{ $errors->first('google') }}
        </div>
    @endif

    <form
        id="login-form"
        method="POST"
        data-check-email-url="{{ route('auth.check-email') }}"
        data-login-url="{{ route('login.store') }}"
        data-register-url="{{ route('register.store') }}"
        data-verify-url="{{ route('verification.code.verify') }}"
        data-resend-url="{{ route('verification.code.resend') }}"
        data-form-state="email"
    >
        @csrf

        {{-- Email state --}}
        <fieldset id="email-state" class="border-0 p-0">
            <div class="my-2">
                <label for="email-input" class="form-label">
                    {{ __('auth.form.email') }}
                </label>
                <input
                    class="form-control"
                    id="email-input"
                    type="email"
                    name="email"
                    placeholder="{{ __('auth.form.email') }}"
                    autocomplete="email"
                    maxlength="100"
                    required
                >
                <div id="email-input-error" class="display-block invalid-feedback"></div>
            </div>
        </fieldset>

        {{-- Login state --}}
        <fieldset id="login-state" class="border-0 p-0" disabled hidden>
            <div class="my-2">
                <label for="password-input" class="form-label">
                    {{ __('auth.form.password') }}
                </label>
                <input
                    class="form-control"
                    id="password-input"
                    type="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >
                <div id="password-error" class="d-block invalid-feedback"></div>
                <div class="mt-2 text-end">
                    <a href="{{ route('password.request') }}">
                        {{ __('auth.form.forgot') }}
                    </a>
                </div>
            </div>
        </fieldset>

        {{-- Registration state --}}
        <fieldset id="register-state" class="border-0 p-0" disabled hidden>
            <div class="my-2">
                <label for="user-input" class="form-label">
                    {{ __('auth.form.username') }}
                </label>
                <input
                    class="form-control"
                    id="user-input"
                    type="text"
                    name="username"
                    maxlength="50"
                    autocomplete="username"
                    required
                >
                <div id="username-error" class="d-block invalid-feedback"></div>
            </div>

            <div class="my-2">
                <label for="new-password-input" class="form-label">
                    {{ __('auth.form.password') }}
                </label>
                <input
                    class="form-control"
                    id="new-password-input"
                    type="password"
                    name="password"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
                <div id="password-error" class="d-block invalid-feedback"></div>
            </div>

            <div class="my-2">
                <label for="confirm-password-input" class="form-label">
                    {{ __('auth.form.confirm_password') }}
                </label>
                <input
                    class="form-control"
                    id="confirm-password-input"
                    type="password"
                    name="password_confirmation"
                    minlength="8"
                    autocomplete="new-password"
                    required
                >
                <div id="password-confirmation-error" class="d-block invalid-feedback"></div>
            </div>
        </fieldset>

        {{-- Email verification state --}}
        <fieldset id="verify-state" class="border-0 p-0" disabled hidden>
            <div class="my-2">
                <label for="code-input" class="form-label">
                    {{ __('auth.form.verification_code') }}
                </label>
                <input
                    class="form-control text-center fs-4"
                    id="code-input"
                    type="text"
                    name="code"
                    inputmode="numeric"
                    pattern="[0-9]{6}"
                    maxlength="6"
                    autocomplete="one-time-code"
                    placeholder="000000"
                    required
                >
                <div id="code-error" class="d-block invalid-feedback"></div>
                <div class="mt-2 text-end">
                    <button type="button" id="resend-code-button" class="btn btn-link p-0">
                        {{ __('auth.form.resend_code') }}
                    </button>
                </div>
            </div>
        </fieldset>

        {{-- Shared controls: deliberately outside all fieldsets --}}
        <div id="login-spinner" class="d-none text-center my-3">
            <div class="spinner-border text-danger spinner-border-sm" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <span class="small ms-2 fg-3" id="spinner-text"></span>
        </div>

        <button
            type="submit"
            id="login-form-primary-button"
            class="btn btn-solid theme-primary w-100 rounded-pill py-2 fw-semibold my-2"
            hidden
        >
            {{ __('auth.form.continue') }}
        </button>

        <button
            type="button"
            id="login-form-secondary-button"
            class="btn btn-link w-100 text-center"
            hidden
        >
            {{ __('auth.form.another_email') }}
        </button>
    </form>

    <div class="d-flex align-items-center gap-2 my-3">
        <hr class="flex-grow-1 m-0">
        <span class="small fg-3 text-uppercase">{{ __('auth.form.or') }}</span>
        <hr class="flex-grow-1 m-0">
    </div>

    <a
        href="{{ route('auth.google.redirect') }}"
        id="google-login-button"
        class="btn btn-outline-secondary w-100 rounded-pill py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 text-decoration-none"
    >
        <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
        </svg>
        <span>{{ __('auth.form.continue_with_google') }}</span>
    </a>
</div>
