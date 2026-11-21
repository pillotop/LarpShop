import '../app.js';
import { autoUpdate, computePosition, flip, shift, size } from '@floating-ui/dom';
import { TwoArrowScroller } from './two_arrow_scroller.js';
import { CategoryMenu } from './category_menu.js';
import { CategoryPreview } from './category_preview.js';
import { Carousel } from 'bootstrap';

class DropdownPortal {
    constructor() {
        this.portal = document.querySelector("#dropdown-portal");
        if (!this.portal) {
            this.portal = document.createElement("div");
            this.portal.id = "dropdown-portal";
        }
        // Escape header stacking contexts so sticky page content cannot cover the menu.
        document.body.appendChild(this.portal);
        this.initializedTriggers = new WeakSet();

        this.activeTrigger = null;
        this.activeMenu = null;
        this.stopPositioning = null;

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
                if (!this.activeMenu?.contains(document.activeElement)) this.close();
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

        document.addEventListener('focusin', event => {
            if (this.activeMenu && !this.activeMenu.contains(event.target)
                && !this.activeTrigger.contains(event.target)) this.close();
        });

        document.addEventListener("keydown", (event) => {
            const trigger = event.target.closest('[data-dropdown-trigger]');
            if (trigger && event.key === 'ArrowDown') {
                event.preventDefault();
                this.open(trigger);
                this.activeMenu?.querySelector('[data-category-link]')?.focus();
            }
            if (event.key === "Escape") {
                const returnFocus = this.activeMenu?.contains(document.activeElement);
                const activeTrigger = this.activeTrigger;
                this.close();
                if (returnFocus) activeTrigger?.focus();
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

        const template = trigger.dataset.dropdownTemplateId
            ? document.getElementById(trigger.dataset.dropdownTemplateId)
            : document.querySelector(`[data-dropdown-template="${name}"]`);

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
            this.timeout = setTimeout(() => {
                if (!menu.contains(document.activeElement)) this.close();
            }, 300);
        });

        menu.style.position = "fixed";
        this.portal.appendChild(menu);
        menu.querySelectorAll('.two-arrow-scroller').forEach(element => new TwoArrowScroller(element));
        if (name === 'categories') {
            const images = menu.querySelector('[data-category-preview-images]');
            const preview = images ? new CategoryPreview(images, Carousel) : null;
            this.categoryMenu = new CategoryMenu(menu, trigger, () => this.close(), preview);
        }

        this.activeTrigger = trigger;
        this.activeMenu = menu;

        trigger.setAttribute("aria-expanded", "true");

        this.stopPositioning = autoUpdate(trigger, menu, () => this.positionMenu(trigger, menu));
    }

    close() {
        clearTimeout(this.timeout);
        this.timeout = null;
        this.categoryMenu?.destroy();
        this.categoryMenu = null;
        this.stopPositioning?.();
        this.stopPositioning = null;

        if (!this.activeMenu) {
            return;
        }

        const menu = this.activeMenu;
        const trigger = this.activeTrigger;

        trigger.setAttribute("aria-expanded", "false");

        menu.inert = true;
        menu.setAttribute('aria-hidden', 'true');
        menu.querySelectorAll('[id]').forEach(element => element.removeAttribute('id'));
        menu.classList.remove("visible");

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            menu.remove();
        } else {
            // A menu closed before its first paint may never emit transitionend.
            const removalTimeout = setTimeout(() => menu.remove(), 200);
            menu.addEventListener("transitionend", event => {
                if (event.target !== menu) return;
                clearTimeout(removalTimeout);
                menu.remove();
            });
        }

        this.activeMenu = null;
        this.activeTrigger = null;
    }

    async positionMenu(trigger, menu) {
        const { x, y } = await computePosition(trigger, menu, {
            strategy: "fixed",
            placement: "bottom-start",
            middleware: [
                flip({ padding: 0 }),
                shift({ padding: 0 }),
                size({
                    padding: 0,
                    apply: ({ availableHeight, availableWidth }) => {
                        if (this.activeMenu !== menu) return;
                        menu.style.setProperty('--dropdown-available-height', `${Math.max(0, availableHeight)}px`);
                        menu.style.setProperty('--dropdown-available-width', `${Math.max(0, availableWidth)}px`);
                    },
                }),
            ],
        });

        // An earlier calculation may finish after the menu has closed or changed.
        if (this.activeMenu !== menu) return;

        menu.style.top = `${y}px`;
        menu.style.left = `${x}px`;
        requestAnimationFrame(() => {
            if (this.activeMenu === menu) menu.classList.add("visible");
        });
    }
}

if (typeof Snap !== "undefined") {
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
