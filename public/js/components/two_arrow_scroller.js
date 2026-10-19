class TwoArrowScroller {
    constructor(element) {
        this.element = element;
        this.scroller = this.element.querySelector("#two-arrow-scrollable");

        this.arrowLeft = this.element.querySelector("#arrow-left");
        this.arrowRight = this.element.querySelector("#arrow-right");

        this.scrollStep = Number(this.element.dataset.scrollStep || "0.67");

        if (!this.arrowLeft || !this.arrowRight || !this.scroller) {
            throw Error("Two arrow scroller cannot be instantiated because one of the required element is missing");
        }

        this.#setupSignals();
    }

    #setupSignals() {
        this.arrowLeft.addEventListener("click", () => {
            const scrollWidth = this.scroller.scrollWidth;
            this.scroller.scrollLeft -= scrollWidth * this.scrollStep;
        });

        this.arrowRight.addEventListener("click", () => {
            const scrollWidth = this.scroller.scrollWidth;
            this.scroller.scrollLeft += scrollWidth * this.scrollStep;
        });
    }
}

document.querySelectorAll(".two-arrow-scroller").forEach(element => new TwoArrowScroller(element));
