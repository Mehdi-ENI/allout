/* global L */
import { createMap, createMarker, moveMarker, setMapView } from './map-utils.js';

document.addEventListener('DOMContentLoaded', () => {

    const select = document.getElementById('sortie_lieu');

    if (!select) {
        return;
    }

    const map = createMap('map-sortie', 48.8566, 2.3522)
    let marker = null;
    select.addEventListener('change', () => {

        const selectedOption = select.options[select.selectedIndex];
        const lat = parseFloat(selectedOption.dataset.lat);
        const lng = parseFloat(selectedOption.dataset.lng);

        if (isNaN(lat) || isNaN(lng)) {
            console.error('Coordonnées invalides');
            return;
        }

        setMapView(map, lat, lng);

        if (marker) {
            moveMarker(marker, lat, lng);
        } else {
            marker = createMarker(map, lat, lng);
        }
    });
});
