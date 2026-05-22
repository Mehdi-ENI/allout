/* global L */
document.addEventListener('DOMContentLoaded', () => {

    const mapElement = document.getElementById('map');

    if (!mapElement) {
        return;
    }

    const latitude = parseFloat(mapElement.dataset.latitude);
    const longitude = parseFloat(mapElement.dataset.longitude);
    const lieuNom = mapElement.dataset.nom;

    if (isNaN(latitude) || isNaN(longitude)) {
        console.error('Coordonnées invalides');
        return;
    }

    const map = L.map('map').setView([latitude, longitude], 13);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    L.marker([latitude, longitude])
        .addTo(map)
        .bindPopup(lieuNom)
        .openPopup();

});
