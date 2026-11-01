import * as bootstrap from 'bootstrap';
import './components/cart_store.js';
import './components/cart_view.js';
import './components/locale_panels.js';
import './components/confirmation_forms.js';

// Existing Blade event handlers use the Bootstrap API through this global.
window.bootstrap = bootstrap;
