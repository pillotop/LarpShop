import assert from 'node:assert/strict';
import test from 'node:test';
import { CategoryMenu } from '../../resources/js/components/category_menu.js';

function fixture(selected = '') {
    const entries = ['Costumes', '<b>Equipment</b>'].map((name, index) => ({
        dataset: { categoryScope: `category-${index}`, categoryName: name, categoryDescription: `<script>${name}</script>` },
        classList: { add() {}, remove() {} },
        querySelector: () => ({ href: `https://example.test/categories/${index}` }),
    }));
    const name = {}, description = {}, link = {}, actions = { hidden: true };
    const changes = [];
    const scope = { value: selected, disabled: !selected, dispatchEvent: event => {
        changes.push({ type: event.type, bubbles: event.bubbles, value: scope.value, disabled: scope.disabled });
        return true;
    } };
    const label = {};
    let focusCount = 0, closeCount = 0;
    const form = { querySelector: selector => selector === '[data-category-scope]' ? scope : { focus: () => focusCount++ } };
    const trigger = { closest: () => form, querySelector: () => label, dataset: { allCategoriesLabel: 'All categories' } };
    const listeners = {};
    const nodes = {
        '[data-category-preview-name]': name,
        '[data-category-preview-description]': description,
        '[data-category-preview-link]': link,
        '[data-category-preview-actions]': actions,
    };
    const menu = {
        addEventListener: (type, handler) => { listeners[type] = handler; },
        querySelectorAll: () => entries,
        querySelector: selector => nodes[selector],
    };
    new CategoryMenu(menu, trigger, () => closeCount++);
    const target = (entry, action) => ({ closest: selector => selector === '[data-category-entry]' ? entry : selector === action ? {} : null });
    return { entries, name, description, link, actions, scope, label, listeners, target, changes, counts: () => [closeCount, focusCount] };
}

test('preview starts at restored scope and pointer/focus updates text safely without choosing scope', () => {
    const f = fixture('category-1');
    assert.equal(f.name.textContent, '<b>Equipment</b>');
    assert.equal(f.description.textContent, '<script><b>Equipment</b></script>');
    assert.equal(f.actions.hidden, false);
    f.listeners.pointerover({ target: f.target(f.entries[0]) });
    assert.equal(f.name.textContent, 'Costumes');
    assert.equal(f.link.href, 'https://example.test/categories/0');
    assert.equal(f.scope.value, 'category-1');
    f.listeners.focusin({ target: f.target(f.entries[1]) });
    assert.equal(f.name.textContent, '<b>Equipment</b>');
    assert.deepEqual(f.counts(), [0, 0]);
});

test('preview and per-entry actions set scope, close and focus search; reset disables the filter', () => {
    const f = fixture();
    f.listeners.click({ target: f.target(null, '[data-category-preview-select]') });
    assert.equal(f.scope.value, 'category-0');
    assert.equal(f.scope.disabled, false);
    f.listeners.click({ target: f.target(f.entries[1], '[data-category-select]') });
    assert.equal(f.scope.value, 'category-1');
    assert.equal(f.label.textContent, '<b>Equipment</b>');
    f.listeners.click({ target: f.target(null, '[data-category-reset]') });
    assert.equal(f.scope.value, '');
    assert.equal(f.scope.disabled, true);
    assert.equal(f.label.textContent, 'All categories');
    assert.deepEqual(f.counts(), [3, 3]);
    assert.deepEqual(f.changes, [
        { type: 'change', bubbles: true, value: 'category-0', disabled: false },
        { type: 'change', bubbles: true, value: 'category-1', disabled: false },
        { type: 'change', bubbles: true, value: '', disabled: true },
    ]);
});

test('category navigation links do not select scope or close the menu programmatically', () => {
    const f = fixture();
    f.listeners.click({ target: f.target(f.entries[1]) });
    assert.equal(f.scope.disabled, true);
    assert.deepEqual(f.counts(), [0, 0]);
});
