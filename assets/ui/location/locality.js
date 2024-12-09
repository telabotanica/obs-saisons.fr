import {debounce} from "../../lib/debounce";
import { StationLocation } from "../stations-observations/locate-station";
const NOMINATIM_OSM_URL = 'https://nominatim.openstreetmap.org/search';
const NOMINATIM_OSM_DEFAULT_PARAMS = {
    'format': 'json',
    'countrycodes': 'fr',
    'addressdetails': 1,
    'limit': 10
};
const ESC_KEY_STRING = /^Esc(ape)?/;

export function OdsPlaces(clientCallback) {

    /**
     * used in this.onSuggestionSelected()
     *
     * @callback clientCallback
     * @param {String} locality
     * @param {{lat: number, lng: number }} coordinates
     */
    this.clientCallback = clientCallback;
    this.searchResults = [];
    this.cities = [];
}

OdsPlaces.prototype.init = function() {
    this.initForm();
    this.initEvts();
};

OdsPlaces.prototype.initForm = function() {
    this.places = $('#ods-places');
    this.placeLabel = this.places.siblings('label');
    this.placesResults = $('.ods-places-results');
    this.placesResultsContainer = $('.ods-places-results-container');
    this.placesCloseButton = $('.ods-places-close');
    this.placesLatitude = $('#station_latitude');
    this.placesLongitude = $('#station_longitude');
    this.placesTownLatitude = $('#station_town_latitude');
    this.placesTownLongitude = $('#station_town_longitude');
    this.placesTown = $("#station_locality");
};

OdsPlaces.prototype.initEvts = function() {
    if (0 < this.places.length) {

        this.toggleCloseButton(false);
        this.places.off('input').on('input', debounce(this.launchSearch.bind(this), 500));
        this.places.off('keydown').on('keydown', evt => {
            const suggestionEl = $('.ods-places-suggestion');

            if ('Escape' === evt.key || ESC_KEY_STRING.test(evt.key)) {
                evt.preventDefault();

                this.placesCloseButton.trigger('click');
                this.places.on('focus');
            } else if(('ArrowDown' === evt.key) && 0 <  suggestionEl.length) {
                evt.preventDefault();

                suggestionEl.first().on('focus');
            }
        });
    }
};

OdsPlaces.prototype.launchSearch = function (evt) {
    if (!!this.places.val()) {
        const params = {'q':  this.places.val()};

        this.placeLabel.addClass('loading');
        $.ajax({
            method: "GET",
            url: NOMINATIM_OSM_URL,
            data: {...NOMINATIM_OSM_DEFAULT_PARAMS, ...params},
            success: this.nominatimOsmResponseCallback.bind(this),
            error: () => {
                this.placeLabel.removeClass('loading');
            }
        });
    }
};

OdsPlaces.prototype.searchCity = function (city) {
    const params = {'city':  city};

    return $.ajax({
        method: "GET",
            url: NOMINATIM_OSM_URL,
            data: {...NOMINATIM_OSM_DEFAULT_PARAMS, ...params},
            success:async function(response){
                var cities = await response;
                var latCity = cities[0].lat;
                latCity = Math.round(latCity * 1000000) / 1000000;
                var lngCity = cities[0].lon;
                lngCity = Math.round(lngCity * 1000000) / 1000000;
                $('#station_town_latitude').val(latCity);
                $('#station_town_longitude').val(lngCity);
            }
    });
    
};

OdsPlaces.prototype.nominatimOsmResponseCallback = function(data) {
    this.places.siblings('label').removeClass('loading');
    if (0 < data.length) {
        this.searchResults = data;
        this.setSuggestions();
        this.toggleCloseButton();
        this.resetOnClick();
        this.onSuggestionSelected();
    }
};

OdsPlaces.prototype.setSuggestions = function() {
    const lthis = this,
        acceptedSuggestions = [];

    this.placesResults.empty();
    this.searchResults.forEach(suggestion => {
        if(lthis.validateSuggestionData(suggestion)) {
            const locality = suggestion['display_name'];

            if (locality && !acceptedSuggestions.includes(locality)) {
                acceptedSuggestions.push(locality);
                lthis.placesResults.append(
                    '<li class="ods-places-suggestion" data-place-id="'+suggestion['place_id']+'" tabindex="-1">' +
                        locality +
                    '</li>'
                );
            }
        }
    });
    this.placesResultsContainer.removeClass('hidden');
};

OdsPlaces.prototype.validateSuggestionData = function(suggestion) {
    const validGeometry = undefined !== suggestion.lat && undefined !== suggestion.lon,
        validAddressData = undefined !== suggestion.address,
        validDisplayName = undefined !== suggestion['display_name'];
        

    return (validGeometry && validAddressData && validDisplayName);
};

OdsPlaces.prototype.onSuggestionSelected = function() {
    const lthis = this;
    
    $('.ods-places-suggestion').off('click').on('click', async function (evt) {
        const $thisSuggestion = $(this),suggestion = lthis.searchResults.find(suggestion => suggestion['place_id'] === $thisSuggestion.data('placeId'));
        evt.preventDefault();
        lthis.places.val($thisSuggestion.text());
        var town = suggestion['address']['municipality'];
        lthis.placesTown.val(town);
        var lat = suggestion['lat'];
        lat = Math.round(lat * 1000000) / 1000000;
        var lng = suggestion['lon'];
        lng = Math.round(lng * 1000000) / 1000000;
        lthis.placesLatitude.val(lat);
        lthis.placesLongitude.val(lng);
        var sl = new StationLocation();
        sl.updateCoordinatesFields();
        sl.getAltitude();
        lthis.placesCloseButton.trigger('click');

    }).off('keydown').on('keydown', function (evt) {
        evt.preventDefault();

        const $thisSuggestion = $(this);

        if ('ArrowUp' === evt.key || 'Enter' === evt.key) {
            $thisSuggestion.trigger('click');
        } else if ('ArrowUp'=== evt.key) {
            if(0 < $thisSuggestion.prev().length) {
                $thisSuggestion.prev().on('focus');
            } else {
                lthis.places.on('focus');
            }
        } else if(( 'ArrowDown' === evt.key) && 0 < $thisSuggestion.next().length) {
            $thisSuggestion.next().on('focus');
        } else if ('Escape' === evt.key || ESC_KEY_STRING.test(evt.key)) {
            lthis.placesCloseButton.trigger('click');
            lthis.places.on('focus');
        }
    });
    
};

OdsPlaces.prototype.resetOnClick = function () {
    const lthis = this;

    this.placesCloseButton.off('click').on('click', function (event) {
        event.preventDefault();
        lthis.resetPlacesSearch();
    });
};

OdsPlaces.prototype.toggleCloseButton = function(isShow = true) {
    this.placesCloseButton.toggleClass('hidden', !isShow);
    $('.ods-places-search-icon').toggleClass('hidden', isShow);
};

OdsPlaces.prototype.resetPlacesSearch = function() {
    this.places.val('');
    this.toggleCloseButton(false);
    this.placesResultsContainer.addClass('hidden');
    this.placesResults.empty();
};

