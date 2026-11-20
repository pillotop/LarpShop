import assert from 'node:assert/strict';
import test from 'node:test';
import { CategoryPreview } from '../../resources/js/components/category_preview.js';

function fixture(desktop = true, reducedMotion = false) {
    const media = [desktop, reducedMotion].map(matches => ({
        matches, listeners: new Set(),
        addEventListener(_, listener) { this.listeners.add(listener); },
        removeEventListener(_, listener) { this.listeners.delete(listener); },
    }));
    globalThis.window = { matchMedia: query => media[query.includes('64rem') ? 0 : 1] };
    const node = () => ({
        children: [], listeners: {},
        append(child) { this.children.push(child); },
        replaceChildren() { this.children = []; },
        addEventListener(type, listener) { this.listeners[type] = listener; },
    });
    globalThis.document = { createElement: node };
    const slides = node(), controls = {}, listeners = new Set(), instances = [];
    const element = {
        dataset: { placeholder: '/images/placeholder.svg' },
        querySelector: selector => selector.includes('slides') ? slides : controls,
        addEventListener: (_, listener) => listeners.add(listener),
        removeEventListener: (_, listener) => listeners.delete(listener),
    };
    class Carousel {
        constructor(_, config) { this.config = config; instances.push(this); }
        dispose() { this.disposed = true; }
        pause() { this.paused = true; }
    }
    const preview = new CategoryPreview(element, Carousel);
    const images = [{ url: '/first.jpg', alt: '<b>First</b>' }, { url: '/second.jpg', alt: 'Second' }];
    return { preview, slides, controls, instances, media, listeners, images };
}

test('multiple images initialize Bootstrap; changing category disposes the old carousel', () => {
    const f = fixture();
    f.preview.show(f.images);
    assert.equal(f.controls.hidden, false);
    assert.equal(f.slides.children.length, 2);
    assert.equal(f.slides.children[0].children[0].alt, '<b>First</b>');
    assert.equal(f.instances[0].config.autoplay, true);
    for (const listener of f.listeners) listener();
    assert.equal(f.instances[0].paused, true);
    f.preview.show([f.images[1]]);
    assert.equal(f.instances[0].disposed, true);
    assert.equal(f.controls.hidden, true);
    assert.equal(f.slides.children.length, 1);
});

test('mobile avoids creating images; breakpoint changes render and dispose safely', () => {
    const f = fixture(false);
    f.preview.show(f.images);
    assert.equal(f.slides.children.length, 0);
    assert.equal(f.instances.length, 0);
    f.media[0].matches = true;
    for (const listener of f.media[0].listeners) listener();
    assert.equal(f.slides.children.length, 2);
    f.media[0].matches = false;
    for (const listener of f.media[0].listeners) listener();
    assert.equal(f.instances[0].disposed, true);
    assert.equal(f.slides.children.length, 0);
    f.preview.destroy();
    assert.equal(f.media[0].listeners.size, 0);
    assert.equal(f.media[1].listeners.size, 0);
    assert.equal(f.listeners.size, 0);
});

test('reduced motion disables autoplay; absent or broken images use the placeholder', () => {
    const f = fixture(true, true);
    f.preview.show(f.images);
    assert.equal(f.instances[0].config.autoplay, false);
    const img = f.slides.children[0].children[0];
    img.listeners.error();
    assert.equal(img.src, '/images/placeholder.svg');
    f.preview.show([]);
    assert.equal(f.slides.children[0].children[0].src, '/images/placeholder.svg');
    assert.equal(f.controls.hidden, true);
});
