class FileDropzone {
    constructor(element) {
        this.element = element;
        this.preview = element.querySelector('[data-file-preview]');
        this.placeholder = element.querySelector('[data-file-placeholder]');
        this.input = element.querySelector('[data-file-input]');
        this.objectUrls = [];
        this.dragDepth = 0;

        this.input.addEventListener('change', () => this.render());

        this.#setupSignals();
    }

    #setupSignals() {
        this.element.addEventListener('dragenter', event => {
            event.preventDefault();
            this.dragDepth++;
            this.element.classList.add('is-dragging');
        });

        this.element.addEventListener('dragover', event => {
            event.preventDefault();
        });

        this.element.addEventListener('dragleave', event => {
            event.preventDefault();
            this.dragDepth = Math.max(0, this.dragDepth - 1);

            if (this.dragDepth === 0) {
                this.element.classList.remove('is-dragging');
            }
        });

        this.element.addEventListener('drop', event => {
            event.preventDefault();

            this.dragDepth = 0;
            this.element.classList.remove('is-dragging');

            const files = [...event.dataTransfer.files];
            if (!files.length) return;

            const transfer = new DataTransfer();

            for (const file of this.input.multiple ? files : files.slice(0, 1)) {
                transfer.items.add(file);
            }

            this.input.files = transfer.files;
            this.input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    render() {
        if (!this.input.multiple) {
            this.placeholder?.classList.toggle(
                'd-none',
                this.input.files.length > 0
            );
        }

        this.objectUrls.forEach(URL.revokeObjectURL);
        this.objectUrls = [];

        this.preview.replaceChildren();

        [...this.input.files].forEach((file, index) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'position-relative';

            if (file.type.startsWith('image/')) {
                const url = URL.createObjectURL(file);
                this.objectUrls.push(url);

                const image = document.createElement('img');
                image.src = url;
                image.alt = file.name;
                image.width = 120;
                image.height = 120;
                image.className = 'rounded object-fit-cover';

                wrapper.append(image);
            } else {
                const filename = document.createElement('span');
                filename.textContent = file.name;

                wrapper.append(filename);
            }

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className =
                'btn btn-danger btn-sm position-absolute top-0 end-0';

            removeButton.textContent = '×';
            removeButton.setAttribute(
                'aria-label',
                `Remove ${file.name}`
            );

            removeButton.addEventListener('click', () => {
                this.removeFile(index);
            });

            wrapper.append(removeButton);
            this.preview.append(wrapper);
        });
    }

    removeFile(index) {
        const transfer = new DataTransfer();

        [...this.input.files].forEach((file, i) => {
            if (i !== index) {
                transfer.items.add(file);
            }
        });

        this.input.files = transfer.files;

        this.input.dispatchEvent(
            new Event('change', { bubbles: true })
        );
    }

    destroy() {
        this.objectUrls.forEach(URL.revokeObjectURL);
        this.objectUrls = [];
    }
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-file-dropzone]')
        .forEach(element => new FileDropzone(element));
});
