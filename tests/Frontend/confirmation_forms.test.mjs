import assert from 'node:assert/strict';
import test from 'node:test';

let submit;
let confirmed;
let accepted;
globalThis.document = { addEventListener: (name, callback) => { submit = callback; } };
globalThis.window = {
    confirm: message => { confirmed = message; return accepted; },
};
await import('../../resources/js/components/confirmation_forms.js');

function dispatch(form) {
    let prevented = false;
    submit({
        target: { closest: () => form },
        preventDefault: () => { prevented = true; },
    });
    return prevented;
}

test('canceling a translated confirmation prevents submission', () => {
    accepted = false;
    assert.equal(dispatch({ dataset: { confirm: 'Xóa danh mục “Kiếm”? Có & không.' } }), true);
    assert.equal(confirmed, 'Xóa danh mục “Kiếm”? Có & không.');
});

test('accepting a confirmation preserves normal form submission', () => {
    accepted = true;
    assert.equal(dispatch({ dataset: { confirm: 'Delete this category?' } }), false);
});

test('unrelated forms submit without a confirmation', () => {
    confirmed = null;
    assert.equal(dispatch(null), false);
    assert.equal(confirmed, null);
});
