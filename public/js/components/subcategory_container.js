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

            button.addEventListener("mouseenter", (_) => {
                this.injectionPoint.firstElementChild?.remove();
                this.injectionPoint.appendChild(banner);
            });
        }
    }
}

document.querySelectorAll(".subcategory-container")?.forEach((container) => {
    new SubcategoryContainer(container);
});
