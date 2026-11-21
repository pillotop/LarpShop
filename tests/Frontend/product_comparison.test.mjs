import assert from 'node:assert/strict';
import test from 'node:test';

globalThis.document = { addEventListener() {} };
let stored = '[]';
globalThis.sessionStorage = { getItem: () => stored };
const { syncProductComparison } = await import('../../resources/js/components/storefront_ux.js');

test('newly inserted product checkboxes restore existing comparison selections', () => {
    stored = JSON.stringify([{ id: 7 }, { id: '12' }]);
    const boxes = [7, 12, 31].map(id => ({ checked: false, getAttribute: () => String(id) }));
    syncProductComparison({ querySelectorAll: () => boxes });
    assert.deepEqual(boxes.map(box => box.checked), [true, true, false]);
    stored = 'invalid JSON';
    syncProductComparison({ querySelectorAll: () => boxes });
    assert.deepEqual(boxes.map(box => box.checked), [false, false, false]);
});
