/* global L */

export function createMap(elementId, latitude, longitude, zoom = 13) {

    const map = L.map(elementId).setView([latitude, longitude], zoom);

    L.tileLayer(
        'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }
    ).addTo(map);

    return map;
}

export function createMarker(map, latitude, longitude, popupText = null) {

    const marker = L.marker([latitude, longitude]).addTo(map);

    if (popupText) {
        marker.bindPopup(popupText);
    }

    return marker;
}

export function moveMarker(marker, latitude, longitude) {
    marker.setLatLng([latitude, longitude]);
}

export function setMapView(map, latitude, longitude, zoom = 13) {
    map.setView([latitude, longitude], zoom);
}
