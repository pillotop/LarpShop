import assert from 'node:assert/strict';
import test from 'node:test';
import { DeferredSection } from '../../resources/js/components/deferred_section.js';

function fixture(fetchPage, extra = {}) {
    const attributes = new Map();
    const loading = { hidden: true };
    const error = { hidden: true };
    const retry = Object.assign(new EventTarget(), { disabled: false });
    let inserted, initialized = 0, focused = false;
    const content = {
        setAttribute: (name, value) => attributes.set(name, value),
        removeAttribute: name => attributes.delete(name),
        replaceChildren: value => { inserted = value; },
        querySelector: () => ({ focus() { focused = true; } }),
    };
    const elements = { '[data-loading-state]': loading, '[data-deferred-error]': error,
        '[data-deferred-retry]': retry, '[data-deferred-content]': content };
    const element = { dataset: { source: '/home/broad-select' }, hidden: false,
        querySelector: selector => elements[selector] };
    const fragment = { querySelector: () => ({}) };
    const page = { activeElement: null, createElement: () => ({ content: fragment }) };
    const loader = new DeferredSection(element, {
        fetchPage, page, initialize: () => { initialized++; }, ...extra,
    });
    return { loader, element, loading, error, retry, page, fragment, attributes,
        inserted: () => inserted, initialized: () => initialized, focused: () => focused };
}
const success = () => ({ ok: true, status: 200, text: async () => '<section data-deferred-fragment></section>' });

test('waits until near the viewport and loads once with same-origin session context', async () => {
    let notify, observed = false, disconnected = false, complete, requests = 0;
    class Observer {
        constructor(callback, options) { notify = callback; assert.equal(options.rootMargin, '300px 0px'); }
        observe() { observed = true; }
        disconnect() { disconnected = true; }
    }
    const f = fixture((url, options) => {
        requests++;
        assert.equal(url, '/home/broad-select');
        assert.equal(options.credentials, 'same-origin');
        assert.equal(options.signal.aborted, false);
        return new Promise(resolve => { complete = resolve; });
    }, { Observer });
    f.loader.start();
    assert.equal(observed, true);
    assert.equal(requests, 0);
    notify([{ isIntersecting: false }]);
    assert.equal(requests, 0);
    notify([{ isIntersecting: true }]);
    assert.equal(disconnected, true);
    assert.equal(f.loading.hidden, false);
    assert.equal(f.attributes.get('aria-busy'), 'true');
    assert.equal(await f.loader.load(), false);
    complete(success());
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(f.loader.state, 'loaded');
    assert.equal(f.inserted(), f.fragment);
    assert.equal(f.initialized(), 1);
    assert.equal(f.loading.hidden, true);
    assert.equal(f.attributes.size, 0);
    assert.equal(await f.loader.load(), false);
    assert.equal(requests, 1);
});

test('empty groups hide the shell without hydration or an indefinite skeleton', async () => {
    const f = fixture(async () => ({ ok: true, status: 204 }));
    assert.equal(await f.loader.load(), true);
    assert.equal(f.element.hidden, true);
    assert.equal(f.loading.hidden, true);
    assert.equal(f.initialized(), 0);
});

test('errors and invalid fragments unlock retry; successful retry restores keyboard focus', async () => {
    for (const response of [
        async () => { throw new TypeError('Offline'); },
        async () => ({ ok: false }),
        async () => ({ ...success(), redirected: true }),
        async () => success(),
    ]) {
        const f = fixture(response);
        f.fragment.querySelector = () => null;
        assert.equal(await f.loader.load(), false);
        assert.equal(f.error.hidden, false);
        assert.equal(f.retry.disabled, false);
        assert.equal(f.loading.hidden, true);
        assert.equal(f.attributes.size, 0);
        assert.equal(f.initialized(), 0);
        f.fragment.querySelector = () => ({});
        f.loader.fetchPage = async () => success();
        f.page.activeElement = f.retry;
        assert.equal(await f.loader.load(), true);
        assert.equal(f.error.hidden, true);
        assert.equal(f.focused(), true);
    }
});

test('browsers without IntersectionObserver fetch immediately', async () => {
    const f = fixture(async () => success(), { Observer: null });
    f.loader.start();
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(f.loader.state, 'loaded');
    assert.equal(f.initialized(), 1);
});

test('calls the browser request function without binding it to the loader instance', async () => {
    const f = fixture(function () {
        assert.equal(this, undefined);
        return Promise.resolve(success());
    });
    assert.equal(await f.loader.load(), true);
});

test('separate placements load and retry independently', async () => {
    const broad = fixture(async () => success());
    const promos = fixture(async () => ({ ok: false }));
    await Promise.all([broad.loader.load(), promos.loader.load()]);
    assert.equal(broad.loader.state, 'loaded');
    assert.equal(promos.loader.state, 'error');
    promos.loader.fetchPage = async () => success();
    await promos.loader.load();
    assert.equal(promos.loader.state, 'loaded');
    assert.equal(broad.initialized(), 1);
    assert.equal(promos.initialized(), 1);
});
