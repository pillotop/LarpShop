/** Read translated confirmation text from the form instead of inline JavaScript. */
document.addEventListener('submit', (event) => {
    const form = event.target.closest('form[data-confirm]');

    if (form && !window.confirm(form.dataset.confirm)) {
        event.preventDefault();
    }
});
