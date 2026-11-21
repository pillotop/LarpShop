import { DeferredSection } from './deferred_section.js';
import { TwoArrowScroller } from './two_arrow_scroller.js';
import { syncProductComparison } from './storefront_ux.js';

document.querySelectorAll('[data-deferred-similar-products]').forEach(element => {
    new DeferredSection(element, {
        initialize(content) {
            content.querySelectorAll('.two-arrow-scroller').forEach(scroller => new TwoArrowScroller(scroller));
            syncProductComparison(content);
        },
    }).start();
});
