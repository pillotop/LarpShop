import { sendOnboarding, stepForError } from './vendor_onboarding_transport.js';

export function vendorOnboarding(config, environment = {}) {
    const send = environment.send ?? sendOnboarding;
    const navigate = environment.navigate ?? (url => window.location.assign(url));
    const replaceUrl = environment.replaceUrl ?? (url => window.history.replaceState(null, '', url));
    const fields = Object.keys(config.form).filter(key => key !== 'current_step');

    return {
        form: Object.fromEntries(Object.entries(config.form).map(([key, value]) => [key, value ?? ''])),
        applicationId: config.applicationId,
        stepIndex: config.editable ? Math.max(0, config.steps.indexOf(config.form.current_step)) : 3,
        saving: '', errors: {}, notice: '', dirty: false, uncertainCreate: false,
        confirmation: false, countrySearch: '', selectedAddress: '', image: null,
        photoUrl: config.imageUrl, removeImage: false, blobUrl: null,
        stepLabels: config.stepLabels,
        get busy() { return this.saving !== ''; },
        get hasPhoto() { return this.photoUrl !== config.defaultImageUrl; },
        get currentStep() { return config.steps[this.stepIndex]; },
        get errorMessages() { return Object.values(this.errors).flat(); },
        get filteredCountries() {
            const search = this.countrySearch.trim().toLocaleLowerCase();
            return Object.entries(config.countries).filter(([code, label]) =>
                `${code} ${label}`.toLocaleLowerCase().includes(search));
        },
        endpoint(name) { return config.urls[name].replace('__id__', this.applicationId); },
        fieldError(name) {
            const key = Object.keys(this.errors).find(key => key === name || key.startsWith(`${name}.`));
            return key ? this.errors[key][0] : '';
        },
        displayValue(name) {
            const value = this.form[name];
            if (name === 'destination_countries') {
                return value.map(code => config.countries[code] ?? code).join(', ') || config.messages.empty;
            }
            if (name.endsWith('_country_code')) return config.countries[value] ?? config.messages.empty;
            return config.choiceLabels[name]?.[value] ?? (value || config.messages.empty);
        },
        markDirty(event) {
            if (!event.target.hasAttribute('data-ui-only')) this.dirty = true;
        },
        warnBeforeLeaving(event) {
            if (this.dirty) { event.preventDefault(); event.returnValue = ''; }
        },
        focusStep() {
            this.$nextTick(() => this.$refs.stepHeading?.focus());
        },
        backTo(index) {
            if (this.busy || index < 0 || index >= this.stepIndex) return;
            this.stepIndex = index;
            this.notice = '';
            this.focusStep();
        },
        useAddress() {
            const address = config.addresses[this.selectedAddress];
            if (!address) return;
            for (const [key, value] of Object.entries(address)) this.form[`pickup_${key}`] = value ?? '';
            this.dirty = true;
        },
        chooseImage(event) {
            const file = event.target.files?.[0];
            if (!file) return;
            this.releasePreview();
            this.image = file;
            this.removeImage = false;
            this.blobUrl = URL.createObjectURL(file);
            this.photoUrl = this.blobUrl;
            this.dirty = true;
        },
        removePhoto() {
            this.releasePreview();
            this.image = null;
            this.removeImage = true;
            this.photoUrl = config.defaultImageUrl;
            if (this.$refs.logoInput) this.$refs.logoInput.value = '';
            this.dirty = true;
        },
        releasePreview() {
            if (this.blobUrl) URL.revokeObjectURL(this.blobUrl);
            this.blobUrl = null;
        },
        destroy() { this.releasePreview(); },
        async switchLocale(event) {
            if (this.busy || this.uncertainCreate) return;
            // Locale changes reload the page; persist edits before posting the existing locale form.
            if (config.editable && this.dirty && !await this.save('locale')) {
                // Restore the active locale so the same choice can be retried after fixing errors.
                event.target.reset();
                return;
            }
            this.dirty = false;
            event.target.submit();
        },
        payload(step, validate) {
            const body = new FormData();
            for (const key of fields) {
                if (key === 'destination_countries') continue;
                body.append(key, this.form[key] ?? '');
            }
            body.append('current_step', step);
            const selected = this.form.delivery_mode === 'selected_countries' ? this.form.destination_countries : [];
            selected.forEach(code => body.append('destination_countries[]', code));
            // An empty selected-country list needs an explicit value for validation/clearing.
            if (selected.length === 0) body.append('destination_countries', '[]');
            if (validate) body.append('validate_step', this.currentStep);
            if (this.applicationId !== null) body.append('_method', 'PATCH');
            if (this.image) body.append('brand_image', this.image);
            if (this.removeImage) body.append('remove_brand_image', '1');
            return body;
        },
        acceptSaved(application) {
            this.applicationId = application.vendor_application_id;
            for (const key of fields) {
                if (key !== 'destination_countries') this.form[key] = application[key] ?? '';
            }
            this.form.destination_countries = application.destinations.map(item => item.country_code);
            this.form.current_step = application.current_step;
            this.releasePreview();
            this.photoUrl = application.profile_image_url;
            this.image = null;
            this.removeImage = false;
            if (this.$refs.logoInput) this.$refs.logoInput.value = '';
            this.dirty = false;
            replaceUrl(this.endpoint('show'));
        },
        handleError(error, creating = false) {
            this.errors = error.errors ?? {};
            if (creating && error.uncertain) this.uncertainCreate = true;
            this.notice = this.uncertainCreate ? config.messages.uncertain
                : [401, 403, 419].includes(error.status) ? config.messages.session
                : this.errorMessages.length ? config.messages.validation : config.messages.failed;
            const field = Object.keys(this.errors)[0];
            const step = stepForError(field ?? '');
            if (step) this.stepIndex = config.steps.indexOf(step);
            this.$nextTick(() => {
                const target = field && this.$root.querySelector(`#onboarding-${field.split('.')[0]}`);
                (target ?? this.$refs.feedback)?.focus();
            });
        },
        async save(action) {
            if (this.busy || this.uncertainCreate || !config.editable) return false;
            this.saving = action;
            this.errors = {};
            this.notice = '';
            const creating = this.applicationId === null;
            try {
                const next = action === 'next' ? this.stepIndex + 1 : this.stepIndex;
                const body = this.payload(config.steps[next], action === 'next');
                const application = await send(creating ? config.urls.store : this.endpoint('update'), body,
                    this.$root.querySelector('[name="_token"]').value);
                this.acceptSaved(application);
                if (action === 'next') { this.stepIndex = next; this.focusStep(); }
                if (action === 'exit') navigate(`${config.urls.profile}#pane-vendor`);
                return true;
            } catch (error) {
                this.handleError(error, creating);
                return false;
            } finally { this.saving = ''; }
        },
        async submit() {
            if (this.busy || this.uncertainCreate || !config.editable) return;
            if (!this.confirmation) {
                this.errors = { confirmation: [config.messages.confirmation] };
                this.notice = config.messages.validation;
                this.$nextTick(() => this.$refs.confirmation?.focus());
                return;
            }
            if (!await this.save('submit')) return;
            this.saving = 'submit';
            try {
                const body = new FormData();
                body.append('confirmation', '1');
                const application = await send(this.endpoint('submit'), body,
                    this.$root.querySelector('[name="_token"]').value);
                this.dirty = false;
                navigate(`${config.urls.profile}#pane-vendor-${application.vendor_id}`);
            } catch (error) { this.handleError(error); }
            finally { this.saving = ''; }
        },
    };
}
