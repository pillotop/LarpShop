// Open a nested tab's parent first, including links using the existing pane IDs.
export function showProfileTab(root, show, { id = '', hash = '' } = {}) {
    if (!id && hash === '#pane-vendor-new') hash = '#pane-vendor';
    const triggers = Array.from(root.querySelectorAll('[data-bs-toggle="tab"]'));
    const trigger = triggers.find(button => id ? button.id === id : button.dataset.bsTarget === hash);
    if (!trigger) return false;

    const parentId = trigger.closest('#accountTabs')
        ? 'tab-customer-info'
        : trigger.closest('#vendorTabs') ? 'tab-vendor-info' : null;
    if (parentId) {
        const parent = triggers.find(button => button.id === parentId);
        if (parent) show(parent);
    }
    show(trigger);

    return true;
}
