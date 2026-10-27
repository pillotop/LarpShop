(() => {
    document.querySelectorAll('[data-payment-methods]').forEach((section) => {
        const tabs = [...section.querySelectorAll('[role="tab"]')];
        const panels = [...section.querySelectorAll('[role="tabpanel"]')];

        const activate = (selected) => {
            tabs.forEach((tab) => {
                const active = tab === selected;
                tab.classList.toggle('active', active);
                tab.setAttribute('aria-selected', String(active));
                tab.tabIndex = active ? 0 : -1;
            });

            // Keep each panel mounted so its inputs retain their current values.
            panels.forEach((panel) => {
                panel.hidden = panel.id !== selected.getAttribute('aria-controls');
            });
        };

        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => activate(tab));
            tab.addEventListener('keydown', (event) => {
                let next;
                if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
                if (event.key === 'ArrowLeft') next = (index - 1 + tabs.length) % tabs.length;
                if (event.key === 'Home') next = 0;
                if (event.key === 'End') next = tabs.length - 1;
                if (next === undefined) return;

                event.preventDefault();
                activate(tabs[next]);
                tabs[next].focus();
            });
        });
    });
})();
