import assert from 'node:assert/strict';
import test from 'node:test';
import { CategoryDiscovery } from '../../resources/js/components/category_discovery.js';

function node(dataset = {}) {
    return {
        dataset, hidden: false, disabled: false, attributes: new Map(), listeners: new Map(),
        addEventListener(name, handler) { this.listeners.set(name, handler); },
        setAttribute(name, value) { this.attributes.set(name, value); },
        removeAttribute(name) { this.attributes.delete(name); },
        focus() { this.focused = true; },
    };
}

function fixture(fetchPage, options = {}) {
    const loading = node(), content = node(), region = node(), error = node(), retry = node();
    const expand = node({ moreLabel: 'More', lessLabel: 'Less' });
    const choices = [node({ discoveryCategory: '' }), node({ discoveryCategory: '9' })];
    const elements = {
        '[data-loading-state]': loading, '[data-discovery-content]': content,
        '[data-discovery-region]': region, '[data-discovery-error]': error,
        '[data-discovery-retry]': retry, '[data-discovery-expand]': expand,
    };
    const element = {
        dataset: { source: '/home/discovery' },
        querySelector: selector => elements[selector], querySelectorAll: () => choices,
    };
    let inserted, hydrated = 0, faded = 0, result;
    content.replaceChildren = fragment => { inserted = fragment.html; };
    content.animate = () => { faded++; };
    const page = {
        activeElement: null,
        defaultView: { matchMedia: () => ({ matches: false }) },
        createElement() {
            const fragment = {
                querySelector() {
                    if (fragment.html === 'invalid') return null;
                    result = node({ hasMore: fragment.html.includes('more') ? 'true' : 'false' });
                    return result;
                },
            };
            return { content: fragment, set innerHTML(html) { fragment.html = html; } };
        },
    };
    const discovery = new CategoryDiscovery(element, {
        fetchPage, page, initialize: () => hydrated++, ...options,
    });
    return { discovery, loading, content, region, error, retry, expand, choices, page,
        inserted: () => inserted, hydrated: () => hydrated, faded: () => faded, result: () => result };
}

const success = (html = 'products more') => ({ ok: true, redirected: false, text: async () => html });

test('waits near viewport, then loads once with skeleton and restores card interactions', async () => {
    let notify, resolve, requests = 0;
    class Observer {
        constructor(callback, options) { notify = callback; assert.equal(options.rootMargin, '300px 0px'); }
        observe() {}
        disconnect() {}
    }
    const f = fixture((url, options) => {
        requests++;
        assert.equal(url, '/home/discovery?expanded=0');
        assert.equal(options.credentials, 'same-origin');
        return new Promise(done => { resolve = done; });
    }, { Observer });
    f.discovery.start();
    assert.equal(requests, 0);
    notify([{ isIntersecting: true }]);
    assert.equal(f.loading.hidden, false);
    assert.equal(f.content.hidden, true);
    assert.equal(f.region.attributes.get('aria-busy'), 'true');
    resolve(success());
    await new Promise(done => setImmediate(done));
    notify([{ isIntersecting: true }]);
    assert.equal(requests, 1);
    assert.equal(f.loading.hidden, true);
    assert.equal(f.content.hidden, false);
    assert.equal(f.expand.hidden, false);
    assert.equal(f.hydrated(), 1);
    assert.equal(f.faded(), 1);
});

test('older responses cannot replace a newer selection or clear its loading state', async () => {
    const pending = [];
    const f = fixture((url, options) => new Promise(resolve => pending.push({ url, options, resolve })));
    const first = f.discovery.load();
    f.discovery.category = '9';
    const second = f.discovery.load();
    assert.equal(pending[0].options.signal.aborted, true);
    assert.equal(pending[1].url, '/home/discovery?expanded=0&category=9');
    pending[0].resolve(success('stale products'));
    assert.equal(await first, false);
    assert.equal(f.loading.hidden, false);
    assert.equal(f.inserted(), undefined);
    pending[1].resolve(success('new products'));
    assert.equal(await second, true);
    assert.equal(f.inserted(), 'new products');
    assert.equal(f.expand.hidden, true);
});

test('category selection resets expansion and preserves normal modified navigation', async () => {
    const urls = [];
    const f = fixture(async url => { urls.push(url); return success(); });
    const choice = f.choices[1];
    const click = choice.listeners.get('click');
    let prevented = false;
    f.discovery.expanded = true;
    click({ ctrlKey: true, preventDefault() { prevented = true; } });
    assert.equal(prevented, false);
    assert.equal(urls.length, 0);
    click({ button: 0, preventDefault() { prevented = true; } });
    await new Promise(done => setImmediate(done));
    assert.equal(prevented, true);
    assert.equal(f.discovery.expanded, false);
    assert.equal(choice.attributes.get('aria-current'), 'true');
    assert.equal(f.choices[0].attributes.has('aria-current'), false);
    assert.equal(urls[0], '/home/discovery?expanded=0&category=9');
});

test('expansion uses the selected category and supports collapse with keyboard focus', async () => {
    const urls = [];
    const f = fixture(async url => { urls.push(url); return success(); });
    f.discovery.category = '9';
    f.page.activeElement = f.expand;
    f.expand.listeners.get('click')();
    await new Promise(done => setImmediate(done));
    assert.equal(urls[0], '/home/discovery?expanded=1&category=9');
    assert.equal(f.expand.textContent, 'Less');
    assert.equal(f.expand.attributes.get('aria-expanded'), 'true');
    assert.equal(f.result().focused, true);
    f.expand.listeners.get('click')();
    await new Promise(done => setImmediate(done));
    assert.equal(urls[1], '/home/discovery?expanded=0&category=9');
    assert.equal(f.expand.textContent, 'More');
});

test('HTTP errors, redirects and malformed fragments offer retry without leaving busy state', async () => {
    for (const response of [{ ok: false }, { ok: true, redirected: true }, success('invalid')]) {
        let attempts = 0;
        const f = fixture(async () => ++attempts === 1 ? response : success('recovered'));
        assert.equal(await f.discovery.load(), false);
        assert.equal(f.error.hidden, false);
        assert.equal(f.loading.hidden, true);
        assert.equal(f.region.attributes.has('aria-busy'), false);
        assert.equal(f.retry.disabled, false);
        f.page.activeElement = f.retry;
        f.retry.listeners.get('click')();
        await new Promise(done => setImmediate(done));
        assert.equal(f.inserted(), 'recovered');
        assert.equal(f.error.hidden, true);
        assert.equal(f.result().focused, true);
    }
});

test('fallback loads without IntersectionObserver and reduced motion skips fading', async () => {
    const f = fixture(async () => success(), { Observer: null });
    f.page.defaultView.matchMedia = () => ({ matches: true });
    f.discovery.start();
    await new Promise(done => setImmediate(done));
    assert.equal(f.discovery.state, 'loaded');
    assert.equal(f.faded(), 0);
});
