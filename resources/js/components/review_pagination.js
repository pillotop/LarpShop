export async function loadMoreReviews(button, fetchPage = fetch, page = document) {
    const pagination = button.closest('[data-review-pagination]');
    if (!pagination || button.getAttribute('aria-disabled') === 'true') return false;

    const feedback = pagination.querySelector('[data-review-error]');
    const loading = pagination.querySelector('[data-loading-state]');
    button.setAttribute('aria-disabled', 'true');
    button.setAttribute('aria-busy', 'true');
    if (loading) loading.hidden = false;
    feedback.textContent = '';

    try {
        const response = await fetchPage(button.dataset.moreReviews, {
            headers: { Accept: 'text/html' },
        });
        if (!response.ok || response.redirected) throw new Error('Unable to load reviews.');

        const template = page.createElement('template');
        template.innerHTML = await response.text();
        pagination.replaceWith(template.content);
        return true;
    } catch {
        feedback.textContent = pagination.dataset.errorMessage;
        return false;
    } finally {
        if (loading) loading.hidden = true;
        button.removeAttribute('aria-disabled');
        button.removeAttribute('aria-busy');
    }
}
