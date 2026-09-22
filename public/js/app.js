const dropdownButtons = [ "categories", "products", "pricing", "enterprise", "events" ];
dropdownButtons.forEach((s) => {
    const arrow = Snap.select(`#dropdown-arrow-path-${s}`);
    const button = document.querySelector(`#login-trigger-${s}`);

    if (button == null) {
        return;
    }

    const closedPath = "M5.75 8.75 L10 13 L14.25 8.75";
    const openPath = "M5.75 13 L10 8.75 L14.25 13";

    button.addEventListener("mouseenter", () => arrow.animate({d: openPath}, 150));
    button.addEventListener("mouseleave", () => arrow.animate({d: closedPath}, 150));

    let isOpen = false;
    button.addEventListener("click", () => {
        isOpen = !isOpen;
        if (isOpen) {
            arrow.animate({d: closedPath}, 150);
        } else {
            arrow.animate({d: openPath}, 150);
        }
    });
});
