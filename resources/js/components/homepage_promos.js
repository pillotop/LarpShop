import '../app.js';
import { DeferredSection } from './deferred_section.js';
import { initBroadSelect } from './broad_select.js';
import { CarouselScrollable } from './carousel_scrollable_controller.js';
import { TwoArrowScroller } from './two_arrow_scroller.js';
import { syncProductComparison } from './storefront_ux.js';
import { CategoryDiscovery } from './category_discovery.js';
import { createSharedPromoFetch, selectPromoSlot } from './homepage_promo_slots.js';

function initialize(content) {
    initBroadSelect(content);
    content.querySelectorAll('.carousel-scrollable').forEach(element => new CarouselScrollable(element));
    content.querySelectorAll('.two-arrow-scroller').forEach(element => new TwoArrowScroller(element));
    syncProductComparison(content);
    // Existing quantity-picker observation and delegated wishlist/compare handlers
    // already support cards inserted after the initial page load.
}

const promoFetch = createSharedPromoFetch();
document.querySelectorAll('[data-deferred-section]').forEach(element => {
    const options = element.dataset.homepagePromoId
        ? { initialize, fetchPage: promoFetch, selectContent: selectPromoSlot }
        : { initialize };
    new DeferredSection(element, options).start();
});

document.querySelectorAll('[data-category-discovery]').forEach(element => {
    new CategoryDiscovery(element, { initialize: syncProductComparison }).start();
});
