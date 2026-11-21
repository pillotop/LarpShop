import assert from 'node:assert/strict';
import test from 'node:test';
import { vendorOnboarding } from '../../resources/js/components/vendor_onboarding.js';
import { sendOnboarding, stepForError } from '../../resources/js/components/vendor_onboarding_transport.js';

function fixture(overrides = {}, sendOverride = null) {
    const config = {
        form: { current_step: 'seller', contact_name: 'Test Seller', contact_email: '',
            store_name: 'Test Store', seller_country_code: 'VN', delivery_mode: 'domestic', destination_countries: [] },
        applicationId: null, editable: true, imageUrl: '/default.svg', defaultImageUrl: '/default.svg',
        steps: ['seller', 'store', 'shipping', 'review'],
        countries: { VN: 'Vietnam', US: 'United States' },
        stepLabels: {}, choiceLabels: {}, addresses: { 8: { city: 'Example City', country_code: 'VN' } },
        urls: { store: '/applications', show: '/applications/__id__', update: '/applications/__id__',
            submit: '/applications/__id__/submit', profile: '/profile' },
        messages: { empty: 'Not provided', uncertain: 'Check drafts', validation: 'Fix errors', session: 'Session expired',
            failed: 'Try again', confirmation: 'Confirm first' },
        ...overrides,
    };
    const calls = [], navigations = [], replacements = [], focused = [];
    const send = sendOverride ?? (async (url, body) => {
        calls.push({ url, body });
        return { ...config.form, current_step: body.get('current_step') ?? 'review',
            vendor_application_id: 42, vendor_id: 7, destinations: [], profile_image_url: '/saved.png' };
    });
    const state = vendorOnboarding(config, { send, navigate: url => navigations.push(url), replaceUrl: url => replacements.push(url) });
    state.$refs = { stepHeading: { focus: () => focused.push('heading') }, feedback: { focus: () => focused.push('feedback') },
        confirmation: { focus: () => focused.push('confirmation') } };
    state.$nextTick = callback => callback();
    state.$root = { querySelector: selector => selector === '[name="_token"]'
        ? { value: 'csrf-fixture' } : { focus: () => focused.push(selector) } };
    return { state, calls, navigations, replacements, focused };
}

test('Next validates and persists the section before advancing and replaces the creation URL', async () => {
    const f = fixture();
    await f.state.save('next');
    assert.equal(f.calls[0].body.get('validate_step'), 'seller');
    assert.equal(f.calls[0].body.get('current_step'), 'store');
    assert.equal(f.calls[0].body.get('destination_countries'), '[]');
    assert.equal(f.state.currentStep, 'store');
    assert.equal(f.state.applicationId, 42);
    assert.deepEqual(f.replacements, ['/applications/42']);
    assert.equal(f.state.busy, false);
    await f.state.save('exit');
    assert.equal(f.calls[1].body.get('_method'), 'PATCH');
    assert.deepEqual(f.navigations, ['/profile#pane-vendor']);
});

test('validation errors open the right section and retain unsaved entries', async () => {
    const f = fixture({}, async () => { throw { status: 422, errors: { 'destination_countries.0': ['Invalid country'] } }; });
    f.state.form.contact_email = 'unsaved@example.test';
    await f.state.save('next');
    assert.equal(f.state.currentStep, 'shipping');
    assert.equal(f.state.fieldError('destination_countries'), 'Invalid country');
    assert.equal(f.state.form.contact_email, 'unsaved@example.test');
    assert.equal(f.state.applicationId, null);
});

test('an uncertain initial save blocks another creation attempt and offers recovery', async () => {
    let calls = 0;
    const f = fixture({}, async () => { calls++; throw { uncertain: true }; });
    await f.state.save('next');
    await f.state.save('exit');
    assert.equal(calls, 1);
    assert.equal(f.state.uncertainCreate, true);
    assert.equal(f.state.notice, 'Check drafts');
});

test('existing drafts can retry after network failure without creating another application', async () => {
    const urls = [];
    const f = fixture({ applicationId: 8 }, async url => { urls.push(url); throw { uncertain: true }; });
    await f.state.save('next');
    await f.state.save('next');
    assert.deepEqual(urls, ['/applications/8', '/applications/8']);
    assert.equal(f.state.uncertainCreate, false);
});

test('submit requires confirmation, saves current edits, then submits the persisted draft', async () => {
    const f = fixture({ applicationId: 42 });
    f.state.stepIndex = 3;
    await f.state.submit();
    assert.equal(f.calls.length, 0);
    assert.equal(f.state.fieldError('confirmation'), 'Confirm first');
    f.state.confirmation = true;
    await f.state.submit();
    assert.equal(f.calls.length, 2);
    assert.equal(f.calls[0].body.get('_method'), 'PATCH');
    assert.equal(f.calls[1].url, '/applications/42/submit');
    assert.equal(f.calls[1].body.get('confirmation'), '1');
    assert.equal(f.calls[1].body.has('_method'), false);
    assert.deepEqual(f.navigations, ['/profile#pane-vendor-7']);
});

test('read-only applications cannot send draft or submit requests', async () => {
    const f = fixture({ editable: false, applicationId: 42 });
    await f.state.save('exit');
    await f.state.submit();
    assert.equal(f.calls.length, 0);
    assert.equal(f.state.currentStep, 'review');
});

test('filtering countries preserves selected values and copying addresses uses a separate snapshot', () => {
    const f = fixture();
    f.state.form.destination_countries = ['VN'];
    f.state.countrySearch = 'united';
    assert.deepEqual(f.state.filteredCountries, [['US', 'United States']]);
    assert.deepEqual(f.state.form.destination_countries, ['VN']);
    f.state.selectedAddress = '8';
    f.state.useAddress();
    assert.equal(f.state.form.pickup_city, 'Example City');
    f.state.form.pickup_city = 'Edited City';
    f.state.useAddress();
    assert.equal(f.state.form.pickup_city, 'Example City');
});

test('Back does not discard input or permit skipping forward', () => {
    const f = fixture();
    f.state.stepIndex = 2;
    f.state.form.contact_name = 'Kept Name';
    f.state.backTo(0);
    f.state.backTo(3);
    assert.equal(f.state.stepIndex, 0);
    assert.equal(f.state.form.contact_name, 'Kept Name');
    assert.equal(stepForError('application'), null);
    assert.equal(stepForError('brand_image'), 'store');
});

test('transport includes session/CSRF headers and preserves structured validation errors', async () => {
    await assert.rejects(sendOnboarding('/application', new FormData(), 'csrf', async (_, options) => {
        assert.equal(options.credentials, 'same-origin');
        assert.equal(options.headers['X-CSRF-TOKEN'], 'csrf');
        return { ok: false, status: 422, json: async () => ({ errors: { store_name: ['Taken'] } }) };
    }), error => error.status === 422 && error.errors.store_name[0] === 'Taken' && !error.uncertain);
    await assert.rejects(sendOnboarding('/application', new FormData(), 'csrf', async () => {
        return { ok: true, status: 200, json: async () => { throw new Error('bad JSON'); } };
    }), error => error.uncertain === true);
});

test('changing language saves dirty fields before submitting the existing locale form', async () => {
    const f = fixture();
    f.state.dirty = true;
    let posts = 0;
    await f.state.switchLocale({ target: { submit: () => { posts++; assert.equal(f.state.dirty, false); } } });
    assert.equal(f.calls.length, 1);
    assert.equal(f.calls[0].body.get('validate_step'), null);
    assert.equal(posts, 1);
    assert.deepEqual(f.replacements, ['/applications/42']);
});

test('language switching keeps invalid edits and avoids duplicate saves when busy or uncertain', async () => {
    const f = fixture({}, async () => { throw { status: 422, errors: { contact_email: ['Invalid email'] } }; });
    f.state.dirty = true;
    let posts = 0;
    let resets = 0;
    const event = { target: { submit: () => posts++, reset: () => resets++ } };
    await f.state.switchLocale(event);
    assert.equal(posts, 0);
    assert.equal(f.state.dirty, true);
    assert.equal(resets, 1);
    f.state.saving = 'next';
    await f.state.switchLocale(event);
    f.state.saving = '';
    f.state.uncertainCreate = true;
    await f.state.switchLocale(event);
    assert.equal(posts, 0);
});

test('language switching on pristine or read-only pages does not create a draft', async () => {
    for (const editable of [true, false]) {
        const f = fixture({ editable });
        let posts = 0;
        await f.state.switchLocale({ target: { submit: () => posts++ } });
        assert.equal(posts, 1);
        assert.equal(f.calls.length, 0);
    }
});
