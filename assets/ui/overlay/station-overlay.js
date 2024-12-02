/* **************** *
 *  STATION OVERLAY *
 * **************** */

import {FormOverlay} from "./form-overlay";
import {parseDatasetValToBool} from "../../lib/parse-to-bool";

export function StationOverlay(openOverlayButton) {
    FormOverlay.call(this, openOverlayButton);
    this.stationData = {};
    this.stationLocation = {};
}
StationOverlay.prototype = Object.create(FormOverlay.prototype);
StationOverlay.prototype.constructor = StationOverlay;

StationOverlay.prototype.init = function() {
    FormOverlay.prototype.init.call(this);

    this.editFormPreSetFields();
};

StationOverlay.prototype.setOverlayEditForm = function() {
    if (this.openOverlayButton.classList.contains('edit')) {
        this.stationData = JSON.parse(this.dataAttrs.station);

        const editionPath = '/station/' + this.stationData.id;

        this.overlay.querySelector('.show-on-edit').href = editionPath + '/delete';
        this.overlay.classList.add('edit');
        this.form.action = editionPath + '/edit';
        this.form.dataset.formActionReset = editionPath + '/new';
    }
};

StationOverlay.prototype.editFormPreSetFields = function() {
    const lthis = this;

    if (this.overlay.classList.contains('edit')) {
        this.overlay.querySelector('.saisie-header').textContent = 'Modifier la station';
        for(const [key, data] of Object.entries(lthis.stationData)) {
            var type='';
            if (key.includes('exact')){
                type = key.replace('exactL','exact_l');
            }else{
                type=key;
            }
            const field = document.getElementById('station_' + type);
            console.log(key+ " "+data);
            switch (key) {
                case 'name':
                case 'description':
                case 'latitude':
                    field.value = data;
                    break;
                case 'longitude':
                    field.value = data;
                    break;
                case 'habitat':
                    const habitatOption = Array.from(field.childNodes).find(
                        option => (option.value).toLowerCase() === (data).toLowerCase()
                    );

                    habitatOption.setAttribute('selected', 'selected');
                    break;
                case 'isPrivate':
                    field.checked = parseDatasetValToBool(data);
                    break;
                case 'headerImage':
                    lthis.fileUploadHandler.preSetFile(data);
                    break;
                case 'exactLatitude':
                    field.value = data;
                    break;
                case 'exactLongitude':
                    field.value = data;
                    break;
                default:
                    break;
            }
        }
    }
};

StationOverlay.prototype.closeOverlay = function () {
    FormOverlay.prototype.closeOverlay.call(this);
    this.stationLocation.removeMap();
    this.stationLocation.odsPlaces.resetPlacesSearch();
    this.resetUploadFilesComponent();
};

StationOverlay.prototype.closeOverlayOnClickOut = function() {
    const lthis = this;
    this.overlay.addEventListener('click', function(evt) {
        if(
            !evt.target.closest('.saisie-container') &&
            !evt.target.classList.contains('ods-places-suggestion')
        ) {
            lthis.closeOverlay();
        }
    });
};

StationOverlay.prototype.closeOverlayOnEscapeKey = function() {
    const lthis = this;
    document.body.addEventListener('keydown', function(evt) {
        const ESC_KEY_STRING = /^Esc(ape)?/;

        if('Escape' === evt.key || ESC_KEY_STRING.test(evt.key)) {
            const openedOverlay = !lthis.overlay.classList.contains('hidden');

            if (openedOverlay) {
                const target = evt.target,
                    isOdsPlacesDropdownEscape = ['ods-places-suggestion','ods-places'].some(
                        className => target.classList.contains(className)
                    );

                if (!isOdsPlacesDropdownEscape) {
                    lthis.closeOverlay(openedOverlay);
                }
            }
        }
    });
};
