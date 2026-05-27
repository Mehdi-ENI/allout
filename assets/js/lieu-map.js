import { createMap, createMarker, moveMarker } from './map-utils.js';

document.addEventListener('DOMContentLoaded', () => {

    const mapElement = document.getElementById('map-create-lieu');
    if (!mapElement) {
        return;
    }

    const map = createMap('map-create-lieu', 48.8566, 2.3522);
    let marker = null;

    map.on('click', (e) => {

        const latitude = e.latlng.lat;
        const longitude = e.latlng.lng;

        document.getElementById('lieu_latitude').value = latitude;
        document.getElementById('lieu_longitude').value = longitude;

        if (marker) {
            moveMarker(marker, latitude, longitude);

        } else {
            marker = createMarker(map, latitude, longitude);
        }
    });

    // utile si carte dans modale Bootstrap
    setTimeout(() => {
        map.invalidateSize();
    }, 200);
});
