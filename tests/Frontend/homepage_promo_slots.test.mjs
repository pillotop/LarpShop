import assert from 'node:assert/strict';
import test from 'node:test';
import { createSharedPromoFetch, selectPromoSlot } from '../../resources/js/components/homepage_promo_slots.js';

const html = '<div data-deferred-fragment><div data-home-promo-id="1"></div></div>';
const response = () => ({ ok: true, status: 200, text: async () => html });

test('concurrent and later slots reuse one request and can each read the body', async () => {
    let requests = 0;
    const fetchPage = createSharedPromoFetch(async (url, options) => {
        requests++;
        assert.equal(url, '/home/promo-sections');
        assert.equal(options.credentials, 'same-origin');
        return response();
    });
    const options = { credentials: 'same-origin' };
    const [a, b] = await Promise.all([fetchPage('/home/promo-sections', options), fetchPage('/home/promo-sections', options)]);
    assert.equal(await a.text(), html);
    assert.equal(await b.text(), html);
    assert.equal(await (await fetchPage('/home/promo-sections', options)).text(), html);
    assert.equal(requests, 1);
});

test('failed, redirected and invalid responses permit a fresh retry', async () => {
    for (const bad of [() => { throw new Error('Offline'); }, () => ({ ok: false }),
        () => ({ ...response(), redirected: true }), () => ({ ...response(), text: async () => '<p>Unexpected</p>' })]) {
        let requests = 0;
        const fetchPage = createSharedPromoFetch(async () => ++requests === 1 ? bad() : response());
        await assert.rejects(fetchPage('/home/promo-sections', {}));
        assert.equal(await (await fetchPage('/home/promo-sections', {})).text(), html);
        assert.equal(requests, 2);
    }
});

test('slots select only their own server-rendered section and reject malformed identifiers', () => {
    const selected = {};
    const fragment = { querySelector(selector) {
        assert.equal(selector, '[data-home-promo-id="12"]');
        return selected;
    } };
    assert.equal(selectPromoSlot(fragment, { dataset: { homepagePromoId: '12' } }), selected);
    assert.equal(selectPromoSlot(fragment, { dataset: { homepagePromoId: '12"] a' } }), null);
    assert.equal(selectPromoSlot({ querySelector: () => null }, { dataset: { homepagePromoId: '12' } }), null);
});
