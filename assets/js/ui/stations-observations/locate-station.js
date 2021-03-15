import {OdsPlaces} from "../location/locality";
import {Location} from "../location/location";

const ODS_LOCATION_INFO_SERVICE_URL = 'https://www.obs-saisons.fr/applications/jrest/OdsCommune/informationsPourCoordonnees';

export function StationLocation() {}

StationLocation.prototype = new Location();

StationLocation.prototype.init = function() {
    this.initForm();
    this.initEvts();
};

StationLocation.prototype.initForm = function() {
    this.latitudeEl = document.getElementById('station_latitude');
    this.longitudeEl = document.getElementById('station_longitude');
};

StationLocation.prototype.initEvts = function() {
    this.handleCoordinates();
    this.onCoordinates();
    this.initSearchLocality();
    this.onLocation();
    this.toggleMap();
};

StationLocation.prototype.handleCoordinates = function() {
    if (!!this.latitudeEl.value && !!this.longitudeEl.value) {
        this.handleNewLocation({
            'lat': this.latitudeEl.value,
            'lng': this.longitudeEl.value
        });
    }
};

StationLocation.prototype.onCoordinates = function() {
    $('#station_latitude, #station_longitude').on('blur', function() {
        this.handleCoordinates();
    }.bind(this));
};

StationLocation.prototype.initSearchLocality = function() {
    this.odsPlaces = new OdsPlaces(this.odsPlacesCallback.bind(this));
    this.odsPlaces.init();
};

StationLocation.prototype.odsPlacesCallback = function(localityData) {
    const addressData = localityData.address,
        locationNameType = ['village', 'city', 'locality', 'municipality', 'county'].find(locationNameType => addressData[locationNameType] !== undefined);
    if(!!locationNameType) {
        document.getElementById('station_locality').value = addressData[locationNameType];
        this.handleNewLocation({
            'lat' : localityData.lat,
            'lng' : localityData.lon
        });
    }
};


StationLocation.prototype.onLocation = function() {
    $('#map').on('location', function () {
        this.updateCoordinatesFields();
        this.loadLocationInfosFromOdsService();
    }.bind(this));
};

StationLocation.prototype.updateCoordinatesFields = function() {
    //updates coordinates fields
    this.latitudeEl.value = this.coordinates.lat;
    this.longitudeEl.value = this.coordinates.lng;
};

StationLocation.prototype.loadLocationInfosFromOdsService = function() {
    const locality = document.getElementById('station_locality'),
        inseeCode = document.getElementById('station_inseeCode'),
        altitude = document.getElementById('station_altitude'),
        labels = [
            locality.parentElement.querySelector('label'),
            inseeCode.parentElement.querySelector('label')
        ],
        query = {
            'lat': this.coordinates.lat,
            'lon': this.coordinates.lng
        };

    // displays loading gif
    labels.forEach(label => label.classList.add('loading'));

    $.ajax({
        method: "GET",
        url: ODS_LOCATION_INFO_SERVICE_URL,
        data: query,
        success: function (data) {
            let locationInformations = JSON.parse(data);
            // updates location informations fields
            locality.value = locationInformations.commune;
            inseeCode.value = locationInformations.code_insee;
            altitude.value = locationInformations.alt;
            // stops displaying loading gif
            labels.forEach(label => label.classList.remove('loading'));
        },
        error: function () {
            labels.forEach(label => label.classList.remove('loading'));
        }
    });
};


