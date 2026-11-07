function mountLocalePanels() {
    document.querySelectorAll('[data-locale-panel-surface]').forEach(panel => {
        document.body.appendChild(panel);
    });
}

// Mount once, so the browser paints the closed state before Bootstrap opens a drawer.
// Reparenting during the opening click discards that state and skips the transition.
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', mountLocalePanels, { once: true });
} else {
    mountLocalePanels();
}
