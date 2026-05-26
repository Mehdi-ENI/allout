/* global L */
import { createMap, createMarker } from './map-utils.js';

document.addEventListener('DOMContentLoaded', () => {

    const mapElement = document.getElementById('map');
    if (!mapElement) {return;}

    const latitude = parseFloat(mapElement.dataset.latitude);
    const longitude = parseFloat(mapElement.dataset.longitude);
    const lieuNom = mapElement.dataset.nom;

    if (isNaN(latitude) || isNaN(longitude)) {
        console.error('Coordonnées invalides');
        return;
    }

    const map = createMap('map', latitude, longitude);
    const marker = createMarker(map, latitude, longitude, lieuNom);
    marker.openPopup();

});
