import '../app.js';

class PersistentHeader {
    constructor(element) {
        this.anchor = this.getAnchor(element);
        this.container = element;
        this.content = this.anchor != null
            ? this.container
            : this.container.querySelector(".persistent-header-content");

        if (this.content === null)
            throw new Error("persistent header requires a .persistent-header-content");

        this.visible = this.maxVisible = 0;
        this.updateDimensions();
        this.visible = this.maxVisible;

        this.scrollThreshold = Number(element.dataset.persistentHeaderScrollThreshold ?? "16");
        this.lastScrollY = window.scrollY;

        this.initState();
        this.setupSignals();
    }

    initState() {
        this.scrollResistance = 0;
        this.duringScrollSession = false;

        this.resizeObserver = new ResizeObserver(() => {
            this.updateDimensions();
            this.updateVisibility();
            // console.log({
            //     maxVisible: this.maxVisible,
            //     visible: this.visible,
            //     anchorBaseHeight: this.anchorBaseHeight,
            //     anchorHeight: this.anchor?.offsetHeight
            // });
        });
        this.resizeObserver.observe(this.content);

        if (this.anchor != null) {
            this.setSticky = this.#hasAnchorSetSticky;
            this.setRelative = this.#hasAnchorSetRelative;
            this.isTopOrAbove = this.#hasAnchorIsTopOrAbove;

            this.updateAnchorDimensions();
            this.mutationObserver = new MutationObserver(() => {
                this.updateAnchorDimensions();
                this.updateVisibility();
            });
            this.mutationObserver.observe(this.anchor, {
                childList: true
            });

            this.state = this.isTopOrAbove()
                ? "sticky"
                : "relative";
            return;
        }

        this.setSticky = this.#noAnchorSetSticky;
        this.setRelative = this.#noAnchorSetRelative;
        this.isTopOrAbove = this.#noAnchorIsTopOrAbove;

        this.container.style.height = `${this.maxVisible}px`;

        this.state = this.isTopOrAbove()
            ? "sticky"
            : "relative";
    }

    setupSignals() {
        window.addEventListener("scroll", () => this.requestScroll());
        window.addEventListener("scrollend", () => this.endScroll());
    }

    requestScroll() {
        const scrollY = window.scrollY;
        const delta = this.lastScrollY - scrollY;

        if (!this.duringScrollSession) {
            this.scrollResistance += delta;
            if (Math.abs(this.scrollResistance) < this.scrollThreshold) {
                this.lastScrollY = scrollY;
                return;
            }
            this.duringScrollSession = true;
            this.react(delta);
        }

        this.react(delta);
        this.lastScrollY = scrollY;
    }

    endScroll() {
        this.scrollResistance = 0;
        this.duringScrollSession = false;
    }

    react(scroll) {
        if (!this.isTopOrAbove()) {
            this.setRelative();
            this.visible = this.maxVisible;
            return;
        }

        this.setSticky();
        this.visible = Math.max(
            Math.min(
                this.visible + scroll,
                this.maxVisible
            ), 0
        );

        this.updateVisibility();
    }

    updateVisibility() {
        this.content.style.transform =
            `translateY(${this.visible - this.maxVisible}px)`;

        if (this.anchor != null) {
            this.anchor.style.height =
                `${this.anchorBaseHeight + this.visible}px`;
        }
    }

    updateAnchorDimensions() {
        const previousHeight = this.anchor.style.height;

        this.anchor.style.height = "";

        const anchorHeight = this.anchor.offsetHeight;
        const headerHeight = this.content.offsetHeight;

        this.anchorBaseHeight = anchorHeight - headerHeight;

        this.anchor.style.height = previousHeight;
    }

    updateDimensions() {
        this.maxVisible = this.content.offsetHeight;
        this.visible = Math.min(
            this.visible,
            this.maxVisible
        );
    }

    getAnchor(element) {
        const parent = element.parentElement;

        if (
            parent &&
            getComputedStyle(parent).position === "sticky"
        ) {
            return parent;
        }

        return null;
    }

    #hasAnchorIsTopOrAbove = () => {
        return this.anchor.getBoundingClientRect().top <= 0;
    }

    #hasAnchorSetRelative = () => {
        if (this.state === "relative") return;
        this.state = "relative";

        this.content.style.transform = "";
    }

    #hasAnchorSetSticky = () => {
        if (this.state === "sticky") return;
        this.state = "sticky";
    }

    #noAnchorIsTopOrAbove = () => {
        return this.container.getBoundingClientRect().top <= 0;
    }

    #noAnchorSetRelative = () => {
        if (this.state === "relative") return;
        this.state = "relative";

        this.content.style.position = "relative";
        this.content.style.top = "";
        this.content.style.left = "";
        this.content.style.right = "";

        this.content.style.transform = "";
    }

    #noAnchorSetSticky = () => {
        if (this.state === "sticky") return;
        this.state = "sticky";

        this.content.style.position = "fixed";
        this.content.style.top = "0";
        this.content.style.left = "0";
        this.content.style.right = "0";
    }
}


document.querySelectorAll(".persistent-header").forEach((element) => {
    if (element.querySelector(".persistent-header-content"))
        new PersistentHeader(element);
});
