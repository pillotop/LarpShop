<?php

return [
    'title' => 'Homepage layout',
    'hint' => 'Set a different order number for each block. Lower numbers appear first. Banners, product sections, Broad Select and category discovery share this order.',
    'visibility' => 'Disabled, expired, incomplete or empty content stays hidden. Broad Select and category discovery move as whole interactive sections. Banners attached inside a product section move with that section.',
    'add_hint' => 'To add a banner here, assign it to Homepage main banners in its editor. Product sections must use the Homepage main placement. Newly assigned blocks appear after blocks with a saved shared position.',
    'legacy_hint' => 'This page controls shared homepage order. The separate banner and product lists supply the default order only for blocks without a saved shared position.',
    'banner' => 'Banner group', 'promo' => 'Product section',
    'system' => 'Interactive section',
    'broad_select' => 'Broad Select · product carousel',
    'category_discovery' => 'Category discovery · AJAX product selection',
    'order' => 'Order', 'block' => 'Block', 'state' => 'State',
    'enabled' => 'Enabled · follows visibility rules', 'disabled' => 'Disabled',
    'save' => 'Save homepage order', 'saved' => 'Homepage order saved.',
    'empty' => 'Assign a banner or create a homepage product section to start arranging this page.',
    'stale' => 'The homepage arrangement changed. Reload this page and apply your order again.',
];
