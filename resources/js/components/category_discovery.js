// Category selection owns request cancellation; cards and loading markup stay server-rendered.
export class CategoryDiscovery {
    constructor(element, {
        fetchPage = fetch,
        page = document,
        initialize = () => {},
        Observer = globalThis.IntersectionObserver,
    } = {}) {
        this.element = element;
        this.fetchPage = (...args) => fetchPage(...args);
        this.page = page;
        this.initialize = initialize;
        this.Observer = Observer;
        this.loading = element.querySelector('[data-loading-state]');
        this.content = element.querySelector('[data-discovery-content]');
        this.region = element.querySelector('[data-discovery-region]');
        this.error = element.querySelector('[data-discovery-error]');
        this.retry = element.querySelector('[data-discovery-retry]');
        this.expand = element.querySelector('[data-discovery-expand]');
        this.choices = [...element.querySelectorAll('[data-discovery-category]')];
        this.category = '';
        this.expanded = false;
        this.requestId = 0;
        this.state = 'idle';

        this.choices.forEach(choice => choice.addEventListener('click', event => {
            if (event.button || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            if (this.category === choice.dataset.discoveryCategory && this.state !== 'error') return;
            this.category = choice.dataset.discoveryCategory;
            this.expanded = false;
            this.updateSelection();
            void this.load();
        }));
        this.expand.addEventListener('click', () => {
            this.expanded = !this.expanded;
            void this.load();
        });
        this.retry.addEventListener('click', () => { void this.load(); });
    }

    updateSelection() {
        this.choices.forEach(choice => {
            if (choice.dataset.discoveryCategory === this.category) choice.setAttribute('aria-current', 'true');
            else choice.removeAttribute('aria-current');
        });
    }

    start() {
        if (!this.Observer) {
            void this.load();
            return;
        }
        this.observer = new this.Observer(entries => {
            if (!entries.some(entry => entry.isIntersecting)) return;
            this.observer.disconnect();
            if (this.state === 'idle') void this.load();
        }, { rootMargin: '300px 0px' });
        this.observer.observe(this.element);
    }

    async load() {
        this.observer?.disconnect();
        this.controller?.abort();
        const requestId = ++this.requestId;
        const controller = new AbortController();
        this.controller = controller;
        const timeout = setTimeout(() => controller.abort(), 15000);
        const restoreFocus = this.page.activeElement === this.retry || this.page.activeElement === this.expand;
        const expanded = this.expanded;
        const params = new URLSearchParams({ expanded: expanded ? '1' : '0' });
        if (this.category) params.set('category', this.category);
        this.state = 'loading';
        this.loading.hidden = false;
        this.content.hidden = true;
        this.error.hidden = true;
        this.expand.disabled = true;
        this.retry.disabled = true;
        this.region.setAttribute('aria-busy', 'true');

        try {
            const response = await this.fetchPage(`${this.element.dataset.source}?${params}`, {
                credentials: 'same-origin',
                headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!response.ok || response.redirected) throw new Error('Unable to load products.');
            const html = await response.text();
            if (requestId !== this.requestId) return false;
            const template = this.page.createElement('template');
            template.innerHTML = html;
            const result = template.content.querySelector('[data-discovery-result]');
            if (!result) throw new Error('Invalid product response.');
            this.content.replaceChildren(template.content);
            this.content.hidden = false;
            this.expand.hidden = !expanded && result.dataset.hasMore !== 'true';
            this.expand.textContent = expanded ? this.expand.dataset.lessLabel : this.expand.dataset.moreLabel;
            this.expand.setAttribute('aria-expanded', String(expanded));
            this.initialize(this.content);
            this.state = 'loaded';
            if (!this.page.defaultView?.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
                this.content.animate?.([{ opacity: 0 }, { opacity: 1 }], { duration: 220, easing: 'ease-out' });
            }
            if (restoreFocus) result.focus();
            return true;
        } catch {
            if (requestId !== this.requestId) return false;
            this.state = 'error';
            this.error.hidden = false;
            this.expand.hidden = true;
            return false;
        } finally {
            clearTimeout(timeout);
            if (requestId === this.requestId) {
                this.loading.hidden = true;
                this.expand.disabled = false;
                this.retry.disabled = false;
                this.region.removeAttribute('aria-busy');
            }
        }
    }
}
