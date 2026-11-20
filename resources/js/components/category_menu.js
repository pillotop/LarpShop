// Owns category preview and search scope; the portal owns visibility and positioning.
export class CategoryMenu {
    constructor(menu, trigger, close, imagePreview = null) {
        this.imagePreview = imagePreview;
        this.menu = menu;
        this.trigger = trigger;
        this.form = trigger.closest('form');
        this.close = close;
        this.current = null;

        menu.addEventListener('pointerover', event => this.previewFrom(event.target));
        menu.addEventListener('focusin', event => this.previewFrom(event.target));
        menu.addEventListener('click', event => this.handleClick(event));

        const entries = [...menu.querySelectorAll('[data-category-entry]')];
        const scope = this.form?.querySelector('[data-category-scope]')?.value;
        this.preview(entries.find(entry => entry.dataset.categoryScope === scope) ?? entries[0]);
    }

    previewFrom(target) {
        const entry = target.closest('[data-category-entry]');
        if (entry) this.preview(entry);
    }

    preview(entry) {
        if (!entry || entry === this.current) return;
        this.current?.classList.remove('is-previewed');
        this.current = entry;
        entry.classList.add('is-previewed');

        const name = this.menu.querySelector('[data-category-preview-name]');
        const description = this.menu.querySelector('[data-category-preview-description]');
        const link = this.menu.querySelector('[data-category-preview-link]');
        const actions = this.menu.querySelector('[data-category-preview-actions]');
        if (name) name.textContent = entry.dataset.categoryName;
        if (description) description.textContent = entry.dataset.categoryDescription;
        if (link) link.href = entry.querySelector('[data-category-link]').href;
        if (actions) actions.hidden = false;
        this.imagePreview?.show(JSON.parse(entry.dataset.categoryImages || '[]'));
    }

    destroy() {
        this.imagePreview?.destroy();
    }

    handleClick(event) {
        if (event.target.closest('[data-category-reset]')) {
            this.select(null);
        } else if (event.target.closest('[data-category-preview-select]')) {
            this.select(this.current);
        } else if (event.target.closest('[data-category-select]')) {
            this.select(event.target.closest('[data-category-entry]'));
        }
    }

    select(entry) {
        const scope = this.form?.querySelector('[data-category-scope]');
        const label = this.trigger.querySelector('[data-category-label]');
        if (!scope || !label) return;

        scope.value = entry?.dataset.categoryScope ?? '';
        scope.disabled = !entry;
        label.textContent = entry?.dataset.categoryName ?? this.trigger.dataset.allCategoriesLabel;
        this.trigger.title = label.textContent;
        this.close();
        this.form.querySelector('input[name="q"]')?.focus();
    }
}
