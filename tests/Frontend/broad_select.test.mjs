import assert from 'node:assert/strict';
import test from 'node:test';
import { initBroadSelect } from '../../resources/js/components/broad_select.js';

globalThis.CustomEvent = class extends Event {
    constructor(type, options) { super(type); this.detail = options.detail; }
};

function fixture() {
    const desktop = Object.assign(new EventTarget(), { matches: false });
    globalThis.window = { matchMedia: () => desktop };
    let focused;

    const buttons = ['collection', 'new', 'rated'].map((key, index) => {
        const attributes = { 'aria-selected': String(index === 0) };
        return Object.assign(new EventTarget(), {
            dataset: { broadSelectMode: key },
            focus() { focused = key; }, scrollIntoView() {},
            classList: { toggle() {} },
            getAttribute: key => attributes[key],
            setAttribute: (key, value) => { attributes[key] = value; },
        });
    });
    const panels = buttons.map((button, index) => {
        const events = [];
        const scroller = new EventTarget();
        scroller.addEventListener('carousel:visibility', event => events.push(event.detail.visible));
        return { dataset: { broadSelectPanel: button.dataset.broadSelectMode }, hidden: index !== 0,
            querySelector: () => scroller, events };
    });
    const pause = Object.assign(new EventTarget(), {
        pressed: 'false',
        getAttribute() { return this.pressed; },
        setAttribute(key, value) { this.pressed = value; },
    });
    const section = { querySelector: selector => selector.includes('pause') ? pause : null, querySelectorAll: selector => selector.includes('-mode') ? buttons : panels };
    return { buttons, panels, section, pause, desktop, focused: () => focused };
}

test('switches independent panels without replacing cards, sorting, or resetting rows', () => {
    const first = fixture(), second = fixture();
    initBroadSelect({ querySelectorAll: () => [first.section, second.section] });
    for (let index = 0; index < 20; index++) {
        const selected = index % 3;
        first.buttons[selected].dispatchEvent(new Event('click'));
        first.panels.forEach((panel, index) => assert.equal(panel.hidden, index !== selected));
        first.buttons.forEach((button, index) => assert.equal(button.getAttribute('aria-selected'), String(index === selected)));
    }
    assert.deepEqual(second.panels.map(panel => panel.hidden), [false, true, true]);
    assert.ok(first.panels.every(panel => panel.events.includes(false) && panel.events.includes(true)));
    const count = first.panels[1].events.length;
    first.buttons[1].dispatchEvent(new Event('click'));
    assert.equal(first.panels[1].events.length, count);
});

test('keyboard navigation wraps, follows orientation, and waits for explicit activation', () => {
    const f = fixture();
    initBroadSelect({ querySelectorAll: () => [f.section] });
    const key = (index, key) => {
        const event = new Event('keydown', { cancelable: true });
        event.key = key;
        f.buttons[index].dispatchEvent(event);
        return event;
    };
    assert.equal(key(0, 'ArrowLeft').defaultPrevented, true);
    assert.equal(f.focused(), 'rated');
    assert.equal(f.panels[0].hidden, false);
    key(2, 'Home');
    assert.equal(f.focused(), 'collection');
    key(0, 'End');
    assert.equal(f.focused(), 'rated');
    f.desktop.matches = true;
    key(2, 'ArrowDown');
    assert.equal(f.focused(), 'collection');
    f.buttons[1].dispatchEvent(new Event('click'));
    assert.equal(f.panels[1].hidden, false);
});

test('pause preference is sent to every panel including hidden ones', () => {
    const f = fixture();
    const received = [];
    f.panels.forEach(panel => panel.querySelector().addEventListener('carousel:pause', event => received.push(event.detail.paused)));
    initBroadSelect({ querySelectorAll: () => [f.section] });
    f.pause.dispatchEvent(new Event('click'));
    assert.equal(f.pause.pressed, 'true');
    assert.deepEqual(received, [true, true, true]);
    f.pause.dispatchEvent(new Event('click'));
    assert.deepEqual(received, [true, true, true, false, false, false]);
});
