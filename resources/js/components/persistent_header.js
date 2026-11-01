export class PersistentHeader {
    constructor(element) {
        this.container = element;

        const parent = element.parentElement;
        this.anchor = parent && getComputedStyle(parent).position === 'sticky' ? parent : null;
        this.surface = this.anchor ?? element;
        this.primary = this.anchor?.querySelector(':scope > nav') ?? null;

        const threshold = Number(element.dataset.persistentHeaderScrollThreshold ?? 16);
        this.threshold = Number.isFinite(threshold) ? Math.max(0, threshold) : 16;
        this.stickyTop = Number.parseFloat(getComputedStyle(this.surface).top) || 0;
        this.lastScrollY = this.scrollPosition();
        this.direction = 0;
        this.travel = 0;
        this.frame = null;
        this.needsMeasurement = true;
        this.reveal = false;
        this.height = 0;
        this.visible = 0;

        if (!this.anchor) {
            // Keep the header in normal flow; native sticky positioning follows scrolling.
            this.surface.style.setProperty('position', 'sticky', 'important');
        }

        this.observer = new ResizeObserver(() => {
            this.needsMeasurement = true;
            this.schedule();
        });
        this.observer.observe(element);

        this.#setupSignals();
        this.schedule();
    }

    #setupSignals() {
        window.addEventListener('scroll', () => {
            return this.schedule()
        }, { passive: true });

        window.addEventListener('resize', () => {
            this.needsMeasurement = true;
            this.schedule();
        }, { passive: true });

        window.addEventListener('focusin', () => {
            this.reveal = true;
            this.schedule();
        });
    }

    scrollPosition() {
        const maximum = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
        return Math.max(0, Math.min(window.scrollY, maximum));
    }

    schedule() {
        if (this.frame !== null) return;
        this.frame = requestAnimationFrame(() => {
            this.frame = null;
            this.update();
        });
    }

    measure() {
        const styles = getComputedStyle(this.container);
        const height = this.container.offsetHeight
            + (Number.parseFloat(styles.marginTop) || 0)
            + (Number.parseFloat(styles.marginBottom) || 0);
        const wasFullyVisible = this.height === 0 || this.visible === this.height;
        this.visible = wasFullyVisible ? height : Math.min(this.visible, height);
        this.height = height;
        this.needsMeasurement = false;
    }

    update() {
        // Read layout before changing styles. No scroll handler changes layout height.
        if (this.needsMeasurement) this.measure();
        const scrollY = this.scrollPosition();
        const delta = scrollY - this.lastScrollY;
        const pinned = this.surface.getBoundingClientRect().top <= this.stickyTop + 0.5;
        this.lastScrollY = scrollY;

        if (!pinned || scrollY === 0 || this.reveal) {
            this.visible = this.height;
            this.direction = 0;
            this.travel = 0;
            this.reveal = false;
        } else if (delta !== 0) {
            this.applyScroll(delta);
        }

        this.render();
    }

    applyScroll(delta) {
        const direction = Math.sign(delta);
        if (direction !== this.direction) {
            this.direction = direction;
            this.travel = 0;
        }

        // Apply only the distance beyond the dead zone, once per frame.
        const before = Math.max(0, this.travel - this.threshold);
        this.travel += Math.abs(delta);
        const movement = Math.max(0, this.travel - this.threshold) - before;
        this.visible = Math.max(0, Math.min(this.height, this.visible - direction * movement));
    }

    render() {
        const hidden = this.height - this.visible;
        const top = `${this.stickyTop - hidden}px`;
        if (this.surface.style.top !== top) this.surface.style.top = top;

        // Move the sticky surface upward while keeping its primary navigation visible.
        // Its normal-flow footprint stays unchanged, so scroll anchoring has nothing to correct.
        if (this.primary) {
            const transform = hidden ? `translateY(${hidden}px)` : '';
            if (this.primary.style.transform !== transform) this.primary.style.transform = transform;
        }
    }
}

if (typeof document !== 'undefined') {
    document.querySelectorAll('.persistent-header').forEach(element => {
        if (element.querySelector('.persistent-header-content')) new PersistentHeader(element);
    });
}
