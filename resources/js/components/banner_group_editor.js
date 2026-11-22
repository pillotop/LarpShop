const form = document.querySelector('[data-banner-editor]');

if (form) {
    const list = form.querySelector('[data-editor-cards]');
    const grid = form.querySelector('[data-preview-grid]');
    const layout = form.querySelector('[data-editor-layout]');
    const locale = form.querySelector('[data-editor-locale]');
    const widths = form.querySelector('[data-editor-width]');
    const frame = form.querySelector('[data-editor-frame]');
    const addButton = form.querySelector('[data-editor-add]');
    const counts = JSON.parse(form.dataset.counts);
    const sources = new Map();
    const cards = () => [...list.querySelectorAll('[data-editor-card]')];

    function renumber() {
        cards().forEach((card, index, all) => {
            card.querySelector('[data-card-number]').textContent = index + 1;
            card.querySelector('[data-card-order]').value = index;
            card.querySelectorAll('[name]').forEach(input => {
                input.name = input.name.replace(/^cards\[\d+\]/, `cards[${index}]`);
            });
            card.querySelectorAll('[id], label[for]').forEach(element => {
                const attribute = element.tagName === 'LABEL' ? 'for' : 'id';
                element.setAttribute(attribute, element.getAttribute(attribute).replace(/^card-\d+-/, `card-${index}-`));
            });
            card.querySelector('[data-card-action="up"]').disabled = index === 0;
            card.querySelector('[data-card-action="down"]').disabled = index === all.length - 1;
        });
        addButton.disabled = cards().length >= 5;
    }

    function text(card, field) {
        const english = card.querySelector(`[data-copy-field="${field}"][data-copy-locale="en"]`).value;
        const translated = card.querySelector(`[data-copy-field="${field}"][data-copy-locale="${locale.value}"]`).value;
        return translated.trim() ? translated : english;
    }

    function render() {
        form.querySelector('[data-editor-preview]').dataset.bannerLayout = layout.value;
        frame.dataset.width = widths.value;
        const tiles = cards().map(card => {
            const tile = form.querySelector('[data-editor-tile-template]').content.firstElementChild.cloneNode(true);
            const image = tile.querySelector('img');
            image.src = sources.get(card)?.url || card.dataset.imageUrl || form.dataset.placeholder;
            image.alt = text(card, 'alt_text');
            image.removeAttribute('srcset');
            tile.dataset.presentation = card.querySelector('[data-card-field="presentation"]').value;
            tile.dataset.textTone = card.querySelector('[data-card-field="text_tone"]').value;
            tile.dataset.inactive = !card.querySelector('[data-card-active]').checked;
            for (const axis of ['x', 'y']) {
                tile.style.setProperty(`--banner-focal-${axis}`, `${card.querySelector(`[data-card-field="focal_${axis}"]`).value}%`);
            }
            for (const [field, selector] of Object.entries({
                title: '.banner-card__title', eyebrow: '.banner-card__eyebrow',
                badge_text: '.banner-card__badge', cta_text: '.banner-card__cta',
            })) {
                const value = text(card, field);
                const node = tile.querySelector(selector);
                node.textContent = value || (field === 'title' ? form.dataset.titlePlaceholder : '');
                node.hidden = !value && field !== 'title';
            }
            return tile;
        });
        grid.replaceChildren(...tiles);
        const active = cards().filter(card => card.querySelector('[data-card-active]').checked).length;
        form.querySelector('[data-editor-count]').textContent = form.dataset.countMessage
            .replace(':count', cards().length).replace(':active', active).replace(':required', counts[layout.value]);
    }

    function add() {
        if (cards().length >= 5) return;
        const html = form.querySelector('[data-editor-card-template]').innerHTML.replaceAll('__INDEX__', cards().length);
        const container = document.createElement('template');
        container.innerHTML = html;
        list.append(container.content);
        renumber();
        render();
    }

    function release(card) {
        const source = sources.get(card);
        if (source) URL.revokeObjectURL(source.url);
        sources.delete(card);
    }

    function imageChanged(input) {
        const card = input.closest('[data-editor-card]');
        release(card);
        input.setCustomValidity('');
        const file = input.files[0];
        if (!file) { render(); return; }
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > Number(form.dataset.maxBytes)) {
            input.setCustomValidity(form.dataset.imageError);
            input.reportValidity();
            render();
            return;
        }
        const source = { url: URL.createObjectURL(file) };
        sources.set(card, source);
        const probe = new Image();
        probe.onload = () => {
            if (sources.get(card) !== source) return;
            if (Math.min(probe.naturalWidth, probe.naturalHeight) < 320
                || Math.max(probe.naturalWidth, probe.naturalHeight) > 8192
                || probe.naturalWidth * probe.naturalHeight > Number(form.dataset.maxPixels)) {
                release(card);
                input.setCustomValidity(form.dataset.imageError);
                input.reportValidity();
            }
            render();
        };
        probe.onerror = () => {
            if (sources.get(card) !== source) return;
            release(card);
            input.setCustomValidity(form.dataset.imageError);
            input.reportValidity();
            render();
        };
        probe.src = source.url;
    }

    form.addEventListener('click', event => {
        const button = event.target.closest('[data-card-action]');
        if (!button) return;
        const card = button.closest('[data-editor-card]');
        if (button.dataset.cardAction === 'remove') {
            release(card);
            card.remove();
        } else if (button.dataset.cardAction === 'up' && card.previousElementSibling) {
            list.insertBefore(card, card.previousElementSibling);
        } else if (button.dataset.cardAction === 'down' && card.nextElementSibling) {
            list.insertBefore(card.nextElementSibling, card);
        }
        renumber();
        render();
    });
    form.addEventListener('input', event => {
        if (!event.target.matches('[data-card-image]')) render();
    });
    form.addEventListener('change', event => {
        if (event.target.matches('[data-card-image]')) imageChanged(event.target);
        else render();
    });
    addButton.addEventListener('click', add);
    form.addEventListener('submit', () => renumber());
    window.addEventListener('pagehide', () => [...sources.keys()].forEach(release));
    if (!cards().length) {
        for (let index = 0; index < counts[layout.value]; index++) add();
    }
    renumber();
    render();
}
