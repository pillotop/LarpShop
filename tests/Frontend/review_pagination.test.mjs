import assert from 'node:assert/strict';
import test from 'node:test';
import { loadMoreReviews } from '../../resources/js/components/review_pagination.js';

function fixture() {
    const loading = { hidden: true };
    const feedback = { textContent: 'Previous error' };
    let replacement;
    const pagination = {
        dataset: { errorMessage: 'Please try again.' },
        querySelector: selector => selector.includes('loading') ? loading : feedback,
        replaceWith(content) { replacement = content; },
    };
    const attributes = new Map();
    const button = {
        textContent: 'Show more reviews', dataset: { moreReviews: '/reviews?page=2' },
        closest: () => pagination,
        getAttribute: name => attributes.get(name),
        setAttribute: (name, value) => attributes.set(name, value),
        removeAttribute: name => attributes.delete(name),
    };
    const template = { content: { reviews: 'next page' }, innerHTML: '' };
    return { loading, feedback, button, attributes, template,
        page: { createElement: () => template }, replacement: () => replacement };
}

test('shows loading during the actual request, prevents duplicates, then appends the response', async () => {
    const f = fixture();
    let resolve, calls = 0;
    const fetchPage = (url, options) => {
        calls++;
        assert.equal(url, '/reviews?page=2');
        assert.equal(options.headers.Accept, 'text/html');
        return new Promise(done => { resolve = done; });
    };
    const request = loadMoreReviews(f.button, fetchPage, f.page);
    assert.equal(f.loading.hidden, false);
    assert.equal(f.attributes.get('aria-busy'), 'true');
    assert.equal(f.feedback.textContent, '');
    assert.equal(f.replacement(), undefined);
    assert.equal(await loadMoreReviews(f.button, fetchPage, f.page), false);
    assert.equal(calls, 1);
    resolve({ ok: true, text: async () => '<article>More reviews</article>' });
    assert.equal(await request, true);
    assert.equal(f.replacement(), f.template.content);
    assert.equal(f.loading.hidden, true);
    assert.equal(f.attributes.size, 0);
    assert.equal(f.button.textContent, 'Show more reviews');
});

test('network, HTTP and redirect failures retain current reviews and unlock retry', async () => {
    for (const fetchPage of [
        async () => { throw new TypeError('Offline'); },
        async () => ({ ok: false }),
        async () => ({ ok: true, redirected: true }),
    ]) {
        const f = fixture();
        assert.equal(await loadMoreReviews(f.button, fetchPage, f.page), false);
        assert.equal(f.replacement(), undefined);
        assert.equal(f.loading.hidden, true);
        assert.equal(f.attributes.size, 0);
        assert.equal(f.feedback.textContent, 'Please try again.');
        assert.equal(await loadMoreReviews(f.button, async () => ({ ok: true, text: async () => 'Retried' }), f.page), true);
    }
});
