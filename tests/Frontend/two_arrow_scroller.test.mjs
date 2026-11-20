import assert from 'node:assert/strict';
import test from 'node:test';

globalThis.document = { querySelectorAll: () => [] };
globalThis.window = { matchMedia: () => ({ matches: false }) };
const { TwoArrowScroller } = await import('../../resources/js/components/two_arrow_scroller.js');

function fixture(step = '0.5') {
    const listeners = {};
    const calls = [];
    const nodes = {
        '[data-scroll-content]': { clientWidth: 400, scrollWidth: 4000, scrollBy: call => calls.push(call) },
        '[data-scroll-previous]': { addEventListener: (_, callback) => { listeners.previous = callback; } },
        '[data-scroll-next]': { addEventListener: (_, callback) => { listeners.next = callback; } },
    };
    const element = { dataset: { scrollStep: step }, querySelector: selector => nodes[selector] };
    new TwoArrowScroller(element);
    return { listeners, calls };
}

test('each section scrolls only its own content by a fraction of the visible width', () => {
    const first = fixture();
    const second = fixture();
    first.listeners.next();
    assert.deepEqual(first.calls, [{ left: 200, behavior: 'smooth' }]);
    assert.deepEqual(second.calls, []);
    second.listeners.previous();
    assert.deepEqual(second.calls, [{ left: -200, behavior: 'smooth' }]);
});

test('reduced motion avoids animated scrolling and invalid steps use the default', () => {
    window.matchMedia = () => ({ matches: true });
    const section = fixture('invalid');
    section.listeners.next();
    assert.deepEqual(section.calls, [{ left: 268, behavior: 'auto' }]);
});

test('incomplete markup does not stop initialization of other sections', () => {
    assert.doesNotThrow(() => new TwoArrowScroller({ querySelector: () => null }));
});
