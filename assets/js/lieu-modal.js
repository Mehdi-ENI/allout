import { createMap, createMarker, moveMarker } from './map-utils.js';

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('lieuModal');
    if (!modal) return;

    let map = null;
    let marker = null;

    modal.addEventListener('shown.bs.modal', () => {
        const mapElement = document.getElementById('map-create-lieu');
        if (!mapElement) return;

        if (!map) {
            map = createMap('map-create-lieu', 48.1, -1.7, 12);
            marker = createMarker(map, 48.1, -1.7, "Choisissez un lieu");

            map.on('click', (e) => {
                const { lat, lng } = e.latlng;
                moveMarker(marker, lat, lng);

                const latInput = document.querySelector('[name$="[latitude]"]');
                const lngInput = document.querySelector('[name$="[longitude]"]');
                if (latInput && lngInput) {
                    latInput.value = lat;
                    lngInput.value = lng;
                }
            });
        } else {
            map.invalidateSize();
        }
    });

    modal.addEventListener('hidden.bs.modal', () => {
        if (marker) {
            marker.remove();
            marker = null;
        }
        map = null;
        lieuForm.reset();
    });

    const lieuForm = document.getElementById('lieu-form');
    if (!lieuForm) return;

    lieuForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        try {
            const response = await fetch(lieuForm.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new FormData(lieuForm),
            });

            const data = await response.json();

            if (response.ok) {
                const lieuSelect = document.querySelector('select[name*="[lieu]"]');
                if (lieuSelect) {
                    const option = new Option(data.nom, data.id, true, true);
                    option.dataset.lat = data.latitude;
                    option.dataset.lng = data.longitude;
                    lieuSelect.add(option);
                    lieuSelect.dispatchEvent(new Event('change'));
                }

                bootstrap.Modal.getInstance(modal).hide();

            } else {
                console.error('Erreurs de validation :', data.errors);
            }

        } catch (err) {
            console.error('Erreur réseau :', err);
        }
    });
});
