export async function sendOnboarding(url, body, csrf, fetcher = globalThis.fetch) {
    let response;
    try {
        response = await fetcher(url, {
            method: 'POST', credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf }, body,
        });
    } catch {
        throw Object.assign(new Error('network'), { uncertain: true });
    }
    let data;
    try {
        data = await response.json();
    } catch {
        throw Object.assign(new Error('response'), { status: response.status, uncertain: true });
    }
    if (!response.ok) {
        throw Object.assign(new Error('request'), {
            status: response.status, errors: data.errors ?? {}, uncertain: response.status >= 500,
        });
    }
    if (!Number.isInteger(data.application?.vendor_application_id)) {
        throw Object.assign(new Error('response'), { uncertain: true });
    }

    return data.application;
}

export function stepForError(field) {
    if (/^(pickup_|delivery_mode|destination_countries)/.test(field)) return 'shipping';
    if (['store_name', 'description', 'brand_image', 'remove_brand_image'].includes(field)) return 'store';
    if (/^(seller_|business_name|contact_)/.test(field)) return 'seller';
    return null;
}
