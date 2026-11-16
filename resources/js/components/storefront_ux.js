const COMPARE_STORAGE_KEY = 'larpshop_compare_v1';
const RECENTLY_VIEWED_KEY = 'larpshop_recently_viewed_v1';
const MAX_COMPARE_ITEMS = 4;
const MAX_RECENTLY_VIEWED = 8;

function loadStorefrontUxMessages() {
    const el = document.getElementById('storefront-ux-messages');
    if (!el) return {};
    try {
        return JSON.parse(el.textContent || '{}');
    } catch {
        return {};
    }
}

function showStorefrontToast(message, { type = 'success', actionLabel = '', actionUrl = '', duration = 3500 } = {}) {
    if (!message) return;

    let container = document.getElementById('larp-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'larp-toast-container';
        container.className = 'larp-toast-container';
        container.setAttribute('aria-live', 'polite');
        container.setAttribute('aria-atomic', 'true');
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `larp-toast d-flex align-items-center justify-content-between gap-2 ${type === 'danger' ? 'is-danger' : ''}`;
    toast.setAttribute('role', 'status');

    const left = document.createElement('div');
    left.className = 'd-flex align-items-center gap-2 small fw-semibold';
    const icon = document.createElement('i');
    icon.className = type === 'danger' ? 'bi bi-exclamation-circle-fill text-danger' : 'bi bi-check-circle-fill text-success';
    icon.setAttribute('aria-hidden', 'true');
    const text = document.createElement('span');
    text.textContent = message;
    left.append(icon, text);

    const right = document.createElement('div');
    right.className = 'd-flex align-items-center gap-2 flex-shrink-0';
    if (actionLabel && actionUrl) {
        const link = document.createElement('a');
        link.href = actionUrl;
        link.className = 'btn btn-sm btn-outline theme-primary rounded-pill px-2 py-1 text-nowrap';
        link.textContent = actionLabel;
        right.appendChild(link);
    }

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'btn-close';
    closeBtn.style.fontSize = '0.65rem';
    closeBtn.addEventListener('click', () => toast.remove());
    right.appendChild(closeBtn);

    toast.append(left, right);
    container.appendChild(toast);

    while (container.children.length > 3) {
        container.firstElementChild?.remove();
    }

    window.setTimeout(() => {
        toast.remove();
    }, duration);
}

window.larpToast = showStorefrontToast;

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

function initProductDetailInteractions() {
    const variantButtons = Array.from(document.querySelectorAll('[data-variant-option]'));
    const priceEl = document.getElementById('product-detail-price');
    const stockEl = document.getElementById('product-detail-stock');
    const selectedLabel = document.getElementById('product-selected-variant-name');
    const counter = document.getElementById('product-purchase-quantity');
    const addBtn = document.querySelector('.quantity-picker [data-add-to-cart]');
    const buyNowBtn = document.querySelector('.quantity-picker [data-buy-now]');

    if (variantButtons.length > 0) {
        variantButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                variantButtons.forEach((other) => {
                    const active = other === btn;
                    other.classList.toggle('border-primary', active);
                    other.classList.toggle('bg-white', active);
                    other.classList.toggle('shadow-xs', active);
                    other.setAttribute('aria-pressed', active ? 'true' : 'false');
                });

                const price = btn.getAttribute('data-variant-price');
                const variantId = btn.getAttribute('data-variant-id') || '';
                const stockCount = Number(btn.getAttribute('data-variant-stock') || 0);
                const stockText = btn.getAttribute('data-variant-stock-label') || '';
                const name = btn.getAttribute('data-variant-name') || '';
                const picker = document.querySelector('.quantity-picker[data-quantity-mode="add"]');

                if (picker) {
                    picker.dataset.variantId = variantId;
                }
                if (priceEl && price) {
                    priceEl.textContent = price;
                }
                if (selectedLabel && name) {
                    selectedLabel.textContent = name;
                }
                if (stockEl && stockText) {
                    stockEl.textContent = stockText;
                    stockEl.classList.toggle('fg-success', stockCount > 0);
                    stockEl.classList.toggle('fg-danger', stockCount <= 0);
                }
                if (counter && stockCount > 0) {
                    counter.max = String(stockCount);
                    if (Number(counter.value) > stockCount) {
                        counter.value = String(stockCount);
                    }
                }
                if (addBtn) addBtn.disabled = stockCount <= 0;
                if (buyNowBtn) buyNowBtn.disabled = stockCount <= 0;
            });
        });
    }

    const carouselEl = document.getElementById('previewCarousel');
    const thumbButtons = Array.from(document.querySelectorAll('[data-carousel-thumb-index]'));
    if (carouselEl && thumbButtons.length > 0) {
        carouselEl.addEventListener('slide.bs.carousel', (event) => {
            const nextIndex = Number(event.to);
            thumbButtons.forEach((btn, idx) => {
                btn.classList.toggle('border-primary', idx === nextIndex);
                btn.classList.toggle('border-2', idx === nextIndex);
            });
        });
        thumbButtons.forEach((btn) => {
            btn.addEventListener('click', () => {
                thumbButtons.forEach((other) => {
                    const active = other === btn;
                    other.classList.toggle('border-primary', active);
                    other.classList.toggle('border-2', active);
                });
            });
        });
    }
}

function initAjaxWishlist() {
    const uxMessages = loadStorefrontUxMessages();

    document.addEventListener('submit', async (event) => {
        const form = event.target.closest('form[data-wishlist-form]');
        if (!form) return;

        event.preventDefault();
        if (form.dataset.pending === '1') return;
        form.dataset.pending = '1';

        const button = form.querySelector('button[type="submit"]');
        const icon = form.querySelector('i.bi');
        const labelSpan = form.querySelector('[data-wishlist-label]');
        if (button) button.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
            });

            if (response.status === 401) {
                const authTrigger = document.getElementById('auth-drawer-trigger');
                if (authTrigger) authTrigger.click();
                return;
            }

            const data = await response.json().catch(() => null);
            if (!response.ok || !data?.success) {
                throw new Error(uxMessages.wishlist_error || 'Failed to update wishlist.');
            }

            const saved = Boolean(data.saved);
            form.dataset.saved = saved ? '1' : '0';
            form.action = data.action_url || (saved ? form.dataset.destroyUrl : form.dataset.storeUrl);

            let methodInput = form.querySelector('input[name="_method"]');
            if (saved) {
                if (!methodInput) {
                    methodInput = document.createElement('input');
                    methodInput.type = 'hidden';
                    methodInput.name = '_method';
                    methodInput.value = 'DELETE';
                    form.appendChild(methodInput);
                }
            } else if (methodInput) {
                methodInput.remove();
            }

            if (icon) {
                icon.className = saved ? 'bi bi-heart-fill fg-danger' : 'bi bi-heart';
            }

            if (button) {
                const nextAria = saved ? form.dataset.removeAria : form.dataset.saveAria;
                const nextTitle = saved ? form.dataset.removeText : form.dataset.saveText;
                button.setAttribute('aria-pressed', saved ? 'true' : 'false');
                if (nextAria) button.setAttribute('aria-label', nextAria);
                if (nextTitle) button.setAttribute('title', nextTitle);
            }

            if (labelSpan) {
                labelSpan.textContent = saved ? (form.dataset.removeText || '') : (form.dataset.saveText || '');
            }

            showStorefrontToast(data.message || '', { type: 'success' });
        } catch (error) {
            showStorefrontToast(error.message || uxMessages.wishlist_error || 'Failed to update wishlist.', { type: 'danger' });
        } finally {
            delete form.dataset.pending;
            if (button) button.disabled = false;
        }
    });
}

function initSearchAutocomplete() {
    const uxMessages = loadStorefrontUxMessages();
    const suggestionsUrl = uxMessages.suggestions_url;
    const searchUrl = uxMessages.search_url || '/products';
    if (!suggestionsUrl) return;

    const searchWrap = document.querySelector('#search-bar .search-input-wrap');
    const input = searchWrap?.querySelector('input[name="q"]');
    if (!searchWrap || !input) return;

    const menu = document.createElement('div');
    menu.className = 'search-suggestions-menu';
    menu.hidden = true;
    searchWrap.appendChild(menu);

    let timer = null;
    let activeController = null;

    function hideMenu() {
        menu.hidden = true;
        menu.replaceChildren();
    }

    function renderSuggestions(keyword, items) {
        menu.replaceChildren();
        if (!Array.isArray(items) || items.length === 0) {
            hideMenu();
            return;
        }

        items.forEach((item) => {
            const link = document.createElement('a');
            link.href = item.url;
            link.className = 'search-suggestion-item';

            const img = document.createElement('img');
            img.src = item.image_url;
            img.alt = '';
            img.className = 'rounded-2 border object-fit-cover flex-shrink-0';
            img.style.width = '40px';
            img.style.height = '40px';

            const info = document.createElement('div');
            info.className = 'flex-grow-1 min-w-0';
            const title = document.createElement('div');
            title.className = 'small fw-semibold text-truncate';
            title.textContent = item.name;
            const meta = document.createElement('div');
            meta.className = 'small text-muted text-truncate';
            meta.textContent = item.category || '';
            info.append(title, meta);

            const price = document.createElement('span');
            price.className = 'small fw-bold text-primary flex-shrink-0';
            price.textContent = item.price;

            link.append(img, info, price);
            menu.appendChild(link);
        });

        const footerLink = document.createElement('a');
        const fullUrl = new URL(searchUrl, window.location.origin);
        fullUrl.searchParams.set('q', keyword);
        footerLink.href = fullUrl.toString();
        footerLink.className = 'search-suggestion-item justify-content-center small fw-semibold text-primary border-bottom-0 bg-light';
        footerLink.textContent = (uxMessages.suggestions_for || 'Search all results for ":query"').replace(':query', keyword);
        menu.appendChild(footerLink);

        menu.hidden = false;
    }

    input.addEventListener('input', () => {
        const keyword = input.value.trim();
        window.clearTimeout(timer);

        if (keyword.length < 2) {
            activeController?.abort();
            hideMenu();
            return;
        }

        timer = window.setTimeout(async () => {
            activeController?.abort();
            activeController = new AbortController();
            try {
                const url = new URL(suggestionsUrl, window.location.origin);
                url.searchParams.set('q', keyword);
                const res = await fetch(url.toString(), {
                    signal: activeController.signal,
                    headers: { 'Accept': 'application/json' },
                });
                const payload = await res.json().catch(() => null);
                if (res.ok && payload?.items) {
                    renderSuggestions(keyword, payload.items);
                }
            } catch (_) {
                // Ignore aborted or transient autocomplete errors
            }
        }, 220);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            hideMenu();
        }
    });

    document.addEventListener('click', (event) => {
        if (!searchWrap.contains(event.target)) {
            hideMenu();
        }
    });
}

function initRecentlyViewed() {
    const uxMessages = loadStorefrontUxMessages();

    function readRecent() {
        try {
            const raw = localStorage.getItem(RECENTLY_VIEWED_KEY);
            const parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed : [];
        } catch {
            return [];
        }
    }

    function writeRecent(items) {
        try {
            localStorage.setItem(RECENTLY_VIEWED_KEY, JSON.stringify(items.slice(0, MAX_RECENTLY_VIEWED)));
        } catch {
            // Ignore quota errors
        }
    }

    const currentScript = document.getElementById('recently-viewed-product');
    if (currentScript) {
        try {
            const current = JSON.parse(currentScript.textContent || 'null');
            if (current && current.id) {
                const filtered = readRecent().filter((item) => String(item.id) !== String(current.id));
                filtered.unshift(current);
                writeRecent(filtered);
            }
        } catch {
            // Ignore invalid JSON payload
        }
    }

    const section = document.getElementById('recently-viewed-section');
    if (!section) return;

    const currentProductId = section.getAttribute('data-current-product-id');
    const items = readRecent().filter((item) => !currentProductId || String(item.id) !== String(currentProductId));
    if (items.length === 0) {
        section.hidden = true;
        return;
    }

    section.replaceChildren();

    const header = document.createElement('div');
    header.className = 'd-flex align-items-center justify-content-between gap-2 mb-3';
    const heading = document.createElement('h2');
    heading.className = 'h4 fw-bold mb-0';
    heading.textContent = uxMessages.recently_viewed_title || 'Recently Viewed';

    const clearBtn = document.createElement('button');
    clearBtn.type = 'button';
    clearBtn.className = 'btn btn-sm btn-outline theme-secondary rounded-pill px-3';
    clearBtn.textContent = uxMessages.recently_viewed_clear || 'Clear history';
    clearBtn.addEventListener('click', () => {
        writeRecent([]);
        section.hidden = true;
        section.replaceChildren();
    });
    header.append(heading, clearBtn);

    const track = document.createElement('div');
    track.className = 'd-flex flex-nowrap gap-3 overflow-x-auto pb-2';

    items.forEach((item) => {
        const card = document.createElement('a');
        card.href = item.url;
        card.className = 'card recently-viewed-card border rounded-4 overflow-hidden text-decoration-none fg-body bg-white shadow-xs';

        const img = document.createElement('img');
        img.src = item.image;
        img.alt = item.name;
        img.loading = 'lazy';
        img.className = 'object-fit-cover w-100';
        img.style.height = '150px';

        const body = document.createElement('div');
        body.className = 'p-3';
        const title = document.createElement('div');
        title.className = 'fw-semibold small text-truncate mb-1';
        title.textContent = item.name;
        const price = document.createElement('div');
        price.className = 'fw-bold fg-primary small';
        price.textContent = item.price;
        body.append(title, price);

        card.append(img, body);
        track.appendChild(card);
    });

    section.append(header, track);
    section.hidden = false;
}

document.addEventListener('DOMContentLoaded', () => {
    initAdvancedSearchButton();
    initHomepageBroadSelect();
    initProductCompare();
    initProductDetailInteractions();
    initAjaxWishlist();
    initSearchAutocomplete();
    initRecentlyViewed();
});
