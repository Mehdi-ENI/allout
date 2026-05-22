/* global L */

document.addEventListener('DOMContentLoaded', () => {

    const select = document.getElementById('sortie_lieu');

    if (!select) {
        return;
    }

    const map = L.map('map-sortie').setView([48.8566, 2.3522], 13);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    let marker = null;

    select.addEventListener('change', () => {

        const selectedOption = select.options[select.selectedIndex];

        const lat = parseFloat(selectedOption.dataset.lat);
        const lng = parseFloat(selectedOption.dataset.lng);

        if (isNaN(lat) || isNaN(lng)) {
            console.error('Coordonnées invalides');
            return;
        }

        const latLng = [lat, lng];

        map.setView(latLng, 13);

        if (marker) {
            marker.setLatLng(latLng);
        } else {
            marker = L.marker(latLng).addTo(map);
        }
    });

});
