import * as L from 'leaflet';
import { GestureHandling } from "leaflet-gesture-handling";
import "leaflet/dist/leaflet.css";
import "leaflet-gesture-handling/dist/leaflet-gesture-handling.css";

export const DEFAULT_POSITION = {
    lat: 47.0504,
    lng: 2.2347
};
export const DEFAULT_ZOOM = 6;

const MARKER_ICON = L.Icon.extend({
    options: {
        shadowUrl: '/media/map/marker-shadow.png',
        iconUrl: '/media/map/marker-icon.png',
        iconSize: [24,40],
        iconAnchor: [12,40]//correctly replaces the dot of the pointer
    }
});

export function createMap(
    elementIdAttr,
    lat = DEFAULT_POSITION.lat,
    lng = DEFAULT_POSITION.lng,
    zoom = DEFAULT_ZOOM,
    hasZoomControl = true,
    isDraggable = true,
    hasMarker = false,
) {
    const map = L.map(elementIdAttr, {zoomControl: hasZoomControl, gestureHandling: true}).setView([lat, lng], zoom);
    map.markers = [];

    L.tileLayer(
    'https://a.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', {
        attribution: 'Data © <a href="http://osm.org/copyright">OpenStreetMap</a>',
        maxZoom: 18
    }).addTo(map);

    map.addLayer(new L.FeatureGroup());

    if (hasMarker) {
        const marker = createMarker({'lat': lat, 'lng': lng});

        map.addLayer(marker);
        map.markers.push(marker);
    }

    return map;
}

export const createMarker = (
    coordinates = DEFAULT_POSITION,
    isDraggable = true,
    hasIcon = false,
) => {
    const options = {draggable: isDraggable};

    if(hasIcon) {
        options.icon = new MARKER_ICON();
    }

    return new L.Marker(coordinates, options);
};
