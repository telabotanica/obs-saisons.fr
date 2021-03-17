import L from 'leaflet';
import 'leaflet-draw';
import 'leaflet.markercluster';
//
import {createMap, DEFAULT_POSITION, DEFAULT_ZOOM} from "../create-map";
export const DEFAULT_CITY_ZOOM = 12;
const DEFAULT_MAP_ID_ATTR = 'map';

/***************************************************/

export function Location(mapIdAttr = DEFAULT_MAP_ID_ATTR) {
    this.mapIdAttr = mapIdAttr;
    this.mapContainer = document.getElementById(mapIdAttr+'-container');
    this.setUpdateMapElement();
    this.map = {};
    this.setDefaultMapData();
}

Location.prototype.setUpdateMapElement = function() {
    this.mapEl = document.getElementById(this.mapIdAttr);
};

Location.prototype.setDefaultMapData = function () {
    this.coordinates = DEFAULT_POSITION;
    this.zoom = DEFAULT_ZOOM;
};

/**
 * triggers location event
 * @param coordinates.
 * @param coordinates.lat latitude.
 * @param coordinates.lng longitude.
 */
Location.prototype.handleNewLocation = function (coordinates) {
    coordinates = this.formatCoordinates(coordinates);

    if(!!coordinates && !!coordinates.lat && coordinates.lng) {
        this.coordinates = coordinates;
        this.zoom = DEFAULT_CITY_ZOOM;
        this.setMapPosition();
        this.triggerLocationEvent();
    }
};

Location.prototype.formatCoordinates = function (coordinates) {
    const lat = Number.parseFloat(coordinates.lat),
        lng = Number.parseFloat(coordinates.lng);

    if(Number.isNaN(lat) || Number.isNaN(lng)) {
        return null;
    }

    return {
        'lat': lat.toFixed(4),
        'lng': lng.toFixed(5)
    };
};

Location.prototype.triggerLocationEvent = function () {
    this.mapContainer.dispatchEvent(new CustomEvent('location'));
};

Location.prototype.setMapPosition = function () {
    if(undefined !== this.map.marker) {
        const latLng = new L.LatLng(this.coordinates.lat, this.coordinates.lng);
        // updates map
        this.map.setView(latLng);
        this.map.marker.setLatLng(latLng, {draggable: 'true'});
    }
};

Location.prototype.toggleMap = function () {
    const lthis = this;
    $('#map-buttons').off('click').on('click', function (evt) {
        evt.preventDefault();

        const isOpenMapRequired = lthis.mapEl.classList.contains('hidden');

        // if isOpenMapRequired: will hide "open" label on button, and show "close" label
        lthis.toggleMapButtonLabel(isOpenMapRequired);

        lthis.mapEl.classList.toggle('hidden', !isOpenMapRequired);
        if (isOpenMapRequired) {
            lthis.initMap();
        } else {
            lthis.closeMap();
        }
    });
};

Location.prototype.initMap = function() {
    this.map = this.createLocationMap(this.coordinates,this.zoom);
    // interactions with map
    this.map.addEventListener('click', function(evt) {
        this.handleNewLocation(evt.latlng);
    }.bind(this));

    this.map.marker.addEventListener('dragend', function() {
        this.handleNewLocation(this.map.marker.getLatLng());
    }.bind(this));
};

Location.prototype.closeMap = function () {
    // reset map
    this.map = L.DomUtil.get(this.mapIdAttr);
    if (this.map != null) {
        this.map._leaflet_id = null;
    }
    // shows 'open' label on map control button
    this.toggleMapButtonLabel();
    this.mapContainer.innerHTML = '<div id="'+this.mapIdAttr+'" class="hidden"></div>';
    this.setUpdateMapElement();
};

Location.prototype.toggleMapButtonLabel = function(isHideOpenButton = false) {
    document.getElementById('open-map').classList.toggle('hidden',isHideOpenButton);
    document.getElementById('close-map').classList.toggle('hidden',!isHideOpenButton);
};

Location.prototype.createLocationMap = function(
    coordinates = DEFAULT_POSITION,
    zoom = DEFAULT_ZOOM,
    elementIdAttr = this.mapIdAttr,
    hasZoomControl = true,
    isDraggable = true
) {
    return createMap(
        elementIdAttr,
        ...Object.values(coordinates),
        zoom,
        hasZoomControl,
        isDraggable,
        true
    );
};

// Remove the map
Location.prototype.removeMap = function () {
    this.setDefaultMapData();
    this.closeMap();
};
