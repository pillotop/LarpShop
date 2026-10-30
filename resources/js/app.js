import * as bootstrap from 'bootstrap';
import './components/cart_store.js';
import './components/cart_view.js';

// Existing Blade event handlers use the Bootstrap API through this global.
window.bootstrap = bootstrap;
