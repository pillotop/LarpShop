import { cartMessage } from './cart_messages.js';
import '../app.js';

class ProductQuantityPicker {
    constructor(element) {
        this.element = element;

        this.addToCartUrl = element.dataset.addToCartUrl;
        this.setCartUrl = element.dataset.setCartUrl;
        this.selectsAddQuantity = element.dataset.quantityMode === 'add';

        this.productId = element.dataset.productId;
        this.csrfToken = element.querySelector('input[name="_token"]').value;

        this.addToCartButton = element.querySelector('[data-add-to-cart], #add-to-cart');
        this.buyNowButton = element.querySelector('[data-buy-now]');
        this.counter = element.querySelector('[data-quantity-counter], #counter, counter');
        this.incrementButton = element.querySelector('[data-quantity-increment], #increment, increment');
        this.decrementButton = element.querySelector('[data-quantity-decrement], #decrement, decrement');
        this.feedback = element.querySelector('.cart-feedback')
            ?? element.closest('[data-cart-item]')?.querySelector('.cart-feedback');

        this.pending = false;
        this.store = window.larpCart;
        this.#setupSignals();
    }

    #setupSignals() {
        this.unsubscribe = this.store?.subscribe(({ cart }) => {
            const item = cart.products.find(item => String(item.product.product_id) === String(this.productId));
            if (this.counter && !this.selectsAddQuantity) this.counter.value = item?.quantity ?? 0;
        });

        this.addToCartButton?.addEventListener('click', () => {
            this.#send(this.addToCartUrl, this.selectsAddQuantity ? Number(this.counter.value) : 1);
        });

        this.buyNowButton?.addEventListener('click', async () => {
            const qty = this.selectsAddQuantity ? Math.max(1, Number(this.counter?.value || 1)) : 1;
            const ok = await this.#send(this.addToCartUrl, qty);
            if (ok && this.buyNowButton.dataset.buyNow) {
                window.location.href = this.buyNowButton.dataset.buyNow;
            }
        });

        this.counter?.addEventListener('change', () => {
            this.#setQuantity(Number(this.counter.value));
        });

        this.incrementButton?.addEventListener('click', () => {
            this.#setQuantity(Number(this.counter.value) + 1);
        });

        this.decrementButton?.addEventListener('click', () => {
            const quantity = Number(this.counter.value);
            if (quantity <= 0) return;
            if (quantity === 1) this.counter.value = 0;
            this.#setQuantity(quantity - 1);
        });
    }

    #setQuantity(quantity) {
        if (this.selectsAddQuantity) {
            this.counter.value = quantity;
            this.#message('');
            return;
        }
        this.#send(this.setCartUrl, quantity);
    }

    async #send(url, quantity) {
        if (this.pending || !url) return false;
        if (quantity === 0) {
            this.#message('');
            return false;
        }
        if (!Number.isInteger(quantity) || quantity < 0) {
            this.#message(cartMessage('invalid_quantity'), true);
            return false;
        }

        this.pending = true;
        const controls = [this.addToCartButton, this.buyNowButton, this.counter, this.incrementButton, this.decrementButton]
            .filter(Boolean).map(control => [control, control.disabled]);
        controls.forEach(([control]) => { control.disabled = true; });
        this.element.setAttribute('aria-busy', 'true');
        this.#message(cartMessage('updating'));

        try {
            if (!this.store) throw new Error(cartMessage('unavailable'));
            const result = url === this.addToCartUrl
                ? await this.store.add(this.productId, quantity)
                : await this.store.setQuantity(this.productId, quantity);

            this.#message(cartMessage('updated', { count: result.quantity }));
            return true;
        } catch (error) {
            // Add is not idempotent: do not automatically retry an uncertain request.
            this.#message(error instanceof TypeError
                ? cartMessage('uncertain')
                : error.message, true);
            return false;
        } finally {
            this.pending = false;
            controls.forEach(([control, disabled]) => { control.disabled = disabled; });
            this.element.removeAttribute('aria-busy');
        }
    }

    #message(message, isError = false) {
        if (!this.feedback) return;
        this.feedback.textContent = message;
        this.feedback.classList.toggle('visually-hidden', !isError);
        this.feedback.classList.toggle('fg-danger', isError);
    }
}

// Cart markup is replaced after each server-confirmed update.
const quantityPickers = new Map();
function initializeQuantityPickers() {
    for (const [element, picker] of quantityPickers) {
        if (!element.isConnected) {
            picker.unsubscribe?.();
            quantityPickers.delete(element);
        }
    }
    document.querySelectorAll('.quantity-picker').forEach(element => {
        if (!quantityPickers.has(element)) {
            quantityPickers.set(element, new ProductQuantityPicker(element));
        }
    });
}
initializeQuantityPickers();
new MutationObserver(initializeQuantityPickers).observe(document.body, { childList: true, subtree: true });
