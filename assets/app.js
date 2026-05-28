import './stimulus_bootstrap.js';

import './styles/app.css';

import * as bootstrap from 'bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';

window.bootstrap = bootstrap;

/**
 * Bootstrap Icons
 */
import '../node_modules/bootstrap-icons/font/bootstrap-icons.css';

/**
 * Leaflet
 */
import L from 'leaflet';
window.L = L;

/**
 * Scripts du projet
 */
import './js/lieu-modal.js';
import './js/map.js';
import './js/lieu-map-create.js';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
