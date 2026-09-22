class Carousel {
    constructor(element) {
        this.element = element;
        this.scroller = element.querySelector("[data-carousel-scroller]");

        this.direction = element.dataset.carouselScrollableDirection ?? "right";

        const startEndValues = this.getStartEndEdges();
        this.startEdge = startEndValues.start;
        this.endEdge = startEndValues.end;

        this.autoplay = element.dataset.carouselScrollableAutoplay === "true";
        this.speed = Number(element.dataset.carouselScrollableSpeed ?? 1);

        this.running = false;

        this.scrollProperty = this.direction === "right" || this.direction === "left"
            ? "scrollLeft"
            : "scrollTop";

        this.setupSignals();

        if (this.autoplay) {
            this.start();
        }
    }

    getStartEndEdges() {
        switch (this.direction) {
            default:
            case "right":
                return {start: "left", end: "right"};
            case "left":
                return {start: "right", end: "left"};
            case "up":
                return {start: "bottom", end: "top"};
            case "down":
                return {start: "top", end: "bottom"};
        }
    }

    calculateOffset(element) {
        const axis = this.direction === "left" || this.direction === "right"
            ? "horizontal"
            : "vertical";

        const scheme = {
            horizontal: {
                offset: "offsetWidth",
                marginStart: "marginLeft",
                marginEnd: "marginRight",
                gap: "columnGap",
            },
            vertical: {
                offset: "offsetHeight",
                marginStart: "marginTop",
                marginEnd: "marginBottom",
                gap: "rowGap",
            },
        };

        const values = scheme[axis];
        const elementStyle = getComputedStyle(element);
        const scrollerStyle = getComputedStyle(this.scroller);

        const marginStart = parseFloat(elementStyle[values.marginStart]) || 0;
        const marginEnd = parseFloat(elementStyle[values.marginEnd]) || 0;
        const gap = parseFloat(scrollerStyle[values.gap]) || 0;

        return element[values.offset] + marginStart + marginEnd + gap;
    }

    setupSignals() {
        this.scroller.addEventListener("scroll", () => {
            this.updateLoop();
        })

        this.scroller.addEventListener("mouseenter", () => {
            this.stop();
        });

        this.scroller.addEventListener("mouseleave", () => {
            if (this.autoplay) {
                this.start();
            }
        });
    }

    stop() {
        this.running = false;
        cancelAnimationFrame(this.animationFrame);
    }

    start() {
        if (this.running) {
            return;
        }

        this.running = true;
        this.animate();
    }

    updateLoop() {
        const first = this.scroller.firstElementChild;
        const last = this.scroller.lastElementChild;

        if (!first || !last) {
            return;
        }

        const viewport = this.scroller.getBoundingClientRect();
        const firstRect = first.getBoundingClientRect();
        const lastRect = last.getBoundingClientRect();

        if (firstRect[this.endEdge] < viewport[this.startEdge]) {
            const offset = this.calculateOffset(first);

            this.scroller.appendChild(first);
            this.scroller[this.scrollProperty] -= offset;
        }

        if (lastRect[this.startEdge] > viewport[this.endEdge]) {
            const offset = this.calculateOffset(last);

            this.scroller.insertBefore(last, this.scroller.firstElementChild);
            this.scroller[this.scrollProperty] += offset;
        }
    }

    animate = () => {
        if (!this.running || this.speed == 0) {
            return;
        }

        const multiplier = this.direction === "left" ||
                           this.direction === "up"
            ? -1
            : 1;

        this.scroller[this.scrollProperty] += this.speed * multiplier;
        this.animationFrame = requestAnimationFrame(this.animate);
    };
}

document.querySelectorAll("[data-carousel-scrollable]").forEach((carouselScrollable) => {
    new Carousel(carouselScrollable);
})
