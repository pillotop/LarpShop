const HORIZONTAL = {
    position: 'scrollLeft',
    extent: 'scrollWidth',
    viewport: 'clientWidth',
    dimension: 'width',
    gap: 'columnGap',
    margins: ['marginLeft', 'marginRight'],
};
const VERTICAL = {
    position: 'scrollTop',
    extent: 'scrollHeight',
    viewport: 'clientHeight',
    dimension: 'height',
    gap: 'rowGap',
    margins: ['marginTop', 'marginBottom'],
};
const AXES = {
    right: { ...HORIZONTAL, sign: 1 },
    left: { ...HORIZONTAL, sign: -1 },
    down: { ...VERTICAL, sign: 1 },
    up: { ...VERTICAL, sign: -1 },
};

// Integrate exponential easing exactly: frame rate changes neither distance nor easing time.
export function advanceCarouselMotion(velocity, target, seconds) {
    const smoothing = 0.18;
    const decay = Math.exp(-seconds / smoothing);
    return {
        velocity: target + (velocity - target) * decay,
        distance: target * seconds + (velocity - target) * smoothing * (1 - decay),
    };
}

export class CarouselScrollable {
    constructor(element) {
        this.scroller = element.querySelector('.carousel-scroller');
        if (!this.scroller) return;
        this.axis = AXES[element.dataset.carouselDirection ?? 'right'] ?? AXES.right;
        const speed = Number(element.dataset.carouselAutoplaySpeed ?? 0);
        this.speed = Number.isFinite(speed) ? Math.abs(speed) : 0; // CSS pixels per second.
        this.direction = this.axis.sign;
        this.position = this.scroller[this.axis.position];
        this.lastWritten = this.position;
        this.velocity = 0;
        this.frame = null;
        this.lastTime = null;
        this.dirty = true;
        this.visible = !this.scroller.closest('[hidden]');
        this.hovered = false;
        this.focused = false;
        this.userPaused = false;
        this.dragging = false;
        this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        this.listeners = [];
        this.observedChildren = new Set();

        this.resizeObserver = new ResizeObserver(() => this.invalidate());
        this.resizeObserver.observe(this.scroller);
        this.mutationObserver = new MutationObserver(() => this.invalidate());
        this.mutationObserver.observe(this.scroller, { childList: true });
        this.listen(this.scroller, 'scroll', () => {
            const actual = this.scroller[this.axis.position];
            // Ignore our writes, including integer rounding in some browser engines.
            if (Math.abs(actual - this.lastWritten) > 0.01) {
                this.pendingDirection = Math.sign(actual - this.lastWritten);
                this.position = actual;
                this.lastWritten = actual;
                this.requestFrame();
            }
        });
        this.listen(this.scroller, 'mouseenter', () => { this.hovered = true; this.requestFrame(); });
        this.listen(this.scroller, 'mouseleave', () => { this.hovered = false; this.requestFrame(); });
        this.listen(this.scroller, 'focusin', () => { this.focused = true; this.requestFrame(); });
        this.listen(this.scroller, 'focusout', event => {
            this.focused = this.scroller.contains(event.relatedTarget);
            this.requestFrame();
        });
        this.listen(this.scroller, 'pointerdown', () => { this.dragging = true; this.requestFrame(); });
        this.listen(window, 'pointerup', () => { this.dragging = false; this.requestFrame(); });
        this.listen(window, 'pointercancel', () => { this.dragging = false; this.requestFrame(); });
        this.listen(this.scroller, 'carousel:reset', () => {
            this.velocity = 0;
            this.direction = this.axis.sign;
            this.scroller[this.axis.position] = this.direction > 0 ? 0 : this.maxScroll();
            this.invalidate();
        });
        this.listen(this.scroller, 'carousel:pause', event => {
            this.userPaused = event.detail.paused;
            this.velocity = 0;
            this.lastTime = null;
            this.requestFrame();
        });
        this.listen(this.scroller, 'carousel:visibility', event => {
            this.visible = event.detail.visible;
            this.velocity = 0;
            this.lastTime = null;
            this.dragging = false;
            cancelAnimationFrame(this.frame);
            this.frame = null;
            if (this.visible) {
                this.hovered = this.scroller.matches(':hover');
                this.focused = this.scroller.contains(document.activeElement);
                this.invalidate();
            }
        });
        this.listen(this.reducedMotion, 'change', () => {
            this.velocity = 0;
            this.requestFrame();
        });
        this.listen(document, 'visibilitychange', () => {
            this.velocity = 0;
            this.lastTime = null;
            if (document.hidden) {
                cancelAnimationFrame(this.frame);
                this.frame = null;
            } else this.invalidate();
        });
        this.requestFrame();
    }

    listen(target, type, listener) {
        target.addEventListener(type, listener);
        this.listeners.push(() => target.removeEventListener(type, listener));
    }

    invalidate() {
        this.dirty = true;
        this.requestFrame();
    }

    maxScroll() {
        return Math.max(0, this.scroller[this.axis.extent] - this.scroller[this.axis.viewport]);
    }

    measure() {
        const children = [...this.scroller.children];
        for (const child of this.observedChildren) {
            if (!children.includes(child)) this.resizeObserver.unobserve(child);
        }
        for (const child of children) {
            if (!this.observedChildren.has(child)) this.resizeObserver.observe(child);
        }
        this.observedChildren = new Set(children);
        const gap = parseFloat(getComputedStyle(this.scroller)[this.axis.gap]) || 0;
        this.advances = new Map(children.map(child => {
            const style = getComputedStyle(child);
            const margins = this.axis.margins.reduce((sum, key) => sum + (parseFloat(style[key]) || 0), 0);
            return [child, child.getBoundingClientRect()[this.axis.dimension] + margins + gap];
        }));
        const largest = Math.max(0, ...this.advances.values());
        // With too little overflow, moving an item would clamp the compensation and jump.
        this.canRecycle = children.length > 1 && largest > 0 && this.maxScroll() >= largest;
        this.position = this.scroller[this.axis.position];
        this.lastWritten = this.position;
        this.lastTime = null;
        this.dirty = false;
    }

    requestFrame() {
        if (this.frame !== null || document.hidden || !this.visible) return;
        this.frame = requestAnimationFrame(time => this.tick(time));
    }

    tick(time) {
        this.frame = null;
        if (this.dirty) this.measure();
        if (!this.scroller[this.axis.viewport] || this.maxScroll() <= 0) {
            this.velocity = 0;
            this.lastTime = null;
            return;
        }
        const seconds = this.lastTime === null ? 0 : Math.min((time - this.lastTime) / 1000, 0.05);
        this.lastTime = time;
        const paused = this.userPaused || this.hovered || this.focused || this.dragging || this.reducedMotion.matches;
        const target = paused ? 0 : this.speed * this.direction;
        const motion = advanceCarouselMotion(this.velocity, target, seconds);
        this.velocity = motion.velocity;
        this.position += motion.distance;
        this.recycle(Math.sign(motion.distance) || this.pendingDirection);
        this.pendingDirection = 0;
        const max = this.maxScroll();
        if (this.position <= 0 || this.position >= max) {
            this.position = Math.max(0, Math.min(max, this.position));
            // Short rows cannot loop invisibly; reverse gently at their ends instead.
            if (!this.canRecycle) this.direction = this.position >= max ? -1 : 1;
        }
        this.scroller[this.axis.position] = this.position;
        this.lastWritten = this.scroller[this.axis.position];
        // Keep the fractional position rather than repeatedly adding to a rounded DOM value.
        if (target !== 0 || Math.abs(this.velocity) > 0.05) this.requestFrame();
        else { this.velocity = 0; this.lastTime = null; }
    }

    recycle(direction) {
        if (!this.canRecycle || !direction) return;
        const count = this.scroller.children.length;
        this.mutationObserver.disconnect();
        for (let index = 0; index < count; index++) {
            const child = direction > 0 ? this.scroller.firstElementChild : this.scroller.lastElementChild;
            const advance = this.advances.get(child);
            if (!advance || child.contains(document.activeElement)) break;
            const outside = direction > 0
                ? this.position >= advance
                : this.position <= this.maxScroll() - advance;
            if (!outside) break;
            if (direction > 0) this.scroller.appendChild(child);
            else this.scroller.insertBefore(child, this.scroller.firstElementChild);
            this.position -= direction * advance;
        }
        this.mutationObserver.observe(this.scroller, { childList: true });
    }

    destroy() {
        cancelAnimationFrame(this.frame);
        this.resizeObserver.disconnect();
        this.mutationObserver.disconnect();
        this.listeners.forEach(remove => remove());
    }
}
