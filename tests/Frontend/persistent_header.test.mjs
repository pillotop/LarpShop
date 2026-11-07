import assert from 'node:assert/strict';
import { test } from 'node:test';
import { PersistentHeader } from '../../resources/js/components/persistent_header.js';

function header() {
    const frames = [];
    const listeners = {};
    const elementListeners = {};
    const writes = [];
    const style = () => new Proxy({}, {
        set(target, key, value) {
            writes.push(key);
            target[key] = value;
            return true;
        },
    });
    const primary = { style: style() };
    const anchor = {
        style: style(),
        querySelector: () => primary,
        getBoundingClientRect: () => ({ top: Math.max(-200, 100 - window.scrollY) }),
    };
    const element = {
        parentElement: anchor,
        dataset: {},
        style: style(),
        offsetHeight: 72,
        addEventListener: (name, callback) => { elementListeners[name] = callback; },
    };
    globalThis.window = {
        scrollY: 120,
        innerHeight: 800,
        addEventListener: (name, callback) => { listeners[name] = callback; },
    };
    globalThis.document = { documentElement: { scrollHeight: 3000 } };
    globalThis.getComputedStyle = target => target === anchor
        ? { position: 'sticky', top: '0px' }
        : { marginTop: '8px', marginBottom: '0px' };
    globalThis.ResizeObserver = class {
        observe() {}
    };
    globalThis.requestAnimationFrame = callback => { frames.push(callback); return frames.length; };
    const instance = new PersistentHeader(element);
    const flush = () => { while (frames.length) frames.shift()(); };
    flush();
    const scroll = value => {
        window.scrollY = value;
        listeners.scroll();
        flush();
    };
    return { instance, anchor, element, primary, writes, frames, listeners, elementListeners, scroll, flush };
}

test('crossing the scroll threshold applies the remaining delta once', () => {
    const fixture = header();
    fixture.scroll(140);
    assert.equal(fixture.instance.visible, 76);
    assert.equal(fixture.anchor.style.top, '-4px');
    assert.equal(fixture.primary.style.transform, 'translateY(4px)');
});

test('scroll updates preserve the flow height and coalesce repeated events', () => {
    const fixture = header();
    window.scrollY = 200;
    fixture.listeners.scroll();
    fixture.listeners.scroll();
    assert.equal(fixture.frames.length, 1);
    fixture.flush();
    assert.equal(window.scrollY, 200);
    assert.equal(fixture.writes.includes('height'), false);
    assert.equal(fixture.writes.includes('position'), false);
    const writes = fixture.writes.length;
    fixture.listeners.scroll();
    fixture.flush();
    assert.equal(fixture.writes.length, writes);
});

test('reversing direction reveals smoothly after the dead zone', () => {
    const fixture = header();
    fixture.scroll(300);
    assert.equal(fixture.instance.visible, 0);
    fixture.scroll(290);
    assert.equal(fixture.instance.visible, 0);
    fixture.scroll(280);
    assert.equal(fixture.instance.visible, 4);
    fixture.scroll(0);
    assert.equal(fixture.instance.visible, 80);
    assert.equal(fixture.primary.style.transform, '');
});

test('overscroll is clamped instead of creating a false direction reversal', () => {
    const fixture = header();
    fixture.scroll(2200);
    assert.equal(fixture.instance.visible, 0);
    fixture.scroll(2250);
    fixture.scroll(2200);
    assert.equal(fixture.instance.visible, 0);
    fixture.scroll(-20);
    assert.equal(fixture.instance.visible, 80);
});


test('resize preserves a collapsed bar without changing the anchor height', () => {
    const fixture = header();
    fixture.scroll(300);
    fixture.element.offsetHeight = 112;
    fixture.listeners.resize();
    fixture.flush();
    assert.equal(fixture.instance.visible, 0);
    assert.equal(fixture.anchor.style.top, '-120px');
    assert.equal(fixture.writes.includes('height'), false);
});

test('keyboard focus reveals the secondary navigation', () => {
    const fixture = header();
    fixture.scroll(300);
    fixture.elementListeners.focusin();
    fixture.flush();
    assert.equal(fixture.instance.visible, 80);
    assert.equal(fixture.anchor.style.top, '0px');
});
