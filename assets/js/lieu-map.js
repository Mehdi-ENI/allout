document.addEventListener('DOMContentLoaded', () => {

    const modalElement = document.getElementById('lieuModal');

    if (!modalElement) {
        return;
    }

    let map = null;
    let marker = null;

    modalElement.addEventListener('shown.bs.modal', () => {

        // évite de recréer la map plusieurs fois
        if (map !== null) {
            map.invalidateSize();
            return;
        }

        map = L.map('map-create-lieu').setView([48.8566, 2.3522], 13);

        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        map.on('click', (e) => {

            const latitude = e.latlng.lat;
            const longitude = e.latlng.lng;

            // remplit les champs du formulaire
            document.getElementById('lieu_latitude').value = latitude;
            document.getElementById('lieu_longitude').value = longitude;

            // déplace le marker si déjà existant
            if (marker) {
                marker.setLatLng(e.latlng);
            } else {
                marker = L.marker(e.latlng).addTo(map);
            }
        });

        // force le recalcul visuel de Leaflet dans une modale Bootstrap
        setTimeout(() => {
            map.invalidateSize();
        }, 200);
    });

});
