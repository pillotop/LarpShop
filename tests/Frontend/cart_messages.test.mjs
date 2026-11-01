import assert from 'node:assert/strict';
import test from 'node:test';

const messages = {
    updated: 'Đã cập nhật: :count sản phẩm.',
    interrupted: 'Kết nối bị gián đoạn.',
    unreadable: 'Phản hồi không hợp lệ.',
};
globalThis.document = {
    getElementById: () => ({ textContent: JSON.stringify(messages) }),
    addEventListener() {},
    removeEventListener() {},
};
globalThis.window = {
    addEventListener() {},
    removeEventListener() {},
};
globalThis.BroadcastChannel = undefined;

const { cartMessage } = await import('../../resources/js/components/cart_messages.js');
await import('../../resources/js/components/cart_store.js');

test('messages preserve Unicode and substitute named values', () => {
    assert.equal(cartMessage('updated', { count: 2 }), 'Đã cập nhật: 2 sản phẩm.');
});

test('connection errors notify subscribers in the selected language', async () => {
    globalThis.fetch = async () => { throw new TypeError('Failed to fetch'); };
    const store = new window.CartStore({ showUrl: '/cart' });
    let notification;
    store.subscribeError(error => { notification = error.message; });

    await assert.rejects(store.refresh(), { message: messages.interrupted });
    assert.equal(notification, messages.interrupted);
    assert.equal(store.getSnapshot(), null);
    store.dispose();
});

test('invalid cart responses use the translated fallback and preserve existing state', async () => {
    globalThis.fetch = async () => ({
        ok: true,
        json: async () => ({ success: true, cart: { products: 'invalid' } }),
    });
    const initial = { cart: { products: [], total_count: 0 }, html: '' };
    const store = new window.CartStore({ showUrl: '/cart', initial });

    await assert.rejects(store.refresh(), { message: messages.unreadable });
    assert.deepEqual(store.getSnapshot(), initial);
    store.dispose();
});
