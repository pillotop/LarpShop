<div id="auth-form" class="blur position-relative shadow border rounded-4 p-4">
    @include('auth.partials.messages')

    <h3 class="primary-text" id="login-form-header">
        {{ __('auth.form.heading') }}
    </h3>

    <p id="login-form-email-text" class="fg-3"></p>
    <div id="login-form-status-text" class="form-text" role="status" aria-live="polite"></div>

    <form
        id="login-form"
        method="POST"
        data-check-email-url="{{ route('auth.check-email') }}"
        data-login-url="{{ route('login.store') }}"
        data-register-url="{{ route('register.store') }}"
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
                    autofocus
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

        {{-- Shared controls: deliberately outside all fieldsets --}}
        <button
            type="submit"
            id="login-form-primary-button"
            class="w-100 border-0 rounded-4 p-2 my-2"
            hidden
        >
            {{ __('auth.form.continue') }}
        </button>

        <button
            type="button"
            id="login-form-secondary-button"
            class="btn btn-link"
            hidden
        >
            {{ __('auth.form.another_email') }}
        </button>
    </form>
</div>
