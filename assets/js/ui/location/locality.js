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
    this.placesLaunchButton = $('.ods-places-launch');
    this.placesCloseButton = $('.ods-places-close');
};

OdsPlaces.prototype.initEvts = function() {
    if (0 < this.places.length) {

        this.toggleSearchButton();
        this.placesLaunchButton.off('click').on('click', this.launchSearch.bind(this) );
        this.places.off('blur change DOMAutoComplete keydown').on('blur change DOMAutoComplete keydown', this.launchSearch.bind(this) );
    }
};

OdsPlaces.prototype.launchSearch = function (event) {
    let isValidSearch = !!this.places.val();
    if('keydown' === event.type) {
        if (27 === event.keyCode || ESC_KEY_STRING.test(event.key)) {
            this.placesCloseButton.trigger('click');
            this.places.focus();
        } else {
            isValidSearch = isValidSearch && (13 === event.keyCode || 'Enter' === event.key);
        }
    }
    if (isValidSearch) {
        event.preventDefault();

        const params = {'q':  this.places.val()};

        this.placeLabel.addClass('loading');
        $.ajax({
            method: "GET",
            url: NOMINATIM_OSM_URL,
            data: {...NOMINATIM_OSM_DEFAULT_PARAMS, ...params},
            success: this.nominatimOsmResponseCallback.bind(this),
            error: () => {
                this.placeLabel.removeClass('loading');
                this.resetPlacesSearch();
            }
        });
    }
};

OdsPlaces.prototype.nominatimOsmResponseCallback = function(data) {
    this.resetPlacesSearch();
    this.places.siblings('label').removeClass('loading');
    if (0 < data.length) {
        this.searchResults = data;
        this.setSuggestions();
        this.toggleSearchButton(false);
        this.resetOnClick();
        this.onSuggestionSelected();
    }
};

OdsPlaces.prototype.setSuggestions = function() {
    const lthis = this,
        acceptedSuggestions = [];

    this.searchResults.forEach(function(suggestion) {
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
    $('.ods-places-suggestion').first().focus();
};

OdsPlaces.prototype.validateSuggestionData = function(suggestion) {
    const validGeometry = undefined !== suggestion.lat && undefined !== suggestion.lon,
        validAddressData = undefined !== suggestion.address,
        validDisplayName = undefined !== suggestion['display_name'];

    return (validGeometry && validAddressData && validDisplayName);
};

OdsPlaces.prototype.onSuggestionSelected = function() {
    const lthis = this;

    $('.ods-places-suggestion').off('click').on('click', function (evt) {
        const $thisSuggestion = $(this),
            suggestion = lthis.searchResults.find(suggestion => suggestion['place_id'] === $thisSuggestion.data('placeId'));

        evt.preventDefault();

        lthis.places.val($thisSuggestion.text());
        lthis.clientCallback(suggestion);
        lthis.placesCloseButton.trigger('click');

    }).off('keydown').on('keydown', function (evt) {
        evt.preventDefault();

        const $thisSuggestion = $(this);

        if (13 === evt.keyCode || 'Enter' === evt.key) {
            $thisSuggestion.trigger('click');
        } else if (38 === evt.keyCode || 'ArrowUp'=== evt.key) {
            if(0 < $thisSuggestion.prev().length) {
                $thisSuggestion.prev().focus();
            } else {
                lthis.places.focus();
            }
        } else if((40 === evt.keyCode || 'ArrowDown' === evt.key) && 0 < $thisSuggestion.next().length) {
            $thisSuggestion.next().focus();
        } else if (27 === evt.keyCode || ESC_KEY_STRING.test(evt.key)) {
            lthis.placesCloseButton.trigger('click');
            lthis.places.focus();
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

OdsPlaces.prototype.toggleSearchButton = function(isShow = true) {
    this.placesLaunchButton.toggleClass('hidden',!isShow );
    this.placesCloseButton.toggleClass('hidden', isShow);
};

OdsPlaces.prototype.resetPlacesSearch = function() {
    this.places.val('');
    this.toggleSearchButton();
    this.placesResultsContainer.addClass('hidden');
    this.placesResults.empty();
};
