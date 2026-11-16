import '../app.js';

class SubcategoryContainer {
    constructor(element) {
        this.element = element;
        this.subcategoryList = element.querySelector("#subcategory-list");
        this.injectionPoint = document.querySelector("[data-subcategory-container-injection-point]");

        this.setupSignals();
    }

    setupSignals() {
        for (let i = 0; i < this.subcategoryList.children.length; i++) {
            let child = this.subcategoryList.children[i];

            let button = child.querySelector(
                "[data-subcategory-container-anchor-id]"
            );

            let template = this.element.querySelector(
                `[id="${button.dataset.subcategoryContainerAnchorId}"]`
            );

            let banner = template.content
                .cloneNode(true)
                .firstElementChild;

            if (i === 0 && this.injectionPoint && !this.injectionPoint.firstElementChild) {
                this.injectionPoint.appendChild(banner.cloneNode(true));
            }

            button.addEventListener("mouseenter", (_) => {
                if (!this.injectionPoint) return;
                this.injectionPoint.firstElementChild?.remove();
                this.injectionPoint.appendChild(banner.cloneNode(true));
            });
        }
    }
}

document.querySelectorAll(".subcategory-container")?.forEach((container) => {
    new SubcategoryContainer(container);
});
