// Bootstrap owns slideshow behavior; this class owns its content and lifetime.
export class CategoryPreview {
    constructor(element, Carousel) {
        this.element = element;
        this.Carousel = Carousel;
        this.slides = element.querySelector('[data-category-preview-slides]');
        this.controls = element.querySelector('[data-category-preview-controls]');
        this.desktop = window.matchMedia('(min-width: 64rem)');
        this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        this.images = [];
        this.refresh = () => this.render();
        this.pauseOnFocus = () => this.carousel?.pause();
        this.desktop.addEventListener('change', this.refresh);
        this.reducedMotion.addEventListener('change', this.refresh);
        element.addEventListener('focusin', this.pauseOnFocus);
    }

    show(images) {
        this.images = images;
        this.render();
    }

    render() {
        this.carousel?.dispose();
        this.carousel = null;
        this.slides.replaceChildren();
        this.controls.hidden = true;
        // Hidden mobile previews should not download images or run a slideshow.
        if (!this.desktop.matches) return;

        const images = this.images.length ? this.images : [{ url: this.element.dataset.placeholder, alt: '' }];
        for (const [index, image] of images.entries()) {
            const slide = document.createElement('div');
            slide.className = `carousel-item${index === 0 ? ' active' : ''}`;
            const img = document.createElement('img');
            img.src = image.url;
            img.alt = image.alt;
            img.width = 480;
            img.height = 360;
            img.decoding = 'async';
            img.addEventListener('error', () => { img.src = this.element.dataset.placeholder; }, { once: true });
            slide.append(img);
            this.slides.append(slide);
        }

        if (images.length > 1) {
            this.controls.hidden = false;
            this.carousel = new this.Carousel(this.element, {
                autoplay: !this.reducedMotion.matches,
                interval: 5000,
                ends: 'wrap',
                pause: 'hover',
            });
        }
    }

    destroy() {
        this.carousel?.dispose();
        this.desktop.removeEventListener('change', this.refresh);
        this.reducedMotion.removeEventListener('change', this.refresh);
        this.element.removeEventListener('focusin', this.pauseOnFocus);
    }
}
