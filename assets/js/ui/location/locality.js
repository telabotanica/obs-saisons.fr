import {closeOverlay} from "../overlay/overlay-close";

/**************************************************
 * NOMINATIM
 **************************************************/
//
const NOMINATIM_OSM_URL = 'https://nominatim.openstreetmap.org/search';
const NOMINATIM_OSM_DEFAULT_PARAMS = {
    'format': 'json',
    'countrycodes': 'fr',
    'addressdetails': 1,
    'limit': 10
};
//
/**************************************************
 * DOM
 **************************************************/
//
const $places = $('#ods-places');
const $placeLabel = $places.siblings('label');
const $placesResults = $('.ods-places-results');
const $placesResultsContainer = $('.ods-places-results-container');
const $placesLaunchButton = $('.ods-places-launch');
const $placesCloseButton = $('.ods-places-close');
//
const ESC_KEY_STRING = /^Esc(ape)?/;
/***************************************************/

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
}

OdsPlaces.prototype.init = function() {
    if (0 < $places.length) {

        this.toggleSearchButton();

        $placesLaunchButton.off('click').on('click', this.launchSearch.bind(this) );
        $places.off('blur change DOMAutoComplete keydown').on('blur change DOMAutoComplete keydown', this.launchSearch.bind(this) );
    }
};

OdsPlaces.prototype.launchSearch = function (event) {
    let isValidSearch = valOk($places.val());
    if('keydown' === event.type) {
        if (27 === event.keyCode || ESC_KEY_STRING.test(event.key)) {
            $placesCloseButton.trigger('click');
            $places.focus();
        } else {
            isValidSearch = isValidSearch && (13 === event.keyCode || 'Enter' === event.key);
        }
    }
    if (isValidSearch) {
        event.preventDefault();
        let params = {'q': $places.val()};

        $placeLabel.addClass('loading');

        $.ajax({
            method: "GET",
            url: NOMINATIM_OSM_URL,
            data: {...NOMINATIM_OSM_DEFAULT_PARAMS, ...params},
            success: this.nominatimOsmResponseCallback.bind(this),
            error: () => {
                $placeLabel.removeClass('loading');
                this.resetPlacesSearch();
            }
        });
    }
};

OdsPlaces.prototype.nominatimOsmResponseCallback = function(data) {
    this.resetPlacesSearch();
    $places.siblings('label').removeClass('loading');
    if (0 < data.length) {
        this.searchResults = data;
        this.setSuggestions();
        this.toggleSearchButton(false);
        this.resetOnClick();
        this.onSuggestionSelected();
    }
};

OdsPlaces.prototype.setSuggestions = function() {
    const lthis = this;
    let acceptedSuggestions = [];

    this.searchResults.forEach(function(suggestion) {
        if(lthis.validateSuggestionData(suggestion)) {
            let locality = suggestion['display_name'];
            if (locality && !acceptedSuggestions.includes(locality)) {
                acceptedSuggestions.push(locality);
                $placesResults.append(
                    '<li class="ods-places-suggestion" data-place-id="'+suggestion['place_id']+'" tabindex="-1">' +
                        locality +
                    '</li>'
                );
            }
        }
    });
    $placesResultsContainer.removeClass('hidden');
    $('.ods-places-suggestion').first().focus();
};

OdsPlaces.prototype.validateSuggestionData = function(suggestion) {
    let validGeometry = undefined !== suggestion.lat && undefined !== suggestion.lon,
        validAddressData = undefined !== suggestion.address,
        validDisplayName = undefined !== suggestion['display_name'];

    return (validGeometry && validAddressData && validDisplayName);
};

OdsPlaces.prototype.onSuggestionSelected = function() {
    const lthis = this;
    $('.ods-places-suggestion').off('click').on('click', function () {
        let $thisSuggestion = $(this),
            suggestion = lthis.searchResults.find(suggestion => suggestion['place_id'] === $thisSuggestion.data('placeId'));

        $places.val($thisSuggestion.text());
        lthis.clientCallback(suggestion);
        $placesCloseButton.trigger('click');
    }).off('keydown').on('keydown', function (event) {
        event.preventDefault();

        let $thisSuggestion = $(this);

        if (13 === event.keyCode || 'Enter' === event.key) {
            $thisSuggestion.trigger('click');
        } else if (38 === event.keyCode || 'ArrowUp'=== event.key) {
            if(0 < $thisSuggestion.prev().length) {
                $thisSuggestion.prev().focus();
            } else {
                $places.focus();
            }
        } else if((40 === event.keyCode || 'ArrowDown' === event.key) && 0 < $thisSuggestion.next().length) {
            $thisSuggestion.next().focus();
        } else if (27 === event.keyCode || ESC_KEY_STRING.test(event.key)) {
            $placesCloseButton.trigger('click');
            $places.focus();
        }
    });
};

OdsPlaces.prototype.resetOnClick = function () {
    const lthis = this;
    $placesCloseButton.off('click').on('click', function (event) {
        event.preventDefault();
        lthis.resetPlacesSearch();
    });
};

OdsPlaces.prototype.toggleSearchButton = function(isShow = true) {
    $placesLaunchButton.toggleClass('hidden',!isShow );
    $placesCloseButton.toggleClass('hidden', isShow);
};

OdsPlaces.prototype.resetPlacesSearch = function() {
    $places.val('');
    this.toggleSearchButton();
    $placesResultsContainer.addClass('hidden');
    $placesResults.empty();
};
