/**************************************************
 * LEAFLET
 **************************************************/
//
import L from 'leaflet';
import 'leaflet-draw';
import 'leaflet.markercluster';
//

/**************************************************
 * CREATE MAP
 **************************************************/
//
import {createMap, DEFAULT_POSITION, DEFAULT_ZOOM} from "../create-map";
export const DEFAULT_CITY_ZOOM = 12;
const DEFAULT_MAP_ID_ATTR = 'map';

/***************************************************/

export function Location(mapIdAttr = DEFAULT_MAP_ID_ATTR) {
    this.mapIdAttr = mapIdAttr;
    this.setUpdateMapElement();
    this.map = {};
    this.setDefaultMapData();
}

Location.prototype.setUpdateMapElement = function() {
    this.$mapEl = $('#'+this.mapIdAttr);
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
    if(valOk(coordinates)) {
        this.coordinates = coordinates;
        this.zoom = DEFAULT_CITY_ZOOM;
        this.setMapPosition();
        this.triggerLocationEvent();
    }
};

Location.prototype.formatCoordinates = function (coordinates) {
    let lat = Number.parseFloat(coordinates.lat),
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
    let locationEvent = $.Event('location');
    this.$mapEl.trigger(locationEvent);
};

Location.prototype.setMapPosition = function () {
    if(undefined !== this.map.marker) {
        let latLng = new L.LatLng(this.coordinates.lat, this.coordinates.lng);
        // updates map
        this.map.setView(latLng);
        this.map.marker.setLatLng(latLng, {draggable: 'true'});
    }
};

Location.prototype.toggleMap = function () {
    const lthis = this;

    $('.open-map-button').off('click').on('click', function (event) {
        event.preventDefault();
        $(this).find('span').toggleClass('hidden');
        lthis.$mapEl.toggleClass('hidden');
        if (!lthis.$mapEl.hasClass('hidden')) {
            lthis.initMap();
        } else {
            lthis.closeMap();
        }
    });
};

Location.prototype.initMap = function() {
    this.map = this.createLocationMap(this.coordinates,this.zoom);
    // interactions with map
    this.map.on('click', function(event) {
        this.handleNewLocation(event.latlng);
    }.bind(this));

    this.map.marker.on('dragend', function() {
        this.handleNewLocation(this.map.marker.getLatLng());
    }.bind(this));
};

Location.prototype.closeMap = function () {
    // reset map
    this.map = L.DomUtil.get('map');
    if (this.map != null) {
        this.map._leaflet_id = null;
    }
    this.$mapEl.remove();
    $('#open-map').removeClass('hidden');
    $('#close-map').addClass('hidden');
    $('.map-container').append('<div id="'+this.mapIdAttr+'" class="hidden"></div>');
    this.setUpdateMapElement();
};

Location.prototype.createLocationMap = function(
    coordinates = DEFAULT_POSITION,
    zoom = DEFAULT_ZOOM,
    elementIdAttr = DEFAULT_MAP_ID_ATTR,
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
