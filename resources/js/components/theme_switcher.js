const storageKey = 'larpshop.theme';
const root = document.documentElement;
const systemTheme = window.matchMedia('(prefers-color-scheme: dark)');
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
let transitionTimer;
let preference = readPreference();

function readPreference() {
    try {
        const value = localStorage.getItem(storageKey);
        return ['light', 'dark'].includes(value) ? value : null;
    } catch {
        return null;
    }
}

function applyTheme(animate = false) {
    const theme = preference ?? (systemTheme.matches ? 'dark' : 'light');

    if (animate && root.dataset.bsTheme !== theme && !reducedMotion.matches) {
        clearTimeout(transitionTimer);
        root.classList.add('theme-changing');
        transitionTimer = setTimeout(() => root.classList.remove('theme-changing'), 300);
    }

    root.dataset.bsTheme = theme;
    document.querySelectorAll('[data-theme-switch]').forEach(input => {
        input.checked = theme === 'dark';
    });
}

document.addEventListener('change', event => {
    if (!event.target.matches('[data-theme-switch]')) return;

    preference = event.target.checked ? 'dark' : 'light';
    try {
        localStorage.setItem(storageKey, preference);
    } catch {
        // Keep the switch usable even when browser storage is unavailable.
    }
    applyTheme(true);
});

systemTheme.addEventListener('change', () => {
    if (preference === null) applyTheme(true);
});

window.addEventListener('storage', event => {
    if (event.key !== storageKey && event.key !== null) return;
    preference = readPreference();
    applyTheme(true);
});

applyTheme();
