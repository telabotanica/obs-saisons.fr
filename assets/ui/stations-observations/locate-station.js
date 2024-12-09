import {OdsPlaces} from "../location/locality";
import {Location} from "../location/location";

const ODS_LOCATION_INFO_SERVICE_URL = 'https://old.obs-saisons.fr/applications/jrest/OdsCommune/informationsPourCoordonnees';

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
    // reset phenoclim warning message
    this.phenoclimWarningToggle(false);
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
    [this.latitudeEl,this.longitudeEl].forEach(coordinate =>
        coordinate.addEventListener('blur', function() {
            this.handleCoordinates();
        }.bind(this))
    );
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

StationLocation.prototype.updateCoordinatesFields = function() {
    //updates coordinates fields
    document.getElementById('station_latitude').value = Math.floor(this.coordinates.lat * 100000) / 100000;
    document.getElementById('station_longitude').value = Math.floor(this.coordinates.lng * 100000) / 100000;
};

StationLocation.prototype.getAltitude = async function(){
    const lthis = this,
        locality = document.getElementById('station_locality'),
        inseeCode = document.getElementById('station_inseeCode'),
        altitude = document.getElementById('station_altitude'),
        labels = [
            locality.parentElement.querySelector('label'),
            inseeCode.parentElement.querySelector('label')
        ],
        query = {
            'lat': document.getElementById('station_latitude').value,
            'lon': document.getElementById('station_longitude').value
        };
        
        
    // displays loading gif
    labels.forEach(label => label.classList.add('loading'));
    $.ajax({
        method: "GET",
        url: ODS_LOCATION_INFO_SERVICE_URL,
        data: query,
        success: async function (data) {
            let locationInformations = JSON.parse(data);
            lthis.phenoclimWarningToggle(locationInformations.commune_phenoclim);
            // updates location informations fields
            locality.value = locationInformations.commune;
            inseeCode.value = locationInformations.code_insee;
            altitude.value = locationInformations.alt;
            if (town){
                const ods = new OdsPlaces();
                await ods.searchCity(locality.value);
                
            }
            // stops displaying loading gif
            labels.forEach(label => label.classList.remove('loading'));
        },
        error: function () {
            labels.forEach(label => label.classList.remove('loading'));
        }
    });
};

// warns user if station locality is included in "Phenoclim" scientific program
StationLocation.prototype.phenoclimWarningToggle = function(isPhenoclim) {
    let phenoclimWarnigEl = document.getElementById('phenoclim-warning');

    if(isPhenoclim && !phenoclimWarnigEl) {
        const text = document.createTextNode('La station que vous souhaitez créer se trouve en montagne (Alpes, Pyrénées, Massif Central, Jura, Vosges, Corse), merci de saisir vos observations sur le programme partenaire '),
            link = document.createElement('a');

            phenoclimWarnigEl = document.createElement('p');
            phenoclimWarnigEl.id = 'phenoclim-warning';
            phenoclimWarnigEl.classList.add('field-help-text', 'help-text');

            link.classList.add('green-link');
            link.href = 'https://phenoclim.org/fr';
            link.target = '_blank';
            link.textContent = 'Phenoclim';

            phenoclimWarnigEl.appendChild(text);
            phenoclimWarnigEl.append(link);
            document.getElementById('station_altitude').insertAdjacentElement('beforebegin', phenoclimWarnigEl);
    } else if (!isPhenoclim && phenoclimWarnigEl) {
        phenoclimWarnigEl.remove();
    }
};

StationLocation.prototype.onLocation = function() {
    document.getElementById('map-container').addEventListener('location', function () {
        this.updateCoordinatesFields();
        this.getAltitude();
    }.bind(this));
};

