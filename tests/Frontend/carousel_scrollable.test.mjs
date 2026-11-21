import assert from 'node:assert/strict';
import test from 'node:test';
import { CarouselScrollable, advanceCarouselMotion } from '../../resources/js/components/carousel_scrollable_controller.js';

function fixture({ widths = [100, 100, 100, 100, 100], viewport = 180, speed = 60, rounding = false, hidden = false } = {}) {
    const frames = new Map();
    let nextFrame = 0, writes = 0, position = 0, moves = 0;
    const media = Object.assign(new EventTarget(), { matches: false });
    globalThis.window = Object.assign(new EventTarget(), { matchMedia: () => media });
    globalThis.document = Object.assign(new EventTarget(), { hidden: false, activeElement: null });
    globalThis.requestAnimationFrame = callback => { frames.set(++nextFrame, callback); return nextFrame; };
    globalThis.cancelAnimationFrame = id => frames.delete(id);
    const observers = [];
    globalThis.ResizeObserver = class {
        constructor(callback) { this.callback = callback; observers.push(this); }
        observe() {} unobserve() {} disconnect() {}
    };
    globalThis.MutationObserver = class {
        constructor(callback) { this.callback = callback; observers.push(this); }
        observe() {} disconnect() {}
    };
    globalThis.getComputedStyle = () => ({ columnGap: '10px', marginLeft: '0', marginRight: '0' });
    const items = widths.map((width, index) => ({
        index, width, getBoundingClientRect: () => ({ width }), contains: () => false,
    }));
    const scroller = Object.assign(new EventTarget(), {
        children: [...items], clientWidth: viewport,
        contains: () => false, closest: () => hidden ? {} : null, matches: () => false,
        appendChild(child) { this.children.splice(this.children.indexOf(child), 1); this.children.push(child); moves++; },
        insertBefore(child) { this.children.splice(this.children.indexOf(child), 1); this.children.unshift(child); moves++; },
    });
    Object.defineProperties(scroller, {
        scrollWidth: { get: () => scroller.children.reduce((sum, child) => sum + child.width, 0) + Math.max(0, scroller.children.length - 1) * 10 },
        scrollLeft: {
            get: () => position,
            set: value => { position = Math.max(0, Math.min(scroller.scrollWidth - scroller.clientWidth, rounding ? Math.round(value) : value)); writes++; },
        },
        firstElementChild: { get: () => scroller.children[0] },
        lastElementChild: { get: () => scroller.children.at(-1) },
    });
    const element = { dataset: { carouselAutoplaySpeed: String(speed) }, querySelector: () => scroller };
    const carousel = new CarouselScrollable(element);
    const frame = time => {
        const callbacks = [...frames.values()];
        frames.clear();
        callbacks.forEach(callback => callback(time));
    };
    return { carousel, scroller, items, frames, observers, media, frame, stats: () => ({ writes, moves, position }) };
}

function run(f, start, end, hz = 60) {
    for (let time = start; time <= end + 0.01; time += 1000 / hz) f.frame(time);
}

test('autoplay distance and easing are independent of 30/60/144 Hz', () => {
    const distances = [30, 60, 144].map(hz => {
        let velocity = 0, distance = 0;
        for (let step = 0; step < hz * 2; step++) {
            const motion = advanceCarouselMotion(velocity, 60, 1 / hz);
            velocity = motion.velocity;
            distance += motion.distance;
        }
        return distance;
    });
    for (const distance of distances) assert.ok(Math.abs(distance - distances[0]) < 1e-8);
});

test('fractional accumulation still moves at high refresh rates with integer DOM scroll values', () => {
    const f = fixture({ widths: [1000, 1000], rounding: true });
    run(f, 0, 2000, 144);
    assert.ok(f.stats().position > 100 && f.stats().position < 115);
    f.carousel.destroy();
});

test('recycling waits until an entire item has left view and compensates its width plus gap', () => {
    const f = fixture({ speed: 0 });
    f.frame(0);
    f.scroller.scrollLeft = 80;
    f.scroller.dispatchEvent(new Event('scroll'));
    f.frame(16);
    assert.equal(f.stats().moves, 0);
    f.scroller.scrollLeft = 115;
    f.scroller.dispatchEvent(new Event('scroll'));
    f.frame(32);
    assert.equal(f.stats().moves, 1);
    assert.equal(f.scroller.scrollLeft, 5);
    assert.equal(f.scroller.children[0], f.items[1]);
    f.carousel.destroy();
});

test('resize and repeated sort resets preserve supplied order and never recycle based on stale geometry', () => {
    const f = fixture();
    run(f, 0, 1000);
    for (let index = 0; index < 12; index++) {
        const expected = index % 2 ? [...f.items].reverse() : [...f.items];
        f.scroller.children = [...expected];
        f.scroller.clientWidth = index % 2 ? 260 : 180;
        f.scroller.dispatchEvent(new Event('carousel:reset'));
        f.observers.forEach(observer => observer.callback());
        f.frame(1100 + index * 16);
        assert.deepEqual(f.scroller.children, expected);
        assert.equal(f.scroller.scrollLeft, 0);
        assert.equal(f.frames.size, 1);
    }
    f.carousel.destroy();
});

test('hover eases speed down and leaving eases it back up; hidden tabs never catch up in one jump', () => {
    const f = fixture({ widths: [1000, 1000] });
    run(f, 0, 1000);
    f.scroller.dispatchEvent(new Event('mouseenter'));
    f.frame(1016);
    assert.ok(f.carousel.velocity > 0 && f.carousel.velocity < 60);
    run(f, 1032, 2600);
    assert.equal(f.carousel.velocity, 0);
    assert.equal(f.frames.size, 0);
    f.scroller.dispatchEvent(new Event('mouseleave'));
    f.frame(2700);
    f.frame(2716);
    assert.ok(f.carousel.velocity > 0 && f.carousel.velocity < 15);
    document.hidden = true;
    document.dispatchEvent(new Event('visibilitychange'));
    const before = f.scroller.scrollLeft;
    assert.equal(f.frames.size, 0);
    document.hidden = false;
    document.dispatchEvent(new Event('visibilitychange'));
    f.frame(100000);
    assert.equal(f.scroller.scrollLeft, before);
    f.carousel.destroy();
});

test('short overflow does not recycle; hidden/empty rows and reduced motion do not autoplay', () => {
    const f = fixture({ widths: [100, 100], viewport: 180 });
    run(f, 0, 3000);
    assert.equal(f.carousel.canRecycle, false);
    assert.equal(f.stats().moves, 0);
    f.media.matches = true;
    f.media.dispatchEvent(new Event('change'));
    f.frame(3016);
    const before = f.scroller.scrollLeft;
    f.frame(4000);
    assert.equal(f.scroller.scrollLeft, before);
    f.scroller.clientWidth = 0;
    f.observers[0].callback();
    f.frame(4016);
    assert.equal(f.frames.size, 0);
    f.carousel.destroy();
    const empty = fixture({ widths: [] });
    empty.frame(0);
    assert.equal(empty.frames.size, 0);
    empty.carousel.destroy();
});

test('reverse scrolling restores the same visible content with unequal item widths', () => {
    const f = fixture({ widths: [80, 120, 90, 100], speed: 0 });
    f.frame(0);
    f.scroller.scrollLeft = 95;
    f.scroller.dispatchEvent(new Event('scroll'));
    f.frame(16);
    assert.equal(f.scroller.scrollLeft, 5);
    f.scroller.scrollLeft = 0;
    f.scroller.dispatchEvent(new Event('scroll'));
    f.frame(32);
    const anchorIndex = f.scroller.children.indexOf(f.items[1]);
    const anchorOffset = f.scroller.children.slice(0, anchorIndex)
        .reduce((offset, child) => offset + child.width + 10, 0);
    assert.equal(anchorOffset - f.scroller.scrollLeft, 0);
    f.carousel.destroy();
});

test('inactive panels schedule no frames and resume with fresh geometry without a catch-up jump', () => {
    const f = fixture({ hidden: true });
    assert.equal(f.frames.size, 0);
    const visibility = visible => {
        const event = new Event('carousel:visibility');
        event.detail = { visible };
        f.scroller.dispatchEvent(event);
    };
    visibility(true);
    run(f, 0, 1000);
    visibility(false);
    const before = f.stats();
    f.observers.forEach(observer => observer.callback());
    run(f, 1000, 5000);
    assert.deepEqual(f.stats(), before);
    assert.equal(f.frames.size, 0);
    f.scroller.clientWidth = 300;
    visibility(true);
    f.frame(100000);
    assert.equal(f.scroller.scrollLeft, before.position);
    assert.equal(f.carousel.dirty, false);
    f.frame(100016);
    assert.ok(f.carousel.velocity > 0);
    f.carousel.destroy();
});

test('explicit pause persists across panel switches and resumes only on request', () => {
    const f = fixture({ widths: [1000, 1000] });
    run(f, 0, 1000);
    const send = (type, detail) => {
        const event = new Event(type);
        event.detail = detail;
        f.scroller.dispatchEvent(event);
    };
    send('carousel:pause', { paused: true });
    f.frame(1016);
    const before = f.scroller.scrollLeft;
    send('carousel:visibility', { visible: false });
    send('carousel:visibility', { visible: true });
    run(f, 2000, 4000);
    assert.equal(f.scroller.scrollLeft, before);
    assert.equal(f.frames.size, 0);
    send('carousel:pause', { paused: false });
    run(f, 4016, 4500);
    assert.ok(f.scroller.scrollLeft > before);
    f.carousel.destroy();
});
