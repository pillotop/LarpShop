class DropdownPortal {
    constructor() {
        this.portal = document.querySelector("#dropdown-portal");
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
            if (this.timeout) clearTimeout(this.timeout);
        });
        menu.addEventListener("mouseleave", () => {
            this.timeout = setTimeout(() => this.close(), 300);
        });

        this.portal.appendChild(menu);

        this.activeTrigger = trigger;
        this.activeMenu = menu;

        trigger.setAttribute("aria-expanded", "true");

        this.positionMenu(trigger, menu);
    }

    close() {
        if (!this.activeTrigger) {
            return;
        }

        this.activeTrigger.setAttribute(
            "aria-expanded",
            "false"
        );

        this.activeMenu?.remove();

        this.activeTrigger = null;
        this.activeMenu = null;
    }

    positionMenu(trigger, menu) {
        const triggerRect = trigger.getBoundingClientRect();

        menu.style.position = "fixed";
        menu.style.top = `${triggerRect.bottom}px`;
        menu.style.left = `${triggerRect.left}px`;
    }
}

new DropdownPortal();
