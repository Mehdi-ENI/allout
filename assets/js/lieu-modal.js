import { createMap, createMarker, moveMarker } from './map-utils.js';

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('lieuModal');

    if (!modal) return;

    let map;
    let marker;

    modal.addEventListener('shown.bs.modal', () => {

        const mapElement = document.getElementById('map-create-lieu');
        if (!mapElement || map) return;

        // Paris par défaut
        map = createMap('map-create-lieu', 48.8566, 2.3522, 12);
        marker = createMarker(map, 48.8566, 2.3522, "Choisissez un lieu");

        map.on('click', (e) => {
            const { lat, lng } = e.latlng;

            moveMarker(marker, lat, lng);

            // champs du form Symfony
            const latInput = document.querySelector('[name$="[latitude]"]');
            const lngInput = document.querySelector('[name$="[longitude]"]');

            if (latInput && lngInput) {
                latInput.value = lat;
                lngInput.value = lng;
            }
        });
    });
});
