import '../app.js';
import { Tab } from 'bootstrap';
import { showProfileTab } from './profile_tab_navigation.js';

function initialize() {
    const root = document.querySelector('[data-profile-tabs]');
    if (!root) return;

    const show = button => Tab.getOrCreateInstance(button).show();
    const showHash = () => showProfileTab(root, show, { hash: window.location.hash });

    // Validation and save feedback take priority over a stale URL fragment.
    if (!showProfileTab(root, show, { id: root.dataset.initialTab })) showHash();
    window.addEventListener('hashchange', showHash);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize, { once: true });
} else {
    initialize();
}
