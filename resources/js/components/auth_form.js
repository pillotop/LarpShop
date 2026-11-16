import '../app.js';
import { authMessages, authStateDefaults, loadAuthMessages, loadAuthStateDefaults } from './auth_messages.js';

class LoginForm {
    static get defaults() {
        return Object.keys(authStateDefaults).length
            ? authStateDefaults
            : loadAuthStateDefaults();
    }

    static FormState = class {
        static displayProps = ['primaryButton', 'secondaryButton', 'header', 'emailText', 'statusText'];
        static requiredProps = ['name', 'outer', 'element', 'submitUrl', 'fields', 'buttons'];
        static requiredFuncs = ['use', 'formatPayload', 'loading', 'error', 'outerPopState', 'outerPushState', 'outerCurrentState'];

        constructor(args) {
            if (!args.name) throw Error(`Undefined name for login form state`);
            const defaults = LoginForm.defaults[args.name];
            if (!defaults) throw Error(`No defaults defined for login form state '${args.name}'`);

            for (const name of this.constructor.requiredProps) {
                const prop = args[name] ?? defaults[name];
                if (prop === null || prop === undefined)
                    throw Error(`Property ${name} must be defined for login form state`);

                this[name] = prop;
            }

            if (!this.fields || typeof this.fields !== 'object') {
                throw Error(`'fields' must be an object for state '${this.name}'`);
            }

            if (!Array.isArray(this.buttons)) {
                throw Error(`'buttons' must be an array for state '${this.name}`);
            }


            this.displayProps = {};
            // optional hook: return true from it when the error was fully handled
            this.onError = typeof args.onError === 'function' ? args.onError : null;
            for (const name of this.constructor.displayProps) {
                const prop = args[name] ?? defaults[name];
                if (prop === null || prop === undefined) continue;

                this.displayProps[name] = prop;
                if (typeof prop === 'string')
                    this.outer[name].textContent = prop;
            }

            for (const name of this.constructor.requiredFuncs) {
                let func = args[name];
                if (func == null) {
                    if (name === 'error') {
                        func = error => this.handleError(error);
                    } else {
                        func = defaults[name];
                    }
                }

                if (typeof func !== 'function') {
                    throw Error(
                        `Property '${name}' must be a function for login form state '${this.name}'`
                    );
                }
                this[name] = func;
            }
        }

        clearErrors() {
            for (const [key, input] of Object.entries(this.fields)) {
                const errorText = this.element.querySelector(
                    `#${key.replaceAll('_', '-')}-error`
                );

                if (errorText) errorText.textContent = '';

                input.removeAttribute('aria-invalid');
                input.classList.remove('is-invalid');
            }

            this.outer.statusText.textContent = '';
        }

        handleError(error) {
            this.clearErrors();

            if (this.onError?.(error) === true) return;

            if ((error.status === 401 || error.status === 422) && error.errors) {
                let matchedError = false;
                for (const [key, input] of Object.entries(this.fields)) {
                    const messages = error.errors[key];

                    if (!messages?.length) continue;

                    const errorText = this.element.querySelector(
                        `#${key.replaceAll('_', '-')}-error`
                    );

                    if (!errorText) continue;

                    errorText.textContent = messages[0];
                    input.setAttribute('aria-invalid', 'true');
                    input.classList.add('is-invalid');
                    matchedError = true;
                }
                if (matchedError) return;
            }
            this.handleDefaultError(error);
        }

        handleDefaultError(error) {
            this.outer.statusText.textContent =
                error instanceof TypeError
                    ? authMessages.network_error
                    : error.message || authMessages.network_error;
        }

        cancel() {
            if (this.outer.isSubmitting) return;
            Object.values(this.fields).forEach(field => field.value = '');
            this.clearErrors();
            this.outerPopState(this);
        }

        hide() {
            this.element.disabled = true;
            this.element.hidden = true;

            this.buttons.forEach(button => button.hidden = true);
        }

        show({ focus = false } = {}) {
            this.element.hidden = false;
            this.element.disabled = false;

            this.buttons.forEach(button => button.hidden = false);

            // currently stateful text content display isnt supported yet
            for (const [key, value] of Object.entries(this.displayProps)) {
                if (typeof value === 'string') {
                    this.outer[key].textContent = value;
                }
            }

            if (focus) {
                Object.values(this.fields)[0]?.focus();
            }
        }
    }

    static FormRequestError = class extends Error {
        constructor(message, errors = {}, status = null) {
            super(message);
            this.name = 'FormRequestError';
            this.errors = errors;
            this.status = status;
        }
    };

    constructor(element) {
        this.container = element;
        this.form = this.container.querySelector('#login-form');

        this.loginUrl = this.form.dataset.loginUrl;
        this.registerUrl = this.form.dataset.registerUrl;
        this.checkEmailUrl = this.form.dataset.checkEmailUrl;
        this.verifyUrl = this.form.dataset.verifyUrl;
        this.resendUrl = this.form.dataset.resendUrl;

        this.csrfToken = this.form.querySelector('input[name="_token"]').value;

        this.primaryButton = this.container.querySelector('#login-form-primary-button');
        this.secondaryButton = this.container.querySelector('#login-form-secondary-button');
        this.spinner = this.container.querySelector('#login-spinner') || this.container.querySelector('#forgot-spinner');
        this.spinnerText = this.container.querySelector('#spinner-text') || this.container.querySelector('#forgot-spinner-text');
        this.header = this.container.querySelector('#login-form-header');
        this.emailText = this.container.querySelector('#login-form-email-text');
        this.statusText = this.container.querySelector('#login-form-status-text');

        this.#initState();
        this.#setupSignals();
    }

    #initState() {
        this.states = {};
        this.stateHistory = [];
        this.stateRevision = 0;
        this.isSubmitting = false;

        const initialState = this.form.dataset.formState || 'email';

        switch (initialState) {
        case "email":
            this.#initAuthenticationStates();
            break;
        case "forgot-password":
            this.#initForgotPasswordStates();
            break;
        case "reset-password":
            this.#initResetPasswordStates();
            break;
        default:
            throw Error(`Unknown initial form state: ${initialState}`)
        }

        const isInsideDrawer = Boolean(this.container.closest('#auth-panel'));
        this.#pushState(initialState, { focus: !isInsideDrawer });
    }

    #initAuthenticationStates() {
        const emailState = this.form.querySelector('#email-state');
        const emailInput = emailState.querySelector('#email-input');

        const loginState = this.form.querySelector('#login-state');
        const passwordInput = loginState.querySelector('#password-input');

        const registerState = this.form.querySelector('#register-state');
        const usernameInput = registerState.querySelector('#user-input');
        const newPasswordInput = registerState.querySelector('#new-password-input');
        const confirmPasswordInput = registerState.querySelector('#confirm-password-input');

        const verifyState = this.form.querySelector('#verify-state');
        const codeInput = verifyState.querySelector('#code-input');
        const resendButton = verifyState.querySelector('#resend-code-button');

        // Shows the code entry step with the server's explanation of why it is needed.
        const showVerification = (message) => {
            this.#pushState('verify');
            this.emailText.textContent = message ?? '';
        };

        const finishSignIn = (result) => {
            if (this.container.closest('#auth-panel')) {
                window.location.reload();
                return;
            }
            window.location.assign(result.redirect);
        };

        // this is glue code (glue*)
        this.emailInput = emailInput;

        this.#defineState('email', {
            element         : emailState,
            submitUrl       : this.checkEmailUrl,
            buttons         : [ this.primaryButton ],
            fields          : { email: emailInput },
            use             : (result) => this.#pushState(result.exists ? 'login' : 'register'),
            formatPayload   : () => ({
                email: emailInput.value.trim(),
            }),
        });

        this.#defineState('login', {
            element         : loginState,
            submitUrl       : this.loginUrl,
            buttons         : [ this.primaryButton, this.secondaryButton ],
            fields          : { password: passwordInput }, // this missing `this.emailInput` is the result of (glue*)
            use             : (result) => finishSignIn(result),
            // 403 means the password was right but the email is not verified yet
            onError         : (error) => {
                if (error.status !== 403) return false;
                showVerification(error.message);
                return true;
            },
            formatPayload   : () => ({
                email: emailInput.value.trim(),
                password: passwordInput.value,
            }),
        });

        this.#defineState('register', {
            element         : registerState,
            submitUrl       : this.registerUrl,
            buttons         : [ this.primaryButton, this.secondaryButton ],
            fields          : { username: usernameInput, password: newPasswordInput, password_confirmation: confirmPasswordInput }, // this missing `this.emailInput` is the result of (glue*)
            use             : (result) => {
                if (result.verify) {
                    showVerification(result.message);
                    return;
                }
                finishSignIn(result);
            },
            formatPayload   : () => ({
                email: emailInput.value.trim(),
                username: usernameInput.value.trim(),
                password: newPasswordInput.value,
                password_confirmation: confirmPasswordInput.value,
            }),
        });

        this.#defineState('verify', {
            element         : verifyState,
            submitUrl       : this.verifyUrl,
            buttons         : [ this.primaryButton, this.secondaryButton ],
            fields          : { code: codeInput },
            use             : (result) => finishSignIn(result),
            formatPayload   : () => ({
                email: emailInput.value.trim(),
                code: codeInput.value.trim(),
            }),
        });

        resendButton.addEventListener('click', async () => {
            if (this.isSubmitting) return;
            resendButton.disabled = true;
            try {
                const result = await this.#postJson(this.resendUrl, { email: emailInput.value.trim() });
                this.statusText.textContent = result.message ?? '';
            } catch (error) {
                this.statusText.textContent = error.message || authMessages.network_error;
            } finally {
                resendButton.disabled = false;
            }
        });
    }

    #initForgotPasswordStates() {
        const emailState = this.form.querySelector("#email-state");
        const emailInput = emailState.querySelector("#email-input");

        this.#defineState('forgot-password', {
            element: emailState,
            submitUrl: this.form.dataset.forgotPasswordUrl,
            buttons: [ this.primaryButton ],
            fields: { email: emailInput },
            formatPayload: () => ({ email: emailInput.value.trim() }),
            use: (result) => {
                this.statusText.textContent =
                    result.message ||
                    authMessages.reset_link_sent
            }
        });
    }

    #initResetPasswordStates() {
        const resetState = this.form.querySelector("#reset-password-state");
        const tokenInput = this.form.querySelector('input[name="token"]');
        const emailInput = resetState.querySelector('#email-input');
        const passwordInput = resetState.querySelector('#new-password-input');
        const confirmInput = resetState.querySelector('#confirm-password-input');

        this.#defineState('reset-password', {
            element: resetState,
            submitUrl: this.form.dataset.resetPasswordUrl,
            buttons: [this.primaryButton],
            fields: {
                email: emailInput,
                password: passwordInput,
                password_confirmation: confirmInput,
            },
            formatPayload: () => ({
                token: tokenInput.value,
                email: emailInput.value.trim(),
                password: passwordInput.value,
                password_confirmation: confirmInput.value,
            }),
            use: (result) => {
                window.location.assign(result.redirect);
            },
        });
    }

    // must be called inside #initState(), just for the sake of the function
    // theres no other meanings besides it
    #defineState(name, definition) {
        if (this.states[name] === null || this.states[name] === undefined) {
            definition.name = name;
            definition.outer = this;
            definition.outerPopState        = (state) => this.#popState(state);
            definition.outerPushState       = (name)  => this.#pushState(name);
            definition.outerCurrentState    = ()      => this.#currentState();
            this.states[name] = new LoginForm.FormState(definition);
        } else {
            throw Error(`The login form state '${name}' has already been defined`);
        }
    }

    #setupSignals() {
        if (this.secondaryButton.tagName === 'BUTTON') {
            this.secondaryButton.addEventListener('click', () => {
                if (this.stateHistory.length > 1)
                    this.#currentState()?.cancel();
            });
        }

        this.form.addEventListener('submit', (event) => {
            event.preventDefault();
            void this.#onSubmit(event);
        });
    }

    #currentState() {
        return this.stateHistory.at(-1) ?? null;
    }

    #pushState(name, { focus = true } = {}) {
        const currentState = this.#currentState();
        const nextState = this.states[name];

        if (!nextState) {
            throw Error(`Unsupported login form state: ${name}`);
        }

        if (currentState === nextState) return;

        currentState?.hide();
        this.stateHistory.push(nextState);
        this.stateRevision++;

        this.statusText.textContent = '';
        nextState.show({ focus });

        this.#syncControls();
    }

    #popState(state) {
        const currentState = this.#currentState();
        if (!currentState || currentState !== state) {
            throw Error('Cannot pop a state that is not active');
        }

        if (this.stateHistory.length <= 1) return;
        currentState.hide();
        this.statusText.textContent = '';
        this.emailText.textContent = '';
        this.stateHistory.pop();
        this.stateRevision++;

        const previousState = this.#currentState();
        previousState.show({ focus: true });

        this.#syncControls();
    }

    #syncControls() {
        this.primaryButton.disabled = this.isSubmitting;
        this.secondaryButton.disabled = this.isSubmitting;

        if (this.spinner) {
            if (this.isSubmitting) {
                this.spinner.classList.remove('d-none');
                if (this.spinnerText) {
                    this.spinnerText.textContent = this.#currentState()?.loading() || '';
                }
            } else {
                this.spinner.classList.add('d-none');
            }
        }

        const state = this.#currentState();
        if (state) {
            state.element.disabled = this.isSubmitting;
        }
    }

    async #onSubmit(event) {
        event.preventDefault();

        if (this.isSubmitting) return;

        const submittedState = this.#currentState();
        if (!submittedState) return;

        const payload = submittedState.formatPayload();
        const canOptimisticallyVerify =
            submittedState.name === 'register' &&
            Boolean(payload.username) &&
            typeof payload.password === 'string' &&
            payload.password.length >= 8 &&
            payload.password === payload.password_confirmation;

        if (canOptimisticallyVerify) {
            this.#pushState('verify');
            this.emailText.textContent = submittedState.loading();
            const verifyRevision = this.stateRevision;

            try {
                const result = await this.#postJson(submittedState.submitUrl, payload);
                if (this.#currentState()?.name === 'verify' && this.stateRevision === verifyRevision) {
                    if (result.verify) {
                        this.emailText.textContent = result.message ?? '';
                    } else if (result.redirect) {
                        window.location.reload();
                    }
                }
            } catch (error) {
                if (this.#currentState()?.name === 'verify' && this.stateRevision === verifyRevision) {
                    this.#popState(this.states.verify);
                    submittedState.handleError(error);
                }
            }
            return;
        }

        const submittedRevision = this.stateRevision;
        this.isSubmitting = true;
        this.#syncControls();
        this.statusText.textContent = submittedState.loading();

        if (submittedState.name === 'forgot-password' && payload.email?.includes('@')) {
            this.statusText.textContent = authMessages.reset_link_sent;
        }

        try {
            const result = await this.#postJson(
                submittedState.submitUrl,
                payload,
            );

            if (this.#currentState() === submittedState &&
                submittedRevision === this.stateRevision) {
                submittedState.use(result);
            }
        } catch (error) {
            if (this.#currentState() === submittedState &&
                submittedRevision === this.stateRevision) {
                submittedState.handleError(error);
            }
        } finally {
            this.isSubmitting = false;
            this.#syncControls();
        }
    }

    async #postJson(url, data) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
            },
            body: JSON.stringify(data),
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new LoginForm.FormRequestError(
                result.message || authMessages.request_error,
                result.errors ?? {},
                response.status,
            );
        }

        return result;
    }
}

function mountAuthForm() {
    const element = document.querySelector('#auth-form');
    if (!element || element.dataset.authFormMounted === 'true') {
        return;
    }
    element.dataset.authFormMounted = 'true';
    Object.assign(authMessages, loadAuthMessages());
    new LoginForm(element);

    const authPanel = document.querySelector('#auth-panel');
    if (authPanel) {
        authPanel.addEventListener('shown.bs.drawer', () => {
            element.querySelector('fieldset:not([hidden]) input')?.focus();
        });
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountAuthForm, { once: true });
} else {
    mountAuthForm();
}

