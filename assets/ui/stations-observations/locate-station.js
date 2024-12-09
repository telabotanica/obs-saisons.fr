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
    this.latitudeEl.value = this.coordinates.lat;
    this.longitudeEl.value = this.coordinates.lng;
};

StationLocation.prototype.getAltitude = async function(town){
    const lthis = this,
        locality = document.getElementById('station_locality'),
        inseeCode = document.getElementById('station_inseeCode'),
        altitude = document.getElementById('station_altitude'),
        labels = [
            locality.parentElement.querySelector('label'),
            inseeCode.parentElement.querySelector('label')
        ],
        latitude = document.getElementById('station_latitude').value,
        longitude= document.getElementById('station_longitude').value;
        
    // displays loading gif
    labels.forEach(label => label.classList.add('loading'));
    var url = "https://api.elevationapi.com/api/Elevation/line/"+latitude+","+longitude+"/"+getNearLocation(this.coordinates.lat)+","+getNearLocation(this.coordinates.lng)+"?dataSet=SRTM_GL3&reduceResolution=5";
    console.log(url);
    $.ajax({
        method: "GET",
        url: url,
        success: function (data) {
            console.log(data);
            var elevation = Math.trunc(data.geoPoints[0].elevation);
            console.log(elevation);
            // updates location informations fields
            altitude.value = elevation;
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

function getNearLocation($coordonnee){
    $coordonnee = Math.floor($coordonnee * 1000) / 1000;
    return $coordonnee;
}

