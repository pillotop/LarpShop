class DropdownPortal {
    constructor() {
        this.portal = document.querySelector("#dropdown-portal");
        if (!this.portal) {
            this.portal = document.createElement("div");
            this.portal.id = "dropdown-portal";
            document.body.appendChild(this.portal);
        }
        this.initializedTriggers = new WeakSet();

        this.activeTrigger = null;
        this.activeMenu = null;

        this.setupSignals();
    }

    discover = (event) => {
        const trigger = event.target.closest("[data-dropdown-trigger]");
        if (!trigger || this.initializedTriggers.has(trigger)) return;

        this.initTrigger(trigger);
    }

    initTrigger(trigger) {
        this.initializedTriggers.add(trigger);
        trigger.addEventListener("mouseenter", (_) => {
            if (this.timeout != null) {
                clearTimeout(this.timeout);
                this.timeout = null;
            }
            this.open(trigger);
        });
        trigger.addEventListener("mouseleave", (_) => {
            this.timeout = setTimeout(() => {
                this.close();
            }, 300);
        });
    }

    setupSignals() {
        document.addEventListener("pointerover", this.discover);

        document.addEventListener("click", (event) => {
            const trigger = event.target.closest(
                "[data-dropdown-trigger]"
            );

            if (trigger) {
                this.toggle(trigger);
                return;
            }

            if (
                this.activeMenu &&
                !this.activeMenu.contains(event.target)
            ) {
                this.close();
            }
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                this.close();
            }
        });
    }

    toggle(trigger) {
        if (this.activeTrigger === trigger) {
            this.close();
            return;
        }

        this.open(trigger);
    }

    open(trigger) {
        this.close();

        const name = trigger.dataset.dropdownTrigger;

        const template = document.querySelector(
            `[data-dropdown-template="${name}"]`
        );

        if (!template) {
            return;
        }

        const menu = template.content
            .cloneNode(true)
            .firstElementChild;

        menu.addEventListener("mouseenter", () => {
            if (this.timeout != null) {
                clearTimeout(this.timeout);
                this.timeout = null;
            }
        });
        menu.addEventListener("mouseleave", () => {
            this.timeout = setTimeout(() => this.close(), 300);
        });

        this.portal.appendChild(menu);

        this.activeTrigger = trigger;
        this.activeMenu = menu;

        trigger.setAttribute("aria-expanded", "true");

        this.positionMenu(trigger, menu);

        requestAnimationFrame(() => {
            menu.classList.add("visible");
        });
    }

    close() {
        if (!this.activeMenu) {
            return;
        }

        const menu = this.activeMenu;
        const trigger = this.activeTrigger;

        trigger.setAttribute("aria-expanded", "false");

        menu.classList.remove("visible");

        menu.addEventListener("transitionend", () => {
            if (menu.isConnected) {
                menu.remove();
            }
        }, { once: true });

        this.activeMenu = null;
        this.activeTrigger = null;
    }

    positionMenu(trigger, menu) {
        const triggerRect = trigger.getBoundingClientRect();

        menu.style.position = "fixed";
        menu.style.top = `${triggerRect.bottom}px`;
        menu.style.left = `${triggerRect.left}px`;
    }
}

if (Snap != null) {
    const dropdownArrows = document.querySelectorAll(".dropdown-arrow");
    dropdownArrows.forEach((arrow) => {
        let pe = arrow.parentElement;
        while (pe) {
            if (pe.tagName === "BUTTON")
                break;
            pe = pe.parentElement;
        }
        if (pe == null) return;

        const arrowPath = arrow.querySelector(".dropdown-arrow-path");
        const snapArrow = Snap(arrowPath);

        const closedPath = "M5.75 8.75 L10 13 L14.25 8.75";
        const openPath = "M5.75 13 L10 8.75 L14.25 13";
        let isOpen = false;

        pe.addEventListener("click", () => {
            isOpen = !isOpen;
            if (isOpen) {
                snapArrow.animate({d: closedPath}, 150);
            }
            else {
                snapArrow.animate({d: openPath}, 150);
            }
        });
        pe.addEventListener("mouseenter", () => snapArrow.animate({d: openPath}, 150));
        pe.addEventListener("mouseleave", () => snapArrow.animate({d: closedPath}, 150));
    });
}

new DropdownPortal();
