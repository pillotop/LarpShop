import '../app.js';

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-more-reviews]');
    if (!button) return;

    event.preventDefault();
    if (button.getAttribute('aria-disabled') === 'true') return;

    const pagination = button.closest('[data-review-pagination]');
    const feedback = pagination.querySelector('[data-review-error]');
    button.setAttribute('aria-disabled', 'true');
    const originalLabel = button.textContent;
    button.textContent = pagination.dataset.loadingMessage;
    feedback.textContent = '';

    try {
        const response = await fetch(button.dataset.moreReviews, {
            headers: { Accept: 'text/html' },
        });
        if (!response.ok) throw new Error(pagination.dataset.errorMessage);

        const template = document.createElement('template');
        template.innerHTML = await response.text();
        pagination.replaceWith(template.content);
    } catch (error) {
        feedback.textContent = pagination.dataset.errorMessage;
        button.removeAttribute('aria-disabled');
        button.textContent = originalLabel;
    }
});

document.addEventListener('click', async (event) => {
    const filterLink = event.target.closest('[data-review-filter]');
    if (!filterLink) return;

    const listContainer = document.querySelector('[data-product-reviews]');
    const filterBar = filterLink.closest('[data-review-filters]');
    if (!listContainer || !filterBar) return;

    event.preventDefault();

    try {
        const response = await fetch(filterLink.dataset.reviewFilter, {
            headers: { Accept: 'text/html' },
        });
        if (!response.ok) return;

        listContainer.innerHTML = await response.text();
        filterBar.querySelectorAll('[data-review-filter]').forEach((el) => {
            const active = el === filterLink;
            el.classList.toggle('btn-solid', active);
            el.classList.toggle('theme-primary', active);
            el.classList.toggle('btn-outline', !active);
            el.classList.toggle('theme-secondary', !active);
            el.classList.toggle('bg-white', !active);
        });
    } catch (_) {
        window.location.href = filterLink.href;
    }
});
