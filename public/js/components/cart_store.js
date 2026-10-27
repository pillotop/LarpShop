// Shared cart state
class CartStore {
    constructor({ showUrl, addUrl, setUrl, csrfToken, initial = null, channelName = 'larpshop-cart' }) {
        this.urls = { show: showUrl, add: addUrl, set: setUrl };
        this.csrfToken = csrfToken;
        this.listeners = new Set();
        this.errorListeners = new Set();
        this.queue = Promise.resolve();
        this.state = null;
        this.disposed = false;
        this.channel = null;

        try {
            if (typeof BroadcastChannel !== 'undefined') this.channel = new BroadcastChannel(channelName);
        } catch (_) {
            /* Visibility refresh remains available when messaging is unavailable. */
        }

        if (this.channel) {
            this.channel.onmessage = event => {
                if (event.data?.type === 'cart-changed' && document.visibilityState === 'visible') {
                    this.refresh().catch(() => {});
                }
            };
        }

        this.onVisible = () => {
            if (document.visibilityState === 'visible') this.refresh().catch(() => {});
        };

        this.onPageShow = event => {
            if (event.persisted) this.refresh().catch(() => {});
        };

        document.addEventListener('visibilitychange', this.onVisible);
        window.addEventListener('pageshow', this.onPageShow);
        if (initial) this.publish(initial);
    }

    subscribe(listener) {
        this.listeners.add(listener);
        if (this.state) this.notify(listener, this.state);
        return () => this.listeners.delete(listener);
    }

    subscribeError(listener) {
        this.errorListeners.add(listener);
        return () => this.errorListeners.delete(listener);
    }

    getSnapshot() {
        return this.state;
    }

    refresh() {
        return this.enqueue(() => this.request(this.urls.show));
    }

    add(productId, quantity = 1) {
        return this.mutate(this.urls.add, productId, quantity);
    }

    setQuantity(productId, quantity) {
        return this.mutate(this.urls.set, productId, quantity);
    }

    mutate(url, productId, quantity) {
        return this.enqueue(async () => {
            const result = await this.request(url, { product_id: productId, quantity });
            try { this.channel?.postMessage({ type: 'cart-changed' }); } catch (_) {}
            return result;
        });
    }

    enqueue(operation) {
        const next = this.queue.then(() => {
            if (this.disposed) throw new Error('This cart store has been disposed.');
            return operation();
        });
        this.queue = next.catch(() => {});
        return next;
    }

    async request(url, data = null) {
        try {
            const response = await fetch(url, {
                method: data ? 'POST' : 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json',
                    ...(data ? { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken } : {}),
                },
                ...(data ? { body: JSON.stringify(data) } : {}),
            });

            const result = await response.json().catch(() => null);

            if (!response.ok || result?.success !== true) {
                throw new Error(result?.error ?? Object.values(result?.errors ?? {}).flat()[0]
                    ?? result?.message ?? 'Could not confirm the cart update. Refresh before trying again.');
            }

            if (!Array.isArray(result.cart?.products) || typeof result.cart_html !== 'string') {
                throw new Error('Could not read the cart response. Refresh before trying again.');
            }

            this.publish({ cart: result.cart, html: result.cart_html, pageHtml: result.cart_page_html });
            return result;
        } catch (error) {
            const failure = error instanceof TypeError
                ? new Error('Connection interrupted. Refresh to check your cart before trying again.') : error;
            this.errorListeners.forEach(listener => this.notify(listener, failure));
            throw failure;
        }
    }

    publish(snapshot) {
        const freeze = value => {
            if (value && typeof value === 'object') {
                Object.values(value).forEach(freeze);
                Object.freeze(value);
            }
            return value;
        };
        this.state = freeze(JSON.parse(JSON.stringify(snapshot)));
        this.listeners.forEach(listener => this.notify(listener, this.state));
    }

    notify(listener, value) {
        try { listener(value); } catch (error) { console.error('Cart listener failed.', error); }
    }

    dispose() {
        this.disposed = true;
        this.channel?.close();
        document.removeEventListener('visibilitychange', this.onVisible);
        window.removeEventListener('pageshow', this.onPageShow);
        this.listeners.clear();
        this.errorListeners.clear();
    }
}

window.CartStore = CartStore;
