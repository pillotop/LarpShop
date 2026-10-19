function defineEdges(dir) {
    switch (dir) {
    case "right":
        return {start: "left", end: "right"};
    case "left":
        return {start: "right", end: "left"};
    case "up":
        return {start: "bottom", end: "top"};
    case "down":
        return {start: "top", end: "bottom"};
    default:
        throw Error("carousel scrollable direction must be one of `left`, `right`, `up` or `down`");
    }
}

function defineEdgesWithAxis(axis, dir) {
    if (axis === "horizontal") {
        return defineEdges(dir == 1 ? "right" : "left");
    }
    return defineEdges(dir == 1 ? "down" : "up");
}

function defineCalculationProperties(dir) {
    switch (dir) {
    case "right":
    case "left":
        return {
            offsetDimension: "offsetWidth",
            marginStart: "marginLeft",
            marginEnd: "marginRight",
            gap: "columnGap",
        };
    case "up":
    case "down":
        return {
            offsetDimension: "offsetHeight",
            marginStart: "marginTop",
            marginEnd: "marginBottom",
            gap: "rowGap",
        };
    default:
        throw Error("carousel scrollable direction must be one of `left`, `right`, `up` or `down`");
    }
}


class Carousel {
    constructor(element) {
        this.element = element;
        this.scroller = this.element.querySelector(".carousel-scroller");

        this.direction = this.element.dataset.carouselDirection ?? "right";
        this.autoplaySpeed = Number(this.element.dataset.carouselAutoplaySpeed ?? "0");

        this.#initState();
        this.#setupSignals();
        if (this.autoplaySpeed != 0) {
            this.#setupAutoplaySignals();
            this.#startAutoplay();
        }
    }

    #initState() {
        const edges = defineEdges(this.direction);
        const calculationProperties = defineCalculationProperties(this.direction);

        this.startEdge          = edges.start;
        this.endEdge            = edges.end;
        this.offsetDimension    = calculationProperties.offsetDimension;
        this.marginStart        = calculationProperties.marginStart;
        this.marginEnd          = calculationProperties.marginEnd;
        this.gap                = calculationProperties.gap;

        if (this.direction === "left" || this.direction === "right") {
            this.scrollProperty = "scrollLeft";
            this.scrollSpace = "scrollWidth";
        } else {
            this.scrollProperty = "scrollTop";
            this.scrollSpace = "scrollHeight";
        }

        this.state = {};
        this.state.running = false;
        this.state.recycle = this.#determinePolicy();
        this.state.axis = this.direction === "left" || this.direction === "right"
            ? "horizontal"
            : "vertical";

        this.#initScrollPosition();
        this.state.lastScroll = this.scroller[this.scrollProperty];
    }

    #setupAutoplaySignals() {
        this.scroller.addEventListener("mouseenter", () => {
            this.#stopAutoplay();
        });

        this.scroller.addEventListener("mouseleave", () => {
            if (this.autoplaySpeed != 0) {
                this.#startAutoplay();
            }
        });
    }

    #setupSignals() {
        this.scroller.addEventListener("scroll", () => {
            this.#requestScroll();
        });
    }

    #determinePolicy() {
        const children = [...this.scroller.children];
        const gap = parseFloat(getComputedStyle(this.scroller)[this.gap]) || 0;

        let sumDimen = 0;
        let maxDimen = null;
        for (const child of this.scroller.children) {
            const thisDimen = this.#totalDimension(child);
            sumDimen += thisDimen;
            if (maxDimen != null) {
                maxDimen = Math.max(maxDimen, thisDimen);
            } else {
                maxDimen = thisDimen;
            }
        }

        sumDimen += gap * Math.max(children.length - 1, 0);
        const offsetDimension = this.scroller[this.offsetDimension];

        // console.log(this.element.id + `: sumDimen vs offsetDimen: ${sumDimen} vs ${offsetDimension}`)

        if (sumDimen <= offsetDimension) {
            // no-opt policy
            // console.log(this.element.id + ": no op");
            return () => this.#noOp();
        } else /* if (sumDimen <= offsetDimension + maxDimen * 1.2) */ {
            // outer edge passed through threshold and inner edge outside viewport
            // console.log(this.element.id + ": deadzone");
            // return (context) => {
            //     context.deadZone = offsetDimension / 2 + maxDimen;
            //     this.#deadZoneShift(context);
            // }

            // react only when center selection shifted
            return (context) => this.#centerPointShift(context);
        } /* else {
            this.state.accumulatedScroll = 0;
            this.state.lastScrollDir = 0;
            console.log(this.element.id + ": budget");
            console.log(`gap is ${parseFloat(getComputedStyle(this.scroller)[this.gap]) || 0}`)
            return (delta, sign) => this.#scrollBudgetShift(delta, sign);

            // // react only when center selection shifted
            // this.state.lastCenterChild = {
            //     element: this.#getCenterChild(),
            // };
            // return (delta, sign) => this.#centerPointShift(delta, sign);
        } */
    }

    #requestScroll() {
        const currentScroll = this.scroller[this.scrollProperty];
        const delta = currentScroll - this.state.lastScroll;
        const dir = Math.sign(delta);
        // console.log(`currentScroll: ${currentScroll}, lastScroll: ${this.state.lastScroll}, delta: ${delta}`);

        this.state.lastScroll = currentScroll;

        if (dir != 0) {
            this.state.recycle({
                scroll: Math.abs(delta),
                dir: dir,
                recycleFunc: dir === 1
                    ? (child) => this.scroller.appendChild(child)
                    : (child) => this.scroller.insertBefore(child, this.scroller.firstElementChild),
            });
        }
        this.state.recycle(Math.abs(delta), Math.sign(delta));
    }

    #stopAutoplay() {
        this.state.running = false;
        cancelAnimationFrame(this.state.animationFrame);
    }

    #startAutoplay() {
        if (this.state.running) {
            return;
        }

        this.state.running = true;
        this.#animate();
    }

    #animate() {
        if (!this.state.running) {
            return;
        }

        this.scroller[this.scrollProperty] += this.autoplaySpeed;
        // this.state.lastScroll = this.scroller[this.scrollProperty];
        this.state.animationFrame = requestAnimationFrame(() => this.#animate());
    }

    #totalDimension(element) {
        const elementStyle = getComputedStyle(element);

        return element[this.offsetDimension]
            +  (parseFloat(elementStyle[this.marginStart]) || 0)
            +  (parseFloat(elementStyle[this.marginEnd])   || 0);
    }

    #noOp() { this.state.running = false; }

    #deadZoneShift(context) {
        const dir = context.dir;
        const children = [...this.scroller.children];
        const scrollerRect = this.scroller.getBoundingClientRect();
        const edges = defineEdgesWithAxis(this.state.axis, dir);
        const center = this.#getCenter(scrollerRect);
        const normalizedScroller = this.#normalizeEdges(scrollerRect, center);
        const gap = parseFloat(getComputedStyle(this.scroller)[this.gap]) || 0;

        const startIndex = dir > 0
            ? 0
            : this.scroller.children.length - 1;

        for (let i = startIndex; 0 <= i && i < children.length; i += dir) {
            const child = children[i];
            const childRect = child.getBoundingClientRect();
            const normalized = this.#normalizeEdges(childRect, center);

            if (
                Math.abs(normalized[edges.end])   > Math.abs(normalizedScroller[edges.start]) &&
                Math.abs(normalized[edges.start]) > context.deadZone
            ) {
                context.recycleFunc(child);
                this.scroller[this.scrollProperty] -= (dir * (this.#totalDimension(child) + gap));
                this.state.lastScroll = this.scroller[this.scrollProperty];
            }
            else {
                return;
            }
        }
    }

    #scrollBudgetShift(context) {
        console.log(this.state.accumulatedScroll);
        const children = [...this.scroller.children];
        const dir = context.dir;
        const startIndex = dir > 0
            ? 0
            : children.length - 1;

        // swapped this out with the below block to preserve scroll even after changin direction
        // if (this.state.lastScrollDir != dir) {
        //     this.state.accumulatedScroll = budget;
        //     this.state.lastScrollDir = dir;
        // } else {
        //     this.state.accumulatedScroll += budget;
        // }

        this.state.accumulatedScroll += scroll;
        if (this.state.lastScrollDir != dir) {
            this.state.lastScrollDir = dir;
            return;
        }

        const gap = parseFloat(getComputedStyle(this.scroller)[this.gap]) || 0;

        for (let i = startIndex; 0 <= i && i < children.length; i += dir) {
            const child = children[i];
            const totalDimen = this.#totalDimension(child) + gap;
            if (this.state.accumulatedScroll < totalDimen) {
                return;
            }

            context.recycleFunc(child);
            this.state.accumulatedScroll -= totalDimen;
            this.scroller[this.scrollProperty] -= (dir * totalDimen);
            this.state.lastScroll = this.scroller[this.scrollProperty];
        }
    }

    #centerPointShift(context) {
        const dir = context.dir;

        if (dir === 0 || context.scroll <= 0) return;

        const scroller = this.scroller;
        const scrollProperty = this.scrollProperty;
        const children = [...scroller.children];

        if (children.length < 2) return;

        const rect = scroller.getBoundingClientRect();
        const center = this.#getCenter(rect);
        const horizontal = this.state.axis === "horizontal";
        const start = horizontal ? "left" : "top";
        const end = horizontal ? "right" : "bottom";
        const gap = parseFloat(getComputedStyle(scroller)[this.gap]) || 0;

        const previousElement = this.state.lastCenterChild?.element;
        const previousIndex = children.indexOf(previousElement);

        let centerChild = null;
        let centerIndex = -1;
        let minDistance = Infinity;
        let centerPosition = 0;

        for (let i = 0; i < children.length; i++) {
            const child = children[i];
            const childRect = child.getBoundingClientRect();

            const distance =
                center < childRect[start]
                    ? childRect[start] - center
                    : center > childRect[end]
                        ? center - childRect[end]
                        : 0;

            const position = (childRect[start] + childRect[end]) / 2;

            if (
                distance < minDistance ||
                (
                    distance === minDistance &&
                    (
                        (dir > 0 && position > centerPosition) ||
                        (dir < 0 && position < centerPosition)
                    )
                )
            ) {
                centerChild = child;
                centerIndex = i;
                minDistance = distance;
                centerPosition = position;
            }
        }

        if (!centerChild) return;

        if (previousIndex === -1) {
            const childRect = centerChild.getBoundingClientRect();

            this.state.lastCenterChild = {
                element: centerChild,
                index: centerIndex,
                [this.startEdge]: Math.abs(childRect[this.startEdge] - center),
                [this.endEdge]: Math.abs(childRect[this.endEdge] - center),
            };

            return;
        }

        const crossed = (centerIndex - previousIndex) * dir;

        if (crossed <= 0) {
            const childRect = centerChild.getBoundingClientRect();

            this.state.lastCenterChild = {
                element: centerChild,
                index: centerIndex,
                [this.startEdge]: Math.abs(childRect[this.startEdge] - center),
                [this.endEdge]: Math.abs(childRect[this.endEdge] - center),
            };

            return;
        }

        for (let i = 0; i < crossed; i++) {
            const outgoing = dir > 0
                ? scroller.firstElementChild
                : scroller.lastElementChild;

            if (!outgoing || outgoing === centerChild) break;

            const style = getComputedStyle(outgoing);
            const advance =
                outgoing[this.offsetDimension] +
                (parseFloat(style[this.marginStart]) || 0) +
                (parseFloat(style[this.marginEnd]) || 0) +
                gap;

            context.recycleFunc(outgoing);

            scroller[scrollProperty] -= dir * advance;
            this.state.lastScroll = scroller[scrollProperty];
        }

        const childRect = centerChild.getBoundingClientRect();

        this.state.lastCenterChild = {
            element: centerChild,
            index: [...scroller.children].indexOf(centerChild),
            [this.startEdge]: Math.abs(childRect[this.startEdge] - center),
            [this.endEdge]: Math.abs(childRect[this.endEdge] - center),
        };
    }

    #normalizeEdges(rect, center) {
        return {
            [this.startEdge]: rect[this.startEdge] - center,
            [this.endEdge]: rect[this.endEdge] - center,
        };
    }

    #initScrollPosition() {
        const viewport = this.scroller[this.offsetDimension];
        const scrollSpace = this.scroller[this.scrollSpace];
        this.scroller[this.scrollProperty] = Math.abs(scrollSpace - viewport) / 2;
    }

    #getCenter(rect) {
        return (
            this.direction === "left" ||
            this.direction === "right"
        )
            ? rect.left + rect.width / 2
            : rect.top + rect.height / 2;
    }
}

document.querySelectorAll(".carousel-scrollable").forEach((element) => {
    new Carousel(element);
});
