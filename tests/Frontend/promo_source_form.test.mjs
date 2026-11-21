import assert from 'node:assert/strict';
import test from 'node:test';
import { promoSourceForm } from '../../resources/js/components/promo_source_form.js';

function form(overrides = {}) {
    const watchers = {};
    const state = promoSourceForm({
        source: 'collection', placement: 'homepage_main', layout: 'grid',
        broadPlacement: 'homepage_broad_select', ...overrides,
    });
    state.$watch = (key, callback) => { watchers[key] = callback; };
    state.init();
    return { state, watchers };
}

test('broad placement fixes the layout while source choice controls collection availability', () => {
    const { state, watchers } = form();
    assert.equal(state.needsCollection, true);
    state.placement = 'homepage_broad_select';
    watchers.placement();
    assert.equal(state.layout, 'row');
    state.source = 'new_arrivals';
    assert.equal(state.needsCollection, false);
    state.placement = 'homepage_main';
    watchers.placement();
    assert.equal(state.source, 'collection');
    assert.equal(state.needsCollection, true);
});

test('saved automatic forms restore their source and keep each form independent', () => {
    const first = form({ source: 'top_rated', placement: 'homepage_broad_select' }).state;
    const second = form().state;
    assert.equal(first.source, 'top_rated');
    assert.equal(first.layout, 'row');
    assert.equal(first.needsCollection, false);
    assert.equal(second.source, 'collection');
    assert.equal(second.layout, 'grid');
});
