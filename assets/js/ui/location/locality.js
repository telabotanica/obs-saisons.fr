import domready from "mf-js/modules/dom/ready";

/**************************************************
 * LOCATION
 **************************************************/
//
import {stationLocation} from "../overlay/overlay-open";
//
/**************************************************
 * NOMINATIM
 **************************************************/
//
const NOMINATIM_OSM_URL = 'https://nominatim.openstreetmap.org/';
const NOMINATIM_OSM_DEFAULT_PARAMS = {
    'format': 'geojson',
    'countrycodes': 'fr',
    'addressdetails': 1
};
//
/**************************************************
 * PLACES
 **************************************************/
//
const places = require('places.js');
const PLACES_CONFIG = {
    appId: 'plV00W9UJC60',
    apiKey: 'b8630d75d81f1343304ac3547a2994af'
};
const PLACES_SELECTOR = '.ods-places';
const $places = $(PLACES_SELECTOR);
var placesAutocomplete = {};

/***************************************************/

export function initSearchLocality() {
    if (0 < $places.length && valOk($places.val())) {
        $places.siblings('button.ap-input-icon').toggle();
    }

    //Algolia places configuration
    placesAutocomplete.on('change', function (event) {
        stationLocation.handleNewLocation(event.suggestion.latlng);
    });
}

export function onLocalityField(localityFieldId) {
    let $locality = $('#'+localityFieldId);

    if (0 < $locality.length) {
        let $label = $locality.siblings('label');

        $locality.off('blur').on('blur', function () {
            if (valOk($locality.val())) {
                let params = {
                    'limit': 1,
                    'q': encodeURIComponent($locality.val()),
                };

                $label.addClass('loading');

                $.ajax({
                    method: "GET",
                    url: NOMINATIM_OSM_URL,
                    data: {...NOMINATIM_OSM_DEFAULT_PARAMS, ...params},
                    success: data => {
                        $label.removeClass('loading');
                        if (0 < data.features.length) {
                            let localityData = data.features[0],
                                coordinates = localityData.geometry.coordinates,
                                addressData = localityData.properties.address,
                                //find the most precise name type for locality value
                                locationNameType = ['village', 'city', 'locality', 'municipality'].find(locationNameType => addressData[locationNameType] !== undefined);

                            if (valOk(locationNameType)) {
                                $locality.val(addressData[locationNameType]);
                            }
                            stationLocation.handleNewLocation({lat: coordinates[1], lng: coordinates[0]});
                            removePlaces();
                        } else {
                            $locality.val('');
                        }
                    },
                    error: function () {
                        $label.removeClass('loading');
                    }
                });
            }
        });
    }
}

const removePlaces = () => {
    if (0 < $places.length && valOk($places.val())) {
        $('.ap-icon-clear').trigger('click');
    }
};

domready(function() {
    removePlaces();
    if (0 < $places.length) {
        placesAutocomplete = places({
            appId: PLACES_CONFIG.appId,
            apiKey: PLACES_CONFIG.apiKey,
            container: PLACES_SELECTOR,
            language: 'fr',
            countries: ['fr']
        });
    }
});
