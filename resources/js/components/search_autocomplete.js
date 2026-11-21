import { autoUpdate, computePosition, flip, shift, size } from '@floating-ui/dom';

class SearchAutocomplete {
    constructor(element) {
        this.element = element;
        this.input = element.querySelector('input[name="q"]');
        this.form = this.input.form;
        this.scope = this.form.querySelector('[data-category-scope]');
        this.panel = element.querySelector('[data-search-panel]');
        this.results = this.panel.querySelector('[data-search-results]');
        this.loading = this.panel.querySelector('[data-search-loading]');
        this.error = this.panel.querySelector('[data-search-error]');
        this.empty = this.panel.querySelector('[data-search-empty]');
        this.activeIndex = -1;
        this.revision = 0;
        this.options = [];

        // Escape sticky headers and keep the popup anchored during scrolling/resizing.
        document.body.appendChild(this.panel);
        this.bindEvents();
    }

    bindEvents() {
        this.input.addEventListener('input', event => {
            if (!event.isComposing) this.schedule();
        });
        this.input.addEventListener('compositionend', () => this.schedule());
        this.input.addEventListener('focus', () => this.schedule());
        this.input.addEventListener('keydown', event => this.navigate(event));
        this.scope?.addEventListener('change', () => this.schedule());
        this.form.addEventListener('category-menu-open', () => this.close());
        this.form.addEventListener('submit', () => this.close());

        document.addEventListener('pointerdown', event => {
            if (!this.element.contains(event.target) && !this.panel.contains(event.target)) this.close();
        });
        document.addEventListener('focusin', event => {
            if (!this.element.contains(event.target) && !this.panel.contains(event.target)) this.close();
        });
    }

    schedule() {
        this.close();
        const keyword = this.input.value.trim();
        if ([...keyword].length < 2) return;

        this.timer = setTimeout(() => this.fetchSuggestions(keyword), 250);
    }

    async fetchSuggestions(keyword) {
        const revision = ++this.revision;
        this.request = new AbortController();
        const url = new URL(this.element.dataset.suggestionsUrl, window.location.origin);
        url.searchParams.set('q', keyword);
        if (this.scope && !this.scope.disabled) url.searchParams.set('category', this.scope.value);

        this.results.replaceChildren();
        this.options = [];
        this.loading.hidden = false;
        this.error.hidden = true;
        this.empty.hidden = true;
        this.results.setAttribute('aria-busy', 'true');
        this.open();

        try {
            const response = await fetch(url, {
                signal: this.request.signal,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error('Suggestions could not be loaded.');
            const data = await response.json();
            if (revision !== this.revision) return;

            this.results.innerHTML = data.html;
            this.empty.hidden = data.count !== 0;
            this.options = [...this.results.querySelectorAll('[role="option"]')];
            this.options.forEach((option, index) => {
                option.id = `${this.results.id}-option-${index}`;
                option.addEventListener('pointermove', () => this.select(index));
            });
            this.position();
        } catch (error) {
            if (revision === this.revision && error.name !== 'AbortError') this.error.hidden = false;
        } finally {
            if (revision === this.revision) {
                this.loading.hidden = true;
                this.results.setAttribute('aria-busy', 'false');
            }
        }
    }

    open() {
        this.form.dispatchEvent(new CustomEvent('search-suggestions-open', { bubbles: true }));
        this.panel.hidden = false;
        this.panel.style.visibility = 'hidden';
        this.input.setAttribute('aria-expanded', 'true');
        this.stopPositioning = autoUpdate(this.form, this.panel, () => this.position());
    }

    async position() {
        const { x, y } = await computePosition(this.form, this.panel, {
            strategy: 'fixed',
            placement: 'bottom-start',
            middleware: [
                flip({ padding: 8 }),
                shift({ padding: 8 }),
                size({
                    padding: 8,
                    apply: ({ availableHeight, availableWidth, rects }) => {
                        this.panel.style.width = `${Math.min(rects.reference.width, availableWidth)}px`;
                        this.panel.style.maxHeight = `${Math.max(0, Math.min(480, availableHeight))}px`;
                    },
                }),
            ],
        });
        if (this.panel.hidden) return;
        Object.assign(this.panel.style, { left: `${x}px`, top: `${y}px`, visibility: 'visible' });
    }

    select(index) {
        this.activeIndex = index;
        this.options.forEach((option, current) => {
            option.setAttribute('aria-selected', String(current === index));
        });
        const option = this.options[index];
        if (option) this.input.setAttribute('aria-activedescendant', option.id);
        else this.input.removeAttribute('aria-activedescendant');
    }

    navigate(event) {
        if (event.isComposing) return;
        if (event.key === 'Escape' || event.key === 'Tab') {
            if (event.key === 'Escape' && !this.panel.hidden) event.preventDefault();
            this.close();
        } else if (!this.panel.hidden && ['ArrowDown', 'ArrowUp'].includes(event.key) && this.options.length) {
            event.preventDefault();
            const direction = event.key === 'ArrowDown' ? 1 : -1;
            const next = this.activeIndex < 0
                ? (direction === 1 ? 0 : this.options.length - 1)
                : (this.activeIndex + direction + this.options.length) % this.options.length;
            this.select(next);
            this.options[next].scrollIntoView({ block: 'nearest' });
        } else if (event.key === 'Enter' && this.activeIndex >= 0 && !this.panel.hidden) {
            event.preventDefault();
            this.options[this.activeIndex].click();
        }
    }

    close() {
        clearTimeout(this.timer);
        this.request?.abort();
        this.revision++;
        this.stopPositioning?.();
        this.stopPositioning = null;
        this.panel.hidden = true;
        this.input.setAttribute('aria-expanded', 'false');
        this.select(-1);
    }
}

document.querySelectorAll('[data-search-autocomplete]').forEach(element => new SearchAutocomplete(element));
