import '../app.js';
import { loadMoreReviews } from './review_pagination.js';

document.addEventListener('click', event => {
    const button = event.target.closest('[data-more-reviews]');
    if (!button) return;

    event.preventDefault();
    void loadMoreReviews(button);
});
