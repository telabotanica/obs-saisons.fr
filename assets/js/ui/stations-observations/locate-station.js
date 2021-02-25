/**************************************************
 * ODS LOCATION INFO SERVICE
 **************************************************/
const ODS_LOCATION_INFO_SERVICE_URL = 'https://www.obs-saisons.fr/applications/jrest/OdsCommune/informationsPourCoordonnees';

/**************************************************
 * DOM ELEMENTS
 **************************************************/
const $latitude = $('#station_latitude');
const $longitude = $('#station_longitude');
/**************************************************
 * MAP
 **************************************************/
import {initSearchLocality, onLocalityField} from "../location/locality";
import {Location} from "../location/location";

/***************************************************/

export function StationLocation() {}

StationLocation.prototype = new Location();

StationLocation.prototype.init = function() {
    this.handleCoordinates();
    this.onCoordinates();
    onLocalityField('station_locality');
    initSearchLocality();
    this.onLocation();
    this.toggleMap();
};

StationLocation.prototype.handleCoordinates = function() {
    if (valOk($latitude.val()) && valOk($longitude.val())) {
        this.handleNewLocation({
            'lat': $latitude.val(),
            'lng': $longitude.val()
        });
    }
};

StationLocation.prototype.onCoordinates = function() {
    $('#station_latitude, #station_longitude').on('blur', function() {
        this.handleCoordinates();
    }.bind(this));
};

StationLocation.prototype.onLocation = function() {
    $('#map').on('location', function () {
        this.updateCoordinatesFields();
        this.loadLocationInfosFromOdsService();
    }.bind(this));
};

StationLocation.prototype.updateCoordinatesFields = function() {
    //updates coordinates fields
    $latitude.val(this.coordinates.lat);
    $longitude.val(this.coordinates.lng);
};

StationLocation.prototype.loadLocationInfosFromOdsService = function() {
    let $label = $('#station_locality,#station_inseeCode').siblings('label'),
        query = {'lat': this.coordinates.lat, 'lon': this.coordinates.lng};

    // displays loading gif
    $label.addClass('loading');

    $.ajax({
        method: "GET",
        url: ODS_LOCATION_INFO_SERVICE_URL,
        data: query,
        success: function (data) {
            let locationInformations = JSON.parse(data);
            // updates location informations fields
            $('#station_locality').val(locationInformations.commune);
            $('#station_inseeCode').val(locationInformations.code_insee);
            $('#station_altitude').val(locationInformations.alt);
            // stops displaying loading gif
            $label.removeClass('loading');
        },
        error: function () {
            $label.removeClass('loading');
        }
    });
};
