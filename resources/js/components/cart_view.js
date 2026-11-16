import { cartMessage } from './cart_messages.js';
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
    let selectedIds = null;
    let selectedShippingMethod = 'home_delivery';
    let appliedVoucher = null;

    async function previewVoucherCode(code) {
        if (!page) return;
        const applyBtn = page.querySelector('[data-voucher-apply-btn]');
        const input = page.querySelector('[data-voucher-input]');
        const removeBtn = page.querySelector('[data-voucher-remove-btn]');
        const feedback = page.querySelector('[data-voucher-feedback]');
        const previewUrl = applyBtn?.dataset.previewUrl;
        const trimmed = (code ?? input?.value ?? '').trim().toUpperCase();

        if (!previewUrl || !trimmed) return;

        if (applyBtn) applyBtn.disabled = true;
        if (feedback) {
            feedback.classList.add('d-none');
            feedback.classList.remove('fg-danger', 'fg-success');
            feedback.textContent = '';
        }

        try {
            const selectedArr = selectedIds ? [...selectedIds].map(Number) : [];
            const response = await fetch(previewUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    voucher_code: trimmed,
                    selected_cart_item_ids: selectedArr.length > 0 ? selectedArr : undefined,
                    shipping_method: selectedShippingMethod,
                }),
            });

            const data = await response.json().catch(() => null);
            if (!response.ok || !data?.success) {
                const msg = Object.values(data?.errors || {}).flat()[0] || data?.message || 'Invalid voucher code.';
                throw new Error(msg);
            }

            appliedVoucher = {
                code: data.voucher.code,
                discount: Number(data.voucher.discount_amount || 0),
                title: data.voucher.title || '',
            };
            if (input) input.value = appliedVoucher.code;
            if (removeBtn) removeBtn.classList.remove('d-none');
            if (feedback) {
                feedback.textContent = data.message || `${appliedVoucher.code} applied.`;
                feedback.classList.remove('d-none', 'fg-danger');
                feedback.classList.add('fg-success');
            }
            window.larpToast?.(data.message || `${appliedVoucher.code} applied.`, { type: 'success' });
        } catch (error) {
            appliedVoucher = null;
            if (removeBtn) removeBtn.classList.add('d-none');
            if (feedback) {
                feedback.textContent = error.message;
                feedback.classList.remove('d-none', 'fg-success');
                feedback.classList.add('fg-danger');
            }
        } finally {
            if (applyBtn) applyBtn.disabled = false;
            syncCartSelection(page);
        }
    }

    function syncCartSelection(root) {
        if (!root) return;
        const itemBoxes = Array.from(root.querySelectorAll('[data-cart-select-item]'));
        if (itemBoxes.length === 0) return;

        if (selectedIds === null) {
            selectedIds = new Set(itemBoxes.map(cb => String(cb.value)));
        }

        let checkedQty = 0;
        let checkedSubtotal = 0;
        const activeIds = [];

        itemBoxes.forEach(cb => {
            const id = String(cb.value);
            cb.checked = selectedIds.has(id);
            if (cb.checked) {
                activeIds.push(id);
                checkedQty += Number(cb.dataset.itemQuantity || 0);
                checkedSubtotal += Number(cb.dataset.itemSubtotal || 0);
            }
        });

        const selectAll = root.querySelector('[data-cart-select-all]');
        if (selectAll) {
            selectAll.checked = activeIds.length === itemBoxes.length && itemBoxes.length > 0;
            selectAll.indeterminate = activeIds.length > 0 && activeIds.length < itemBoxes.length;
        }

        const shippingSelect = root.querySelector('[data-shipping-method-select]');
        if (shippingSelect) {
            shippingSelect.value = selectedShippingMethod;
        }
        const selectedOption = shippingSelect?.selectedOptions?.[0];
        const shippingFee = activeIds.length > 0 ? Number(selectedOption?.dataset.fee || 0) : 0;
        const discountAmount = activeIds.length > 0 && appliedVoucher ? Number(appliedVoucher.discount || 0) : 0;
        const grandTotal = Math.max(0, checkedSubtotal + shippingFee - discountAmount);

        const summary = root.querySelector('.order-summary');
        if (summary) {
            const freeLabel = summary.dataset.freeLabel || 'Free';
            const prodAmount = summary.querySelector('[data-summary-products-amount]');
            const shipEl = summary.querySelector('[data-summary-shipping]');
            const discountRow = summary.querySelector('[data-summary-discount-row]');
            const discountCodeEl = summary.querySelector('[data-summary-voucher-code]');
            const discountEl = summary.querySelector('[data-summary-discount]');
            const subEl = summary.querySelector('[data-summary-subtotal]');
            const totalEl = summary.querySelector('[data-summary-total]');
            const inputsContainer = summary.querySelector('[data-checkout-selected-inputs]');
            const submitBtn = summary.querySelector('[data-checkout-submit]');
            const voucherInput = summary.querySelector('[data-voucher-input]');
            const voucherRemoveBtn = summary.querySelector('[data-voucher-remove-btn]');
            const addrSelect = summary.querySelector('#cart-shipping-address');
            const hasAddress = addrSelect && !addrSelect.closest('[hidden]') && addrSelect.options.length > 0;

            if (prodAmount) prodAmount.textContent = `$${amount.format(checkedSubtotal)}`;
            if (shipEl) shipEl.textContent = shippingFee > 0 ? `$${amount.format(shippingFee)}` : freeLabel;
            if (subEl) subEl.textContent = `$${amount.format(checkedSubtotal)}`;
            if (totalEl) totalEl.textContent = `$${amount.format(grandTotal)}`;

            if (discountRow) {
                if (appliedVoucher && discountAmount > 0) {
                    discountRow.classList.remove('d-none');
                    if (discountCodeEl) discountCodeEl.textContent = appliedVoucher.code;
                    if (discountEl) discountEl.textContent = `-$${amount.format(discountAmount)}`;
                } else {
                    discountRow.classList.add('d-none');
                }
            }
            if (voucherInput && appliedVoucher) {
                voucherInput.value = appliedVoucher.code;
            }
            if (voucherRemoveBtn) {
                voucherRemoveBtn.classList.toggle('d-none', !appliedVoucher);
            }

            if (inputsContainer) {
                inputsContainer.replaceChildren();
                activeIds.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_cart_item_ids[]';
                    input.value = id;
                    inputsContainer.appendChild(input);
                });
            }

            if (submitBtn) {
                submitBtn.disabled = checkedQty < 1 || !hasAddress;
            }
        }
    }

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
            if (details.open !== expanded) details.open = expanded;
        });
    }

    function applySort() {
        applyPresentation(template.content);
        if (page) applyPresentation(page);
        if (panel?.dataset.activeSidePanel === '#cart-section') {
            applyPresentation(panel);
            const status = panel.querySelector('[data-cart-sort-status]');
            const label = panel.querySelector('[data-cart-sort] option:checked')?.textContent;
            if (status) status.textContent = cartMessage('sorted', { field: label, direction: cartMessage(presentation.direction === 'asc' ? 'ascending' : 'descending') });
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
            const collapsed = collapse && !collapse.open;
            const focusedId = page.contains(document.activeElement) ? document.activeElement.id : null;
            page.innerHTML = pageHtml;
            applyPresentation(page);
            if (selectedIds !== null) {
                const existingIds = new Set(Array.from(page.querySelectorAll('[data-cart-select-item]')).map(cb => String(cb.value)));
                selectedIds = new Set([...selectedIds].filter(id => existingIds.has(id)));
            }
            syncCartSelection(page);
            if (collapsed) page.querySelector('#cart')?.removeAttribute('open');
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
        if (event.target.matches('[data-cart-select-all]')) {
            const checked = event.target.checked;
            const itemBoxes = Array.from(page?.querySelectorAll('[data-cart-select-item]') || []);
            selectedIds = checked ? new Set(itemBoxes.map(cb => String(cb.value))) : new Set();
            syncCartSelection(page);
            if (appliedVoucher) previewVoucherCode(appliedVoucher.code);
            return;
        }
        if (event.target.matches('[data-cart-select-item]')) {
            if (!selectedIds) selectedIds = new Set();
            if (event.target.checked) selectedIds.add(String(event.target.value));
            else selectedIds.delete(String(event.target.value));
            syncCartSelection(page);
            if (appliedVoucher) previewVoucherCode(appliedVoucher.code);
            return;
        }
        if (event.target.matches('[data-shipping-method-select]')) {
            selectedShippingMethod = event.target.value || 'home_delivery';
            syncCartSelection(page);
            if (appliedVoucher) previewVoucherCode(appliedVoucher.code);
            return;
        }
        if (!event.target.matches('[data-cart-sort]') || !sortFields.has(event.target.value)) return;
        presentation.field = event.target.value;
        applySort();
    });
    document.addEventListener('click', async event => {
        const applyVoucher = event.target.closest('[data-voucher-apply-btn]');
        if (applyVoucher) {
            previewVoucherCode();
            return;
        }
        const chipVoucher = event.target.closest('[data-voucher-chip]');
        if (chipVoucher) {
            previewVoucherCode(chipVoucher.dataset.voucherChip);
            return;
        }
        const removeVoucher = event.target.closest('[data-voucher-remove-btn]');
        if (removeVoucher) {
            appliedVoucher = null;
            const input = page?.querySelector('[data-voucher-input]');
            const feedback = page?.querySelector('[data-voucher-feedback]');
            if (input) input.value = '';
            if (feedback) {
                feedback.textContent = '';
                feedback.classList.add('d-none');
            }
            syncCartSelection(page);
            return;
        }
        const openAddr = event.target.closest('[data-open-inline-address]');
        if (openAddr) {
            const dlg = document.getElementById('inline-address-dialog');
            if (dlg && !dlg.open) dlg.showModal();
            return;
        }
        const closeAddr = event.target.closest('[data-close-inline-address]');
        if (closeAddr) {
            const dlg = document.getElementById('inline-address-dialog');
            if (dlg && dlg.open) dlg.close();
            return;
        }
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
            const variantId = remove.dataset.cartVariantId || null;
            await store.setQuantity(remove.dataset.cartRemove, 0, variantId);
        } catch (_) {
            // The shared error listener displays the server or connection error.
        } finally {
            remove.disabled = false;
        }
    });

    document.addEventListener('submit', async event => {
        const form = event.target.closest('form[data-inline-address-form]');
        if (!form) return;

        event.preventDefault();
        const errorBox = form.querySelector('[data-inline-address-error]');
        const submitBtn = form.querySelector('button[type="submit"]');
        if (errorBox) {
            errorBox.textContent = '';
            errorBox.classList.add('d-none');
        }
        if (submitBtn) submitBtn.disabled = true;

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

            const data = await response.json().catch(() => null);
            if (!response.ok || !data?.success) {
                const firstErr = Object.values(data?.errors || {}).flat()[0] || data?.message || 'Could not save address.';
                throw new Error(firstErr);
            }

            const select = document.getElementById('cart-shipping-address');
            const selectWrap = document.querySelector('[data-address-select-wrap]');
            const noHint = document.querySelector('[data-no-address-hint]');

            if (select && data.address) {
                const option = document.createElement('option');
                option.value = String(data.address.address_id);
                option.textContent = data.address.label;
                option.selected = true;
                select.appendChild(option);
                select.required = true;
            }
            if (selectWrap) selectWrap.hidden = false;
            if (noHint) noHint.hidden = true;

            form.reset();
            const dlg = document.getElementById('inline-address-dialog');
            if (dlg && dlg.open) dlg.close();
            syncCartSelection(page);
            window.larpToast?.(data.message || 'Address saved.', { type: 'success' });
        } catch (err) {
            if (errorBox) {
                errorBox.textContent = err.message;
                errorBox.classList.remove('d-none');
            }
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    });
    // Native details toggle events do not bubble, so observe them in capture phase.
    document.addEventListener('toggle', event => {
        const details = event.target;
        const id = details.dataset?.cartDetails;
        if (!id || (!panel?.contains(details) && !page?.contains(details))) return;
        if (details.open) presentation.expanded.add(id);
        else presentation.expanded.delete(id);
        applyPresentation(template.content);
    }, true);

    trigger.addEventListener('click', () => store.refresh().catch(() => {}));
    store.refresh().catch(() => {});
})();
