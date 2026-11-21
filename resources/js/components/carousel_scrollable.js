import '../app.js';
import { CarouselScrollable } from './carousel_scrollable_controller.js';

document.querySelectorAll('.carousel-scrollable').forEach(element => new CarouselScrollable(element));
