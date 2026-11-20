export class TwoArrowScroller {
    constructor(element) {
        const content = element.querySelector('[data-scroll-content]');
        const previous = element.querySelector('[data-scroll-previous]');
        const next = element.querySelector('[data-scroll-next]');
        if (!content || !previous || !next) return;

        const step = Number(element.dataset.scrollStep || '0.67');
        const distance = () => content.clientWidth * (Number.isFinite(step) && step > 0 ? step : 0.67);
        const reducedMotion = () => window.matchMedia?.('(prefers-reduced-motion: reduce)').matches;
        const scroll = direction => content.scrollBy({
            left: direction * distance(),
            behavior: reducedMotion() ? 'auto' : 'smooth',
        });

        previous.addEventListener('click', () => scroll(-1));
        next.addEventListener('click', () => scroll(1));
    }
}

document.querySelectorAll('.two-arrow-scroller').forEach(element => new TwoArrowScroller(element));
