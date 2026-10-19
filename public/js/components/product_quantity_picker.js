class ProductQuantityPicker {
    constructor(element) {
        this.element = element;

        this.cartStoreUrl = this.element.dataset.addToCartUrl;
        this.cartUpdateUrl = this.element.dataset.cartUpdateUrl;
        this.productId = this.element.dataset.productId;

        this.csrfToken = document.querySelector("meta[name='csrf_token']").content;

        this.addToCartButton = this.element.querySelector("#add-to-cart");
        this.counter = this.element.querySelector("counter");
        this.incrementButton = this.element.querySelector("increment");
        this.decrementButton = this.element.querySelector("decrement");

        this.#setupSignals();
    }

    #setupSignals() {
        this.counter.addEventListener("change", () => {
            this.#updateQuantity(this.counter.value);
        });

        this.counter.addEventListener("keydown", () => {
            this.#updateQuantity(this.counter.value);
        });

        this.addToCartButton.addEventListener("click", () => {
            // this looks dangerous for some reason
            this.#updateQuantity(1);
        });

        this.incrementButton.addEventListener("click", () => {
            this.#incrementCount();
        });

        this.decrementButton.addEventListener("click", () => {
            this.#decrementCount();
        });
    }

    #incrementCount() {
        this.#updateQuantity(this.counter.value + 1);
    }

    #decrementCount() {
        this.#updateQuantity(this.counter.value - 1);
    }

    async #updateQuantity(value) {
        try {
            const result = await this.#postJson(this.cartUpdateUrl, "POST", {
                product_id: this.productId,
                quantity: value,
            });

            this.counter.value = result.quantity;
        } catch (error) {
        }
    }

    async #postJson(url, method, data) {
        const response = fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': this.csrfToken,
            },
            body: JSON.stringify(data),
        });

        const result = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(
                result.error ?? 'An error happened'
            );
        }

        return result;
    }
}

document.querySelectorAll(".quantity_picker").forEach(element => new ProductQuantityPicker(element));
