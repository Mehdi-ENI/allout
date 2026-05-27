import { createMap, createMarker, moveMarker, setMapView } from './map-utils.js';

/**
 * Initialisation de la carte Leaflet pour le formulaire de modification d'un lieu.
 *
 * Si des coordonnées valides sont présentes dans les data-attributes du conteneur
 * (mode modification), la carte est centrée sur le lieu existant et un marqueur
 * y est positionné. Sinon (mode création sans coordonnées), la carte est centrée
 * sur Paris par défaut.
 *
 * Au clic sur la carte, les champs latitude et longitude du formulaire Symfony
 * sont mis à jour et le marqueur est déplacé ou créé.
 */
document.addEventListener('DOMContentLoaded', () => {

    /** @type {HTMLElement|null} Conteneur de la carte portant les data-attributes lat/lng */
    const mapElement = document.getElementById('map-create-lieu');
    if (!mapElement) {
        return;
    }

    /** @type {number} Latitude existante lue depuis le data-attribute, NaN si absente */
    const existingLat = parseFloat(mapElement.dataset.lat);

    /** @type {number} Longitude existante lue depuis le data-attribute, NaN si absente */
    const existingLng = parseFloat(mapElement.dataset.lng);

    /**
     * Indique si des coordonnées valides et non nulles sont disponibles.
     * Utilisé pour distinguer un lieu sans coordonnées d'un lieu positionné.
     *
     * @type {boolean}
     */
    const hasCoords = !isNaN(existingLat) && !isNaN(existingLng)
        && existingLat !== 0 && existingLng !== 0;

    /** @type {L.Map} Instance de la carte Leaflet */
    const map = createMap(
        'map-create-lieu',
        hasCoords ? existingLat : 48.8566,
        hasCoords ? existingLng : 2.3522,
        hasCoords ? 15 : 8
    );

    /**
     * Marqueur positionné sur les coordonnées existantes du lieu.
     * Null si aucune coordonnée valide n'est disponible.
     *
     * @type {L.Marker|null}
     */
    let marker = hasCoords ? createMarker(map, existingLat, existingLng) : null;

    /**
     * Au clic sur la carte, met à jour les champs latitude et longitude
     * du formulaire Symfony et déplace ou crée le marqueur.
     *
     * Les valeurs sont arrondies à 6 décimales pour éviter les problèmes
     * de précision flottante avec les contraintes de validation Symfony.
     *
     * @param {L.LeafletMouseEvent} e L'événement de clic Leaflet
     */
    map.on('click', (e) => {
        const latitude = parseFloat(e.latlng.lat.toFixed(6));
        const longitude = parseFloat(e.latlng.lng.toFixed(6));

        document.getElementById('lieu_latitude').value = latitude;
        document.getElementById('lieu_longitude').value = longitude;

        if (marker) {
            moveMarker(marker, latitude, longitude);
        } else {
            marker = createMarker(map, latitude, longitude);
        }
    });

    /**
     * Forcer le recalcul de la taille de la carte après rendu complet du DOM.
     * Nécessaire lorsque la carte est affichée dans un conteneur dont les dimensions
     * sont calculées après le chargement initial (carte dans une modale Bootstrap, etc.).
     */
    setTimeout(() => {
        map.invalidateSize();
    }, 200);
});
