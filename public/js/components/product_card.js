class ProductCard {
    constructor(element) {
        this.element = element;
        this.cardImage = element.querySelector(".card-img-top");
        this.cardBody = element.querySelector(".card-body");

        if (this.cardImage != null) {
            this.cardImageOverlay = this.cardImage.querySelector(".overlay");
            if (this.cardImageOverlay != null) {
                this.cardImageOverlay.style.visibility = "hidden";
            }
        }

        if (this.cardBody != null) {
            this.cardBodyOverlay = this.element.querySelector(".card-body-overlay");
            if (this.cardBodyOverlay != null) {
                this.cardBodyOverlay.style.visibility = "hidden";
            }
        }

        this.#setupSignals();
    }

    #setupSignals() {
        if (this.cardImageOverlay != null) {
            this.cardImage.addEventListener("mouseenter", () => {
                this.cardImageOverlay.style.visibility = "visible";
            });

            this.cardImage.addEventListener("mouseleave", () => {
                this.cardImageOverlay.style.visibility = "hidden";
            });

            this.cardImageOverlay.addEventListener("mouseenter", () => {
                this.cardImageOverlay.style.visibility = "visible";
            });
        }

        if (this.cardBodyOverlay != null) {
            this.cardBody.addEventListener("mouseenter", () => {
                this.cardBodyOverlay.style.visibility = "visible";
            });

            this.cardBody.addEventListener("mouseleave", () => {
                this.cardBodyOverlay.style.visibility = "hidden";
            });

            this.cardBodyOverlay.addEventListener("mouseenter", () => {
                this.cardBodyOverlay.style.visibility = "visible";
            });
        }
    }
}

document.querySelectorAll(".product-card").forEach((element) => {
    new ProductCard(element);
})
