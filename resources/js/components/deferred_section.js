// Request lifecycle only; the server owns product membership and Blade rendering.
export class DeferredSection {
    constructor(element, {
        fetchPage = fetch,
        page = document,
        initialize = () => {},
        selectContent = fragment => fragment,
        Observer = globalThis.IntersectionObserver,
    } = {}) {
        this.element = element;
        this.fetchPage = (...args) => fetchPage(...args);
        this.page = page;
        this.initialize = initialize;
        this.selectContent = selectContent;
        this.Observer = Observer;
        this.loading = element.querySelector('[data-loading-state]');
        this.content = element.querySelector('[data-deferred-content]');
        this.error = element.querySelector('[data-deferred-error]');
        this.retry = element.querySelector('[data-deferred-retry]');
        this.state = 'idle';
        this.retry.addEventListener('click', () => { void this.load(); });
    }

    start() {
        if (!this.Observer) {
            void this.load();
            return;
        }
        this.observer = new this.Observer(entries => {
            if (!entries.some(entry => entry.isIntersecting)) return;
            this.observer.disconnect();
            void this.load();
        }, { rootMargin: '300px 0px' });
        this.observer.observe(this.element);
    }

    async load() {
        if (this.state === 'loading' || this.state === 'loaded' || this.state === 'empty') return false;
        const restoreFocus = this.page.activeElement === this.retry;
        this.state = 'loading';
        this.loading.hidden = false;
        this.error.hidden = true;
        this.retry.disabled = true;
        this.content.setAttribute('aria-busy', 'true');
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 15000);

        try {
            const response = await this.fetchPage(this.element.dataset.source, {
                headers: { Accept: 'text/html' },
                credentials: 'same-origin',
                signal: controller.signal,
            });
            if (!response.ok || response.redirected) throw new Error('Unable to load section.');
            if (response.status === 204) {
                this.state = 'empty';
                this.element.hidden = true;
                return true;
            }

            const template = this.page.createElement('template');
            template.innerHTML = await response.text();
            if (!template.content.querySelector('[data-deferred-fragment]')) {
                throw new Error('Invalid section response.');
            }
            const selected = this.selectContent(template.content, this.element);
            if (!selected) {
                this.state = 'empty';
                this.element.hidden = true;
                return true;
            }
            this.content.replaceChildren(selected);
            this.loading.hidden = true;
            this.initialize(this.content);
            this.state = 'loaded';
            if (restoreFocus) this.content.querySelector('[data-deferred-focus]')?.focus();
            return true;
        } catch {
            this.state = 'error';
            this.error.hidden = false;
            return false;
        } finally {
            clearTimeout(timeout);
            this.loading.hidden = true;
            this.retry.disabled = false;
            this.content.removeAttribute('aria-busy');
        }
    }
}
