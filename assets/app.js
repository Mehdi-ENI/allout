import './stimulus_bootstrap.js';

// Bootstrap CSS en premier
import 'bootstrap/dist/css/bootstrap.min.css';
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// Ton CSS après, pour qu'il puisse écraser Bootstrap
import './styles/app.css';

import 'bootstrap-icons/font/bootstrap-icons.css';

import L from 'leaflet';
window.L = L;

import './js/lieu-modal.js';
import './js/map.js';
import './js/lieu-map-create.js';
