import '../../css/components/image_cropper.css';
import { cropRectangle } from './image_crop_geometry.js';

class ImageCropper extends HTMLElement {
    connectedCallback() {
        if (this.events) return;
        this.settings = JSON.parse(this.dataset.settings);
        this.source = this.querySelector('[data-crop-source]');
        this.output = this.querySelector('[data-crop-output]');
        this.editor = this.querySelector('[data-crop-editor]');
        this.canvas = this.querySelector('canvas');
        this.status = this.querySelector('[data-crop-status]');
        this.emptyMessage = this.status.textContent;
        this.controls = Object.fromEntries([...this.querySelectorAll('[data-crop-control]')]
            .map(input => [input.dataset.cropControl, input]));
        this.version = 0;
        this.events = new AbortController();
        const on = (element, event, listener) => element.addEventListener(event, listener, { signal: this.events.signal });

        on(this.source, 'change', () => this.load());
        for (const input of Object.values(this.controls)) on(input, 'input', () => this.draw());
        on(this.querySelector('[data-crop-apply]'), 'click', () => this.apply());
        on(this.querySelector('[data-crop-reset]'), 'click', () => this.reset());
        on(this.querySelector('[data-crop-clear]'), 'click', () => this.clear());
        on(this.canvas, 'pointerdown', event => {
            if (!this.image) return;
            this.canvas.setPointerCapture(event.pointerId);
            this.drag = { x: event.clientX, y: event.clientY };
        });
        on(this.canvas, 'pointermove', event => this.move(event));
        on(this.canvas, 'lostpointercapture', () => { this.drag = null; });
        const form = this.closest('form');
        if (form) {
            on(form, 'submit', event => {
                if (this.source.files.length && !this.output.files.length) {
                    event.preventDefault();
                    this.message('apply_first');
                    this.querySelector('[data-crop-apply]').focus();
                }
            });
            on(form, 'reset', () => this.clear());
        }
    }

    disconnectedCallback() {
        this.events?.abort();
        this.events = null;
        this.version++;
        this.image?.close();
        this.image = null;
    }

    message(key) {
        this.status.textContent = this.settings.messages[key];
    }

    async load() {
        const file = this.source.files[0];
        this.clearImage();
        if (!file) {
            this.status.textContent = this.emptyMessage;
            return;
        }
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > this.settings.maxBytes) {
            this.source.value = '';
            this.message('invalid_source');
            return;
        }
        const version = this.version;
        try {
            const image = await createImageBitmap(file, { imageOrientation: 'from-image' });
            if (version !== this.version) {
                image.close();
                return;
            }
            if (image.width * image.height > this.settings.maxPixels) {
                image.close();
                this.source.value = '';
                this.message('invalid_pixels');
                return;
            }
            this.image = image;
            this.editor.hidden = false;
            this.reset();
        } catch {
            if (version === this.version) {
                this.source.value = '';
                this.message('decode_error');
            }
        }
    }

    rectangle() {
        return cropRectangle(this.image.width, this.image.height,
            this.canvas.width, this.canvas.height,
            Number(this.controls.zoom.value), Number(this.controls.horizontal.value), Number(this.controls.vertical.value));
    }

    draw() {
        if (!this.image) return;
        this.version++;
        this.output.value = '';
        const crop = this.rectangle();
        const context = this.canvas.getContext('2d');
        context.clearRect(0, 0, this.canvas.width, this.canvas.height);
        context.drawImage(this.image, crop.x, crop.y, crop.width, crop.height,
            0, 0, this.canvas.width, this.canvas.height);
        this.message('editing');
    }

    move(event) {
        if (!this.drag || !this.image) return;
        const crop = this.rectangle();
        const bounds = this.canvas.getBoundingClientRect();
        const dx = event.clientX - this.drag.x;
        const dy = event.clientY - this.drag.y;
        this.drag = { x: event.clientX, y: event.clientY };
        const update = (control, delta, available) => {
            if (available > 0) control.value = Math.max(0, Math.min(1, Number(control.value) - delta / available));
        };
        update(this.controls.horizontal, dx * crop.width / bounds.width, this.image.width - crop.width);
        update(this.controls.vertical, dy * crop.height / bounds.height, this.image.height - crop.height);
        this.draw();
    }

    reset() {
        this.controls.zoom.value = 1;
        this.controls.horizontal.value = 0.5;
        this.controls.vertical.value = 0.5;
        this.draw();
    }

    apply() {
        if (!this.image) return;
        const version = this.version;
        this.canvas.toBlob(blob => {
            if (version !== this.version) return;
            if (!blob) {
                this.message('decode_error');
                return;
            }
            const transfer = new DataTransfer();
            transfer.items.add(new File([blob], 'banner-crop.png', { type: 'image/png' }));
            this.output.files = transfer.files;
            this.output.dispatchEvent(new Event('change', { bubbles: true }));
            this.message('ready');
        }, 'image/png');
    }

    clearImage() {
        this.version++;
        this.image?.close();
        this.image = null;
        this.drag = null;
        this.output.value = '';
        this.editor.hidden = true;
    }

    clear() {
        this.clearImage();
        this.source.value = '';
        this.status.textContent = this.emptyMessage;
    }
}

if (!customElements.get('image-cropper')) customElements.define('image-cropper', ImageCropper);
