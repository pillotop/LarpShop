class LoginForm {
    static defaults = {
        email: {
            header: 'Sign in or create account',
            loading: () => 'Checking email…',
        },
        login: {
            header: 'Enter your password',
            loading: () => 'Signing in…',
        },
        register: {
            header: 'Create your account',
            loading: () => 'Creating account…',
        }
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
            for (const key of Object.keys(this.fields)) {
                const errorText = this.element.querySelector(
                    `#${key.replaceAll('_', '-')}-error`
                );

                if (!errorText) continue;
                errorText.textContent = '';
            }
            this.outer.statusText.textContent = '';
        }

        handleError(error) {
            this.clearErrors();

            if (error.status === 422 && error.errors) {
                let matchedError = false;
                for (const [key, input] of Object.entries(this.fields)) {
                    const messages = error.errors[key];
                    console.log(messages);

                    if (!messages?.length) continue;

                    const k = `#${key.replaceAll('_', '-')}-error`;
                    console.log(`key name is ${key}`);
                    console.log(`looked for ${k}`);
                    const errorText = this.element.querySelector(
                        `#${key.replaceAll('_', '-')}-error`
                    );
                    if (!errorText) continue;

                    errorText.textContent = messages[0];
                    input.setAttribute('aria-invalid', 'true');
                    matchedError = true;
                }
                if (matchedError) return;
            }
            this.handleDefaultError(error);
        }

        handleDefaultError(error) {
            this.outer.statusText.textContent = error.message;
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

        this.csrfToken = this.form.querySelector('input[name="_token"]').value;

        this.primaryButton = this.container.querySelector('#login-form-primary-button');
        this.secondaryButton = this.container.querySelector('#login-form-secondary-button');
        this.header = this.container.querySelector('#login-form-header');
        this.emailText = this.container.querySelector('#login-form-email-text');
        this.statusText = this.container.querySelector('#login-form-status-text');

        this.#initState();
        this.#setupSignals();
    }

    #initState() {
        const emailState = this.form.querySelector('#email-state');
        const emailInput = emailState.querySelector('#email-input');

        const loginState = this.form.querySelector('#login-state');
        const passwordInput = loginState.querySelector('#password-input');

        const registerState = this.form.querySelector('#register-state');
        const usernameInput = registerState.querySelector('#user-input');
        const newPasswordInput = registerState.querySelector('#new-password-input');
        const confirmPasswordInput = registerState.querySelector('#confirm-password-input');

        // this is glue code (glue*)
        this.emailInput = emailInput;
        this.states = {};

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
            use             : (result) => window.location.assign(result.redirect),
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
            use             : (result) => window.location.assign(result.redirect),
            formatPayload   : () => ({
                email: emailInput.value.trim(),
                username: usernameInput.value.trim(),
                password: newPasswordInput.value,
                password_confirmation: confirmPasswordInput.value,
            }),
        });

        this.stateHistory = [];
        this.stateRevision = 0;
        this.isSubmitting = false;
        const initialState = this.form.dataset.formState || 'email';
        this.#pushState(initialState);
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
        this.secondaryButton.addEventListener('click', () => {
            if (this.stateHistory.length > 0)
                this.#currentState()?.cancel();
        });

        this.form.addEventListener('submit', (event) => {
            void this.#onSubmit(event);
        });
    }

    #currentState() {
        return this.stateHistory.at(-1) ?? null;
    }

    #pushState(name) {
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
        nextState.show({ focus: true });

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
        this.stateHistory.pop();
        this.stateRevision++;

        const previousState = this.#currentState();
        previousState.show({ focus: true });

        this.#syncControls();
    }

    #syncControls() {
        this.primaryButton.disabled = this.isSubmitting;
        this.secondaryButton.disabled = this.isSubmitting;

        const state = this.#currentState();
        if (state) {
            state.element.disabled = this.isSubmitting;
        }
    }

    async #onSubmit(event) {
        event.preventDefault();

        if (this.isSubmitting) return;

        const submittedState = this.#currentState();
        const submittedRevision = this.stateRevision;
        if (!submittedState) return;

        this.isSubmitting = true;
        this.#syncControls();
        this.statusText.textContent = submittedState.loading();

        try {
            const result = await this.#postJson(
                submittedState.submitUrl,
                submittedState.formatPayload(),
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
                result.message || 'The request could not be completed.',
                result.errors ?? {},
                response.status,
            );
        }

        return result;
    }
}

new LoginForm(document.querySelector('#auth-form'));
