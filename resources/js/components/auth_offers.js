import { Carousel } from 'bootstrap';

const element = document.getElementById('auth-offers-carousel');

if (element) {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const toggle = element.querySelector('.carousel-control-play-pause');
    const slides = [...element.querySelectorAll('.carousel-item')];
    Carousel.getOrCreateInstance(element, {
        autoplay: !reducedMotion.matches,
        interval: Number(element.dataset.offerInterval),
        ends: 'wrap',
        pause: 'hover',
    });

    const preloadNext = index => {
        slides[(index + 1) % slides.length].querySelector('img').loading = 'eager';
    };
    preloadNext(0);
    element.addEventListener('slid.bs.carousel', event => preloadNext(event.to));

    // Stop automatic changes while someone interacts with links or slide controls.
    element.addEventListener('focusin', event => {
        if (event.target === toggle || toggle.classList.contains('paused')) return;
        toggle.click();
    });
    reducedMotion.addEventListener('change', event => {
        if (event.matches && !toggle.classList.contains('paused')) toggle.click();
    });
}
