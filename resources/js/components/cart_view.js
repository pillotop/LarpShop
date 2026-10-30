(() => {
    const trigger = document.querySelector('#product-cart[data-cart-show-url]');
    const initial = document.getElementById('cart-initial-state');
    const template = document.getElementById('cart-section');
    if (!trigger || !initial || !template) return;

    const store = new window.CartStore({
        showUrl: trigger.dataset.cartShowUrl,
        addUrl: trigger.dataset.cartAddUrl,
        setUrl: trigger.dataset.cartSetUrl,
        csrfToken: document.querySelector('meta[name="csrf-token"]').content,
        initial: { cart: JSON.parse(initial.textContent), html: template.innerHTML },
    });
    window.larpCart = store;
    const panel = document.getElementById('side-panel');
    const page = document.querySelector('[data-cart-page]');
    const errorLabel = document.querySelector('[data-cart-error]');
    const amount = new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const presentation = { field: 'added', direction: 'asc', expanded: new Set() };
    const collator = new Intl.Collator(undefined, { numeric: true, sensitivity: 'base' });
    const sortFields = new Set(['added', 'name', 'price', 'quantity', 'subtotal', 'stock']);

    function applyPresentation(root) {
        const list = root.querySelector('[data-cart-items]');
        const select = root.querySelector('[data-cart-sort]');
        if (select) select.value = presentation.field;
        root.querySelectorAll('[data-cart-direction]').forEach(button => {
            const active = button.dataset.cartDirection === presentation.direction;
            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', String(active));
        });
        if (list) {
            const key = `sort${presentation.field[0].toUpperCase()}${presentation.field.slice(1)}`;
            const multiplier = presentation.direction === 'asc' ? 1 : -1;
            const items = Array.from(list.children);
            items.sort((left, right) => {
                const a = left.matches('[data-cart-item]') ? left : left.querySelector('[data-cart-item]');
                const b = right.matches('[data-cart-item]') ? right : right.querySelector('[data-cart-item]');
                const compared = presentation.field === 'name'
                    ? collator.compare(a.dataset[key], b.dataset[key])
                    : Number(a.dataset[key]) - Number(b.dataset[key]);
                return compared * multiplier || Number(a.dataset.cartItem) - Number(b.dataset.cartItem);
            });
            items.forEach(item => list.appendChild(item));
        }
        root.querySelectorAll('[data-cart-details]').forEach(details => {
            const expanded = presentation.expanded.has(details.dataset.cartDetails);
            details.classList.toggle('show', expanded);
            const button = root.querySelector(`[aria-controls="${details.id}"]`);
            button?.classList.toggle('collapsed', !expanded);
            button?.setAttribute('aria-expanded', String(expanded));
        });
    }

    function applySort() {
        applyPresentation(template.content);
        if (page) applyPresentation(page);
        if (panel?.dataset.activeSidePanel === '#cart-section') {
            applyPresentation(panel);
            const status = panel.querySelector('[data-cart-sort-status]');
            const label = panel.querySelector('[data-cart-sort] option:checked')?.textContent;
            if (status) status.textContent = `Sorted by ${label}, ${presentation.direction === 'asc' ? 'ascending' : 'descending'}.`;
        }
    }

    store.subscribe(({ cart, html, pageHtml }) => {
        const total = cart.total_amount ?? cart.products.reduce((sum, item) => sum + Number(item.subtotal), 0);
        trigger.querySelector('[data-cart-total]').textContent = `$${amount.format(total)}`;
        trigger.querySelector('[data-cart-count]').textContent = cart.total_count;
        if (errorLabel) errorLabel.textContent = '';
        template.innerHTML = html;
        applyPresentation(template.content);
        if (page && typeof pageHtml === 'string') {
            const collapse = page.querySelector('#cart');
            const collapsed = collapse && !collapse.classList.contains('show');
            const focusedId = page.contains(document.activeElement) ? document.activeElement.id : null;
            page.innerHTML = pageHtml;
            applyPresentation(page);
            if (collapsed) {
                page.querySelector('#cart')?.classList.remove('show');
                const toggle = page.querySelector('[data-bs-target="#cart"]');
                toggle?.classList.add('collapsed');
                toggle?.setAttribute('aria-expanded', 'false');
            }
            if (focusedId) page.querySelector(`[id="${focusedId}"]`)?.focus({ preventScroll: true });
        }
        if (panel?.dataset.activeSidePanel === '#cart-section') {
            const scrollTop = panel.querySelector('[data-cart-scroll]')?.scrollTop ?? 0;
            const focusedId = panel.contains(document.activeElement) ? document.activeElement.id : null;
            panel.innerHTML = template.innerHTML;
            const scroller = panel.querySelector('[data-cart-scroll]');
            if (scroller) scroller.scrollTop = scrollTop;
            if (focusedId) document.getElementById(focusedId)?.focus({ preventScroll: true });
        }
    });

    store.subscribeError(error => {
        if (errorLabel) errorLabel.textContent = error.message;
        for (const root of [panel, page]) {
            const feedback = root?.querySelector('[data-cart-feedback]');
            if (feedback) feedback.textContent = error.message;
        }
    });

    document.addEventListener('change', event => {
        if (!event.target.closest('[data-cart-view], [data-cart-page]')) return;
        if (!event.target.matches('[data-cart-sort]') || !sortFields.has(event.target.value)) return;
        presentation.field = event.target.value;
        applySort();
    });
    document.addEventListener('click', async event => {
        if (!event.target.closest('[data-cart-view], [data-cart-page]')) return;
        const direction = event.target.closest('[data-cart-direction]');
        if (direction && ['asc', 'desc'].includes(direction.dataset.cartDirection)) {
            presentation.direction = direction.dataset.cartDirection;
            applySort();
        }
        const remove = event.target.closest('[data-cart-remove]');
        if (!remove || remove.disabled) return;
        remove.disabled = true;
        try {
            await store.setQuantity(remove.dataset.cartRemove, 0);
        } catch (_) {
            // The shared error listener displays the server or connection error.
        } finally {
            remove.disabled = false;
        }
    });
    for (const [eventName, expanded] of [['shown.bs.collapse', true], ['hidden.bs.collapse', false]]) {
        panel?.addEventListener(eventName, event => {
            const id = event.target.dataset.cartDetails;
            if (!id) return;
            if (expanded) presentation.expanded.add(id);
            else presentation.expanded.delete(id);
            applyPresentation(template.content);
        });
    }

    trigger.addEventListener('click', () => store.refresh().catch(() => {}));
    store.refresh().catch(() => {});
})();
