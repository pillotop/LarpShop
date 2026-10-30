document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-more-reviews]');
    if (!button) return;

    event.preventDefault();
    if (button.getAttribute('aria-disabled') === 'true') return;

    const pagination = button.closest('[data-review-pagination]');
    const feedback = pagination.querySelector('[data-review-error]');
    button.setAttribute('aria-disabled', 'true');
    button.textContent = 'Loading reviews…';
    feedback.textContent = '';

    try {
        const response = await fetch(button.dataset.moreReviews, {
            headers: { Accept: 'text/html' },
        });
        if (!response.ok) throw new Error('Could not load reviews. Please try again.');

        const template = document.createElement('template');
        template.innerHTML = await response.text();
        pagination.replaceWith(template.content);
    } catch (error) {
        feedback.textContent = 'Could not load reviews. Please try again.';
        button.removeAttribute('aria-disabled');
        button.textContent = 'Show more reviews';
    }
});
