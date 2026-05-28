import { createMap, createMarker, moveMarker } from './map-utils.js';

document.addEventListener('DOMContentLoaded', () => {

    const lieuForm = document.getElementById('lieu-form');

    if (!lieuForm) {
        return;
    }

    let mapCreateLieu = null;
    let marker = null;

    document.getElementById('lieuModal').addEventListener('shown.bs.modal', function () {
        if (!mapCreateLieu) {
            mapCreateLieu = createMap('map-create-lieu', 48.1, -1.7);

            mapCreateLieu.on('click', (e) => {
                const latitude = e.latlng.lat;
                const longitude = e.latlng.lng;

                document.getElementById('lieu_latitude').value = latitude;
                document.getElementById('lieu_longitude').value = longitude;

                if (marker) {
                    moveMarker(marker, latitude, longitude);
                } else {
                    marker = createMarker(mapCreateLieu, latitude, longitude);
                }
            });

        } else {
            mapCreateLieu.invalidateSize();
        }
    });

    lieuForm.addEventListener('submit', async (e) => {

        e.preventDefault();

        const formData = new FormData(lieuForm);

        const response = await fetch(
            lieuForm.action,
            {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }
        );

        const contentType = response.headers.get('content-type');

        // CAS ERREUR : Symfony renvoie du HTML
        if (!contentType || !contentType.includes('application/json')) {

            const html = await response.text();

            console.log(html);

            alert('Le formulaire lieu contient des erreurs.');

            return;
        }

        // CAS OK : JSON
        const result = await response.json();

        console.log(result);

        const select = document.getElementById('sortie_lieu');

        const option = new Option(
            result.nom,
            result.id,
            true,
            true
        );

        option.dataset.lat = result.latitude;
        option.dataset.lng = result.longitude;

        select.add(option);
        select.dispatchEvent(new Event('change'));

        const modalElement = document.getElementById('lieuModal');
        const modal = bootstrap.Modal.getInstance(modalElement);
        modal.hide();

        lieuForm.reset();
        modal.hide();

        lieuForm.reset();

        if (marker) {
            marker.remove(); // supprime le marqueur de la carte
            marker = null;
        }
    });

});
