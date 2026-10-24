class SidePanel {
    constructor(element) {
        this.element = element;
        // this.header = element.querySelector("#side-panel-header");
        // this.body = element.querySelector("#side-panel-body");

        this.triggers = [];

        this.#setupTriggers();
        this.#setupSignals();
    }

    #setupTriggers() {
        const triggers = document.querySelectorAll(".side-panel-trigger");

        for (const trigger of triggers) {
            const targetTemplateSelector = trigger.dataset.sidePanelTarget;
            if (!targetTemplateSelector) {
                throw new Error('Missing data-side-panel-target attribute on trigger.');
            }

            const template = document.querySelector(targetTemplateSelector);
            if (!template) {
                console.warn(`Template not found for selector: ${targetTemplateSelector}`);
            }

            const entry = {
                trigger: trigger,
                template: template,
            }

            this.triggers.push(entry);
        }
    }

    #setupSignals() {
        for (const entry of this.triggers) {
            entry.trigger.addEventListener("click", () => {
                if (entry.template) {
                    this.element.dataset.activeSidePanel = entry.trigger.dataset.sidePanelTarget;
                    this.element.innerHTML = entry.template.innerHTML;
                }
            });
        }
    }
}

const singleton = document.querySelector("#side-panel");
if (singleton) {
    new SidePanel(singleton);
}
