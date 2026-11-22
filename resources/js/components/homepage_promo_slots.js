// Several ordered placeholders share one bounded server-rendered product response.
export function createSharedPromoFetch(fetchPage = fetch) {
    let pending;
    return (url, options) => {
        pending ??= fetchPage(url, options).then(async response => {
            if (!response.ok || response.redirected) throw new Error('Unable to load homepage products.');
            const html = response.status === 204 ? '' : await response.text();
            if (response.status !== 204 && !/\bdata-deferred-fragment(?:\s|>)/.test(html)) {
                throw new Error('Invalid homepage products response.');
            }
            return { ok: true, status: response.status, redirected: false, html };
        }).catch(error => {
            pending = null;
            throw error;
        });
        return pending.then(result => ({ ...result, text: async () => result.html }));
    };
}

export function selectPromoSlot(fragment, element) {
    const id = element.dataset.homepagePromoId;
    if (!/^\d+$/.test(id ?? '')) return null;
    return fragment.querySelector(`[data-home-promo-id="${id}"]`);
}
