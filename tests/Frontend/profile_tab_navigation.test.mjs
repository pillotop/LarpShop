import assert from 'node:assert/strict';
import test from 'node:test';
import { showProfileTab } from '../../resources/js/components/profile_tab_navigation.js';

function fixture() {
    const definitions = [
        ['tab-customer-info', '#pane-account'],
        ['tab-vendor-info', '#pane-vendor'],
        ['tab-purchases', '#pane-purchases'],
        ['tab-wishlist', '#pane-wishlist'],
        ['tab-profile-details', '#pane-customer', '#accountTabs'],
        ['tab-billing', '#pane-billing', '#accountTabs'],
        ['tab-security', '#pane-security', '#accountTabs'],
        ['tab-vendor-12', '#pane-vendor-12', '#vendorTabs'],
    ];
    const buttons = definitions.map(([id, target, group]) => ({
        id,
        dataset: { bsTarget: target },
        closest: selector => selector === group ? {} : null,
    }));
    const shown = [];
    const root = { querySelectorAll: () => buttons };
    const open = options => showProfileTab(root, button => shown.push(button.id), options);
    return { shown, open };
}

for (const [pane, tab] of [['customer', 'profile-details'], ['billing', 'billing'], ['security', 'security']]) {
    test(`existing #pane-${pane} links open Account before its child`, () => {
        const f = fixture();
        assert.equal(f.open({ hash: `#pane-${pane}` }), true);
        assert.deepEqual(f.shown, ['tab-customer-info', `tab-${tab}`]);
    });
}

test('validation selects the requested child even when the URL points elsewhere', () => {
    const f = fixture();
    f.open({ id: 'tab-security', hash: '#pane-billing' });
    assert.deepEqual(f.shown, ['tab-customer-info', 'tab-security']);
});

test('vendor deep links continue to open the Stores parent', () => {
    const f = fixture();
    f.open({ hash: '#pane-vendor-12' });
    assert.deepEqual(f.shown, ['tab-vendor-info', 'tab-vendor-12']);
});

test('top-level tabs stay independent of Account and unknown fragments are ignored', () => {
    const f = fixture();
    f.open({ hash: '#pane-purchases' });
    assert.deepEqual(f.shown, ['tab-purchases']);
    assert.equal(f.open({ hash: '#not-a-tab' }), false);
    assert.deepEqual(f.shown, ['tab-purchases']);
});
