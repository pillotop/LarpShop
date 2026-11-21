// Membership and ordering come from the server; switching preserves each row's state.
export function initBroadSelect(root = document) {
    root.querySelectorAll('[data-broad-select]').forEach(section => {
        const buttons = [...section.querySelectorAll('[data-broad-select-mode]')];
        const panels = [...section.querySelectorAll('[data-broad-select-panel]')];
        const tablist = section.querySelector('[role="tablist"]');
        const desktop = window.matchMedia('(min-width: 768px)');
        const orientation = () => tablist?.setAttribute('aria-orientation', desktop.matches ? 'vertical' : 'horizontal');
        orientation();
        desktop.addEventListener('change', orientation);
        const pause = section.querySelector('[data-broad-select-pause]');
        pause?.addEventListener('click', () => {
            const paused = pause.getAttribute('aria-pressed') !== 'true';
            pause.setAttribute('aria-pressed', String(paused));
            panels.forEach(panel => panel.querySelector('.carousel-scroller')?.dispatchEvent(
                new CustomEvent('carousel:pause', { detail: { paused } }),
            ));
        });

        function select(button) {
            const selected = panels.find(panel => panel.dataset.broadSelectPanel === button.dataset.broadSelectMode);
            if (!selected) return;

            buttons.forEach(other => {
                const active = other === button;
                other.classList.toggle('is-active', active);
                other.setAttribute('aria-selected', String(active));
                other.tabIndex = active ? 0 : -1;
            });
            panels.forEach(panel => {
                const active = panel === selected;
                panel.hidden = !active;
                panel.querySelector('.carousel-scroller')?.dispatchEvent(new CustomEvent('carousel:visibility', {
                    detail: { visible: active },
                }));
            });
        }

        buttons.forEach((button, index) => {
            button.addEventListener('click', () => {
                if (button.getAttribute('aria-selected') !== 'true') select(button);
            });
            // Manual activation: arrows move focus; Enter/Space activate the focused tab.
            button.addEventListener('keydown', event => {
                const previous = desktop.matches ? 'ArrowUp' : 'ArrowLeft';
                const next = desktop.matches ? 'ArrowDown' : 'ArrowRight';
                let target;
                if (event.key === previous) target = (index - 1 + buttons.length) % buttons.length;
                if (event.key === next) target = (index + 1) % buttons.length;
                if (event.key === 'Home') target = 0;
                if (event.key === 'End') target = buttons.length - 1;
                if (target === undefined) return;
                event.preventDefault();
                buttons.forEach((other, position) => { other.tabIndex = position === target ? 0 : -1; });
                buttons[target].focus({ preventScroll: true });
                buttons[target].scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'instant' });
            });
        });
    });
}
