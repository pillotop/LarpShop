const COMPARE_STORAGE_KEY = 'larpshop_compare_v1';
const MAX_COMPARE_ITEMS = 4;

function loadCompareMessages() {
    const el = document.getElementById('compare-messages');
    if (!el) return {};
    try {
        return JSON.parse(el.textContent || '{}');
    } catch {
        return {};
    }
}

function readCompareItems() {
    try {
        const raw = sessionStorage.getItem(COMPARE_STORAGE_KEY);
        const parsed = raw ? JSON.parse(raw) : [];
        return Array.isArray(parsed) ? parsed.slice(0, MAX_COMPARE_ITEMS) : [];
    } catch {
        return [];
    }
}

function writeCompareItems(items) {
    try {
        sessionStorage.setItem(COMPARE_STORAGE_KEY, JSON.stringify(items.slice(0, MAX_COMPARE_ITEMS)));
    } catch {
        // Ignore storage quota errors
    }
}

function initAdvancedSearchButton() {
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-advanced-search]');
        if (!trigger) return;

        event.preventDefault();
        const filterBtn = document.getElementById('open-filter-panel-btn');
        if (filterBtn) {
            filterBtn.click();
            return;
        }

        const baseUrl = trigger.getAttribute('data-advanced-search') || '/products';
        const form = trigger.closest('form');
        const queryInput = form ? form.querySelector('input[name="q"]') : null;
        const url = new URL(baseUrl, window.location.origin);

        if (queryInput && queryInput.value.trim() !== '') {
            url.searchParams.set('q', queryInput.value.trim());
        }
        url.searchParams.set('advanced', '1');
        window.location.href = url.toString();
    });

    const params = new URLSearchParams(window.location.search);
    if (params.get('advanced') === '1') {
        const filterBtn = document.getElementById('open-filter-panel-btn');
        if (filterBtn) {
            window.requestAnimationFrame(() => {
                setTimeout(() => filterBtn.click(), 80);
            });
        }
    }
}

function initHomepageBroadSelect() {
    const buttons = Array.from(document.querySelectorAll('[data-broad-select-mode]'));
    const list = document.querySelector('.selector-product-list');
    if (buttons.length === 0 || !list) return;

    buttons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const mode = btn.getAttribute('data-broad-select-mode');

            buttons.forEach((other) => {
                const active = other === btn;
                other.classList.toggle('is-active', active);
                other.setAttribute('aria-pressed', active ? 'true' : 'false');
            });

            const items = Array.from(list.querySelectorAll(':scope > li'));
            items.sort((a, b) => {
                const orderA = Number(a.getAttribute('data-product-order') || 0);
                const orderB = Number(b.getAttribute('data-product-order') || 0);
                const ratingA = Number(a.getAttribute('data-product-rating') || 0);
                const ratingB = Number(b.getAttribute('data-product-rating') || 0);
                const reviewsA = Number(a.getAttribute('data-product-reviews') || 0);
                const reviewsB = Number(b.getAttribute('data-product-reviews') || 0);

                if (mode === 'top_rated') {
                    if (ratingB !== ratingA) return ratingB - ratingA;
                    if (reviewsB !== reviewsA) return reviewsB - reviewsA;
                    return orderA - orderB;
                }

                if (reviewsB !== reviewsA) return reviewsB - reviewsA;
                return orderA - orderB;
            });

            items.forEach((item) => list.appendChild(item));
            list.scrollTo({ left: 0, behavior: 'smooth' });
        });
    });
}

function initProductCompare() {
    const messages = loadCompareMessages();
    const t = (key, fallback) => messages[key] || fallback;

    let bar = null;
    let dialog = null;

    function ensureCompareDom() {
        if (!bar) {
            bar = document.createElement('aside');
            bar.id = 'product-compare-bar';
            bar.className = 'product-compare-bar shadow-lg';
            bar.setAttribute('aria-label', t('title', 'Compare products'));
            bar.hidden = true;
            document.body.appendChild(bar);
        }

        if (!dialog) {
            dialog = document.createElement('dialog');
            dialog.id = 'product-compare-dialog';
            dialog.className = 'product-compare-dialog rounded-5 border-0 shadow-lg p-0';
            dialog.addEventListener('click', (e) => {
                if (e.target === dialog) dialog.close();
            });
            document.body.appendChild(dialog);
        }
    }

    function syncCheckboxes(items) {
        const selectedIds = new Set(items.map((item) => String(item.id)));
        document.querySelectorAll('.product-card-compare-checkbox').forEach((cb) => {
            const id = String(cb.getAttribute('data-compare-id') || cb.value);
            cb.checked = selectedIds.has(id);
        });
    }

    function renderDialog(items) {
        ensureCompareDom();
        dialog.replaceChildren();

        if (items.length === 0) {
            if (dialog.open) dialog.close();
            return;
        }

        const header = document.createElement('div');
        header.className = 'd-flex justify-content-between align-items-center border-bottom px-4 py-3 bg-1';
        const title = document.createElement('h2');
        title.className = 'h5 fw-bold mb-0';
        title.textContent = t('title', 'Compare products');

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'btn-close';
        closeBtn.setAttribute('aria-label', t('close', 'Close'));
        closeBtn.addEventListener('click', () => dialog.close());

        header.append(title, closeBtn);

        const body = document.createElement('div');
        body.className = 'p-3 p-md-4 overflow-x-auto';

        const table = document.createElement('table');
        table.className = 'table align-middle mb-0 product-compare-table';

        const thead = document.createElement('thead');
        const headRow = document.createElement('tr');
        const emptyTh = document.createElement('th');
        emptyTh.style.minWidth = '120px';
        headRow.appendChild(emptyTh);

        items.forEach((item) => {
            const th = document.createElement('th');
            th.style.minWidth = '180px';
            th.className = 'text-center position-relative';

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-outline theme-danger rounded-circle position-absolute top-0 end-0 m-1';
            removeBtn.setAttribute('aria-label', t('remove', 'Remove from comparison'));
            removeBtn.innerHTML = '<i class="bi bi-x-lg" aria-hidden="true"></i>';
            removeBtn.addEventListener('click', () => removeItem(item.id));

            const img = document.createElement('img');
            img.src = item.image;
            img.alt = item.name;
            img.className = 'rounded-3 border bg-white object-fit-cover mb-2';
            img.style.width = '88px';
            img.style.height = '88px';

            const nameLink = document.createElement('a');
            nameLink.href = item.url;
            nameLink.className = 'd-block fw-semibold text-decoration-none fg-body small';
            nameLink.textContent = item.name;

            th.append(removeBtn, img, nameLink);
            headRow.appendChild(th);
        });
        thead.appendChild(headRow);

        const tbody = document.createElement('tbody');
        const rows = [
            [t('price', 'Price'), (it) => it.price, 'fw-bold fg-primary'],
            [t('rating', 'Rating'), (it) => it.rating, 'small'],
            [t('category', 'Category'), (it) => it.category, 'small'],
            [t('availability', 'Availability'), (it) => it.stock, 'small fw-semibold'],
        ];

        rows.forEach(([label, getter, cellClass]) => {
            const tr = document.createElement('tr');
            const th = document.createElement('th');
            th.scope = 'row';
            th.className = 'small fg-3 fw-semibold';
            th.textContent = label;
            tr.appendChild(th);

            items.forEach((item) => {
                const td = document.createElement('td');
                td.className = `text-center ${cellClass}`;
                td.textContent = getter(item);
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });

        const actionRow = document.createElement('tr');
        const actionTh = document.createElement('th');
        actionTh.scope = 'row';
        actionTh.className = 'small fg-3 fw-semibold';
        actionTh.textContent = t('action', 'Action');
        actionRow.appendChild(actionTh);

        items.forEach((item) => {
            const td = document.createElement('td');
            td.className = 'text-center';
            const link = document.createElement('a');
            link.href = item.url;
            link.className = 'btn btn-sm btn-solid theme-primary rounded-pill px-3';
            link.textContent = t('view', 'View product');
            td.appendChild(link);
            actionRow.appendChild(td);
        });
        tbody.appendChild(actionRow);

        table.append(thead, tbody);
        body.appendChild(table);
        dialog.append(header, body);
    }

    function renderBar() {
        const items = readCompareItems();
        syncCheckboxes(items);

        if (items.length === 0) {
            if (bar) bar.hidden = true;
            if (dialog && dialog.open) dialog.close();
            return;
        }

        ensureCompareDom();
        bar.hidden = false;
        bar.replaceChildren();

        const container = document.createElement('div');
        container.className = 'd-flex flex-wrap align-items-center justify-content-between gap-3 w-100';

        const left = document.createElement('div');
        left.className = 'd-flex align-items-center gap-2 flex-wrap';

        const badge = document.createElement('span');
        badge.className = 'fw-semibold small me-1';
        badge.textContent = t('selected', ':count selected (up to 4)').replace(':count', String(items.length));
        left.appendChild(badge);

        items.forEach((item) => {
            const chip = document.createElement('div');
            chip.className = 'product-compare-chip d-inline-flex align-items-center gap-2 rounded-pill ps-1 pe-2 py-1 border bg-white text-dark';

            const img = document.createElement('img');
            img.src = item.image;
            img.alt = '';
            img.className = 'rounded-circle object-fit-cover';
            img.style.width = '26px';
            img.style.height = '26px';

            const label = document.createElement('span');
            label.className = 'small text-truncate';
            label.style.maxWidth = '110px';
            label.textContent = item.name;

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn-close';
            removeBtn.style.fontSize = '0.6rem';
            removeBtn.setAttribute('aria-label', t('remove', 'Remove from comparison'));
            removeBtn.addEventListener('click', () => removeItem(item.id));

            chip.append(img, label, removeBtn);
            left.appendChild(chip);
        });

        const actions = document.createElement('div');
        actions.className = 'd-flex align-items-center gap-2 ms-auto';

        const clearBtn = document.createElement('button');
        clearBtn.type = 'button';
        clearBtn.className = 'btn btn-sm btn-outline theme-secondary rounded-pill px-3';
        clearBtn.textContent = t('clear', 'Clear all');
        clearBtn.addEventListener('click', () => {
            writeCompareItems([]);
            renderBar();
        });

        const compareBtn = document.createElement('button');
        compareBtn.type = 'button';
        compareBtn.id = 'open-compare-modal-btn';
        compareBtn.className = 'btn btn-sm btn-solid theme-primary rounded-pill px-3 fw-semibold';
        compareBtn.textContent = `${t('compare_now', 'Compare now')} (${items.length})`;
        compareBtn.addEventListener('click', () => {
            const current = readCompareItems();
            renderDialog(current);
            if (!dialog.open) dialog.showModal();
        });

        actions.append(clearBtn, compareBtn);
        container.append(left, actions);
        bar.appendChild(container);

        if (dialog && dialog.open) {
            renderDialog(items);
        }
    }

    function removeItem(id) {
        const filtered = readCompareItems().filter((item) => String(item.id) !== String(id));
        writeCompareItems(filtered);
        renderBar();
    }

    document.addEventListener('change', (event) => {
        const cb = event.target.closest('.product-card-compare-checkbox');
        if (!cb) return;

        const id = String(cb.getAttribute('data-compare-id') || cb.value);
        const current = readCompareItems();

        if (cb.checked) {
            if (current.some((item) => String(item.id) === id)) {
                renderBar();
                return;
            }
            if (current.length >= MAX_COMPARE_ITEMS) {
                cb.checked = false;
                alert(t('limit', 'You can compare up to 4 products at a time.'));
                return;
            }
            current.push({
                id,
                name: cb.getAttribute('data-compare-name') || '',
                price: cb.getAttribute('data-compare-price') || '',
                image: cb.getAttribute('data-compare-image') || '/images/product-placeholder.jpg',
                url: cb.getAttribute('data-compare-url') || '#',
                category: cb.getAttribute('data-compare-category') || '—',
                rating: cb.getAttribute('data-compare-rating') || '—',
                stock: cb.getAttribute('data-compare-stock') || '',
            });
            writeCompareItems(current);
        } else {
            writeCompareItems(current.filter((item) => String(item.id) !== id));
        }

        renderBar();
    });

    if (document.querySelector('.product-card-compare-checkbox') || readCompareItems().length > 0) {
        renderBar();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initAdvancedSearchButton();
    initHomepageBroadSelect();
    initProductCompare();
});
