/* ******************* *
 * OBSERVATION OVERLAY *
 * ******************* */

import {FormOverlay} from "./form-overlay";
import {generateComparableFormatedDate} from "../date-format";
import {handleErrorMessages} from "../error-display";

export function ObservationOverlay(openOverlayButton) {
    FormOverlay.call(this, openOverlayButton);

    this.observationEl = document.querySelector('.stage-marker.observation-' + this.dataAttrs.observationId);
    this.eventEl = document.getElementById('observation_event');
    this.individualEl = document.getElementById('observation_individual');
    this.observationDateEl = document.getElementById('observation_date');
    this.openDetailsButton = this.form.querySelector('.open-details-button');
    this.openDetailsButtonContainer = this.form.querySelector('.button-form-container');
    this.detailsContainer = this.form.querySelector('.details-container');
    this.observationData = {};
}
ObservationOverlay.prototype = Object.create(FormOverlay.prototype);
ObservationOverlay.prototype.constructor = ObservationOverlay;

ObservationOverlay.prototype.init = function() {
    FormOverlay.prototype.init.call(this);

    this.openDetailsField();
    this.onChangeSetIndividual();
    this.onChangeObsEvent();
    this.onChangeObsDate();
    this.manageIndividualAndEvents();
    this.editFormPreSetFields();
};

ObservationOverlay.prototype.setOverlayEditForm = function() {
    if (this.openOverlayButton.classList.contains('edit')) {
        const obsInfosOverlay = document.querySelector('.obs-infos');
        // close obs-infos overlay
        if(obsInfosOverlay) {
            obsInfosOverlay.classList.add('hidden');
        }

        this.observationData = JSON.parse(this.observationEl.dataset.observation);
        this.dataAttrs.individualsIds = this.observationEl.dataset.individualsIds;
        this.dataAttrs.speciesName = this.observationData.individual.species.vernacularName;

        const observationPath = '/observation/';
        const editionPath = observationPath + this.observationData.id;

        this.overlay.querySelector('.show-on-edit').href = editionPath + '/delete';
        this.overlay.classList.add('edit');
        this.form.action = editionPath + '/edit';
        this.form.dataset.formActionReset = `/station/${this.observationData.individual.station.id}${observationPath}new`;
    }
};

ObservationOverlay.prototype.openDetailsField = function() {
    $(this.openDetailsButton).off('click').on('click', evt => {
        evt.preventDefault();

        // hide button
        this.openDetailsButtonContainer.classList.add('hidden');
        // show field
        this.detailsContainer.classList.remove('hidden');
    });
};

ObservationOverlay.prototype.onChangeSetIndividual = function() {
    const lthis = this;
    $(this.individualEl).off('change').on('change', () => {
        const selectedIndividual = this.individualEl.options[this.individualEl.selectedIndex];

        Array.from(this.eventEl.getElementsByClassName('event-option')).forEach(
            eventOption => {
                eventOption.removeAttribute('selected');
                lthis.selectOptionsLockToggle(eventOption);
            }
        );

        if (selectedIndividual && selectedIndividual.value) {
            const availableEvents = this.getDataAttrValuesArray(selectedIndividual.dataset.availableEvents.toString()),
                eventsAberrationsDays = JSON.parse(selectedIndividual.dataset.aberrationsDays),
                speciesPictureBase = selectedIndividual.dataset.picture;

            this.updateSpeciesPageUrl(selectedIndividual);
            this.eventEl.removeAttribute('disabled');

            if (1 === availableEvents.length) {
                const eventId= availableEvents[0],
                    eventOption = this.eventEl.querySelector('.event-option.event-'+ eventId);

                eventOption.setAttribute('selected','selected');
                this.selectOptionsLockToggle(this.eventEl.firstElementChild);
                this.eventEl.value = eventId;
                this.eventEl.required = false;
                this.eventEl.classList.add('disabled');
                this.setObsEvent(
                    eventId,
                    eventOption,
                    speciesPictureBase,
                    eventsAberrationsDays
                );
            } else {
                availableEvents.forEach(eventId => {
                    const eventOption = lthis.eventEl.querySelector('.event-option.event-'+ eventId),
                        speciesPicture = speciesPictureBase + eventOption.dataset.pictureSuffix;
                    lthis.eventEl.required = true;
                    lthis.eventEl.classList.remove('disabled');
                    lthis.setObsEvent(
                        eventId,
                        eventOption,
                        speciesPicture,
                        eventsAberrationsDays
                    );
                });
            }
        } else {
            this.eventEl.classList.add('disabled');
            this.eventEl.setAttribute('disabled', 'disabled');
        }
        this.eventEl.dispatchEvent(new Event('change'));// triggers onChangeObsEvent()
    });
};

ObservationOverlay.prototype.updateSpeciesPageUrl = function(selectedIndividual) {
    const link = this.form.querySelector('.saisie-aide-txt a.green-link'),
        url = link.href,
        speciesInUrl = url.substring(url.lastIndexOf('/')+1);
    let species = selectedIndividual.dataset.speciesName;

    if((selectedIndividual.dataset.isTreeGroup)) {
        species = species.split(' ')[0];
    }
    if(!/%[A-Z0-9]{2}/.test(species)) {
        species = encodeURI(species);
    }

    if (speciesInUrl !== species) {
        link.href = url.replace(speciesInUrl, species);
    }
};

ObservationOverlay.prototype.setObsEvent = function(
    eventId,
    eventOption,
    speciesPicture,
    eventsAberrationsDays
) {
    this.selectOptionsLockToggle(eventOption, false);
    eventOption.dataset.picture = '/media/species/' + speciesPicture + '.jpg';
    if(!!eventsAberrationsDays) {
        this.setEventAberrationDaysDataAttr(eventId, eventsAberrationsDays);
    }
};

/*
 * @param {number} eventId (integer)
 * @param {Array.<{eventId: Number, aberrationStartDay: string, aberrationEndDay: string, displayedStartDay: string, displayedEndDay: string}>} eventsAberrationsDays
 * eventId is integer
 * aberrationStartDay is formatted string (format: month-day, e.g. "03-28")
 * aberrationEndDay is formatted string (format: month-day, e.g. "05-09")
 * displayedStartDay is formatted string (format fr: day month, e.g. "28 mars")
 * displayedEndDay is formatted string (format fr: day month, e.g. "9 mai")
 */
ObservationOverlay.prototype.setEventAberrationDaysDataAttr = function(
    eventId,
    eventsAberrationsDays
) {
    const eventOptionEl = this.eventEl.querySelector('.event-option.event-'+eventId),
        aberrationDays = eventsAberrationsDays.find(aberrationDays =>
            parseInt(aberrationDays.eventId) === parseInt(eventId)
        );

    for(const [key, value] of Object.entries(aberrationDays)) {
        if('eventId' !== key && value) {
            eventOptionEl.dataset[key] = value;
        }
    }
};

ObservationOverlay.prototype.onChangeObsEvent = function() {
    $(this.eventEl).off('change').on('change', () => {
        const isValidEvent = !!this.eventEl.value;

        this.updateHelpInfos(isValidEvent);
        if (isValidEvent && !!this.observationDateEl.value) {
            this.checkAberrationsObsDays();
        }
    });
};

ObservationOverlay.prototype.updateHelpInfos = function(isValidEvent) {
    const eventHelp = this.form.querySelector('.saisie-aide.event');

    Array.from(eventHelp.getElementsByTagName('img')).forEach(img => img.remove());

    if (isValidEvent) {
        const selectedEvent = this.eventEl.options[this.eventEl.selectedIndex],
            eventStage = selectedEvent.textContent,
            eventHelpImg = document.createElement('img');

        Object.assign(eventHelpImg, {
            src: selectedEvent.dataset.picture,
            alt: eventStage,
            width: 80,
            height: 80
        });

        eventHelp.classList.remove('hidden');
        eventHelp.prepend(eventHelpImg);
        this.form.querySelector('.text-aide-1.event').textContent = eventStage;
        this.form.querySelector('.text-aide-2.event').textContent = selectedEvent.dataset.description;
    } else {
        eventHelp.classList.add('hidden');
    }
};

ObservationOverlay.prototype.onChangeObsDate = function() {
    const lthis = this;
    let message = '';

    if(!!this.observationDateEl && !!this.eventEl) {
        $(this.observationDateEl).off('blur').on('blur', function () {
            // front validation for safari input type date to type text
            const dateValue = lthis.observationDateEl.value;

            if (!!dateValue) {
                const date = generateComparableFormatedDate(dateValue),
                    now = generateComparableFormatedDate(new Date()),
                    minDate = generateComparableFormatedDate(new Date('2006-01-01'));
                if (minDate > date || now < date) {
                    if (minDate > date) {
                        message = 'Cette date est antérieure au programme ODS';
                    } else {
                        message = 'Cette date est postérieure à aujourd’hui';
                    }
                }
            }

            handleErrorMessages(
                lthis.observationDateEl,
                message,
                'invalid-date'
            );

            // check aberration dates
            if (!!dateValue && !!lthis.eventEl.value) {
                lthis.checkAberrationsObsDays();
            }
        });
    }
};

ObservationOverlay.prototype.checkAberrationsObsDays = function() {
    const formWarningEl = this.form.querySelector('.ods-form-warning'),
        selectedEvent = this.eventEl.options[this.eventEl.selectedIndex],
        aberrationStartDay = selectedEvent.dataset.aberrationStartDay,
        aberrationEndDay = selectedEvent.dataset.aberrationEndDay,
        observationDay = this.observationDateEl.value.slice(5);
    let message = '';

    function comparativeTimeValue(day) {
        return parseInt(day.replace('-', ''));
    }

    function checkIfAberrationBetween2Years(start, end){
        return (end - start) < 0;
    }

    function fillMessage(species){
        return `La date que vous venez de saisir sort de la période habituelle pour cet événement chez cette espèce (${selectedEvent.dataset.displayedStartDate} au ${selectedEvent.dataset.displayedEndDate}).
            Si vous êtes sûr(e) de votre observation, ne tenez pas compte de ce message, sinon, vérifiez qu’il s’agit bien de ce stade et de cette <a href="/especes/${species}" target="_blank" class="deep-green-link small">espèce</a>.
            Si vous restez dans le doute, <a href="https://www.obs-saisons.fr/contact" target="_blank" class="deep-green-link small">contactez nous</a>.`
    }

    if(!!aberrationStartDay && !!aberrationEndDay && !!observationDay){
        const between2Years = checkIfAberrationBetween2Years(comparativeTimeValue(aberrationStartDay), comparativeTimeValue(aberrationEndDay))
        const species = this.individualEl.options[this.individualEl.selectedIndex].dataset.speciesName;

        if (between2Years){
            if (
                comparativeTimeValue(aberrationStartDay) > comparativeTimeValue(observationDay) && comparativeTimeValue(aberrationEndDay) < comparativeTimeValue(observationDay)
            ){
                message = fillMessage(species);
            }
        } else {
            if (comparativeTimeValue(aberrationStartDay) > comparativeTimeValue(observationDay)
                || comparativeTimeValue(aberrationEndDay) < comparativeTimeValue(observationDay)){

                message = fillMessage(species);
            }
        }

    }

    formWarningEl.innerHTML = message;
    formWarningEl.classList.toggle('hidden',!message);
};

ObservationOverlay.prototype.manageIndividualAndEvents = function() {
    const availableIndividuals = this.dataAttrs.individualsIds ? this.getDataAttrValuesArray(this.dataAttrs.individualsIds.toString()) : [],
        formTitle = this.overlay.querySelector('.saisie-title');

    this.updateSelectOptions(this.individualEl, availableIndividuals);

    this.individualEl.dispatchEvent(new Event('change'));//triggers onChangeSetIndividual()
    if (formTitle) {
        const formTitleText = this.dataAttrs.speciesName || formTitle.dataset.stationName;

        formTitle.textContent = formTitleText.htmlEntitiesODS();
    }
};

ObservationOverlay.prototype.editFormPreSetFields = function() {
    if (this.overlay.classList.contains('edit')) {
        const lthis = this;

        this.overlay.querySelector('.saisie-header').textContent = 'Modifier l’observation';

        for(const [key, data] of Object.entries(this.observationData)) {
            const field = document.getElementById('observation_' + key);

            switch (key) {
                case 'individual':
                    lthis.individualEl.classList.remove('disabled');
                    lthis.selectOption(
                        lthis.individualEl.querySelector('.individual-option.individual-' + data.id)
                    );
                    lthis.individualEl.dispatchEvent(new Event('change'));
                    break;
                case 'event':
                    if (!!data.id || 0 === data.id) {
                        lthis.selectOption(
                            lthis.eventEl.querySelector('.event-option.event-'+data.id)
                        );
                    }
                    break;
                case 'date':
                    field.value = data;
                    break;
                case 'details':
                    if(!!data) {
                        field.value = data;
                        lthis.openDetailsButton.click();//triggers openDetailsField()
                    }
                    break;
                case 'isMissing':
                    field.checked = data;
                    break;
                case 'picture':
                    lthis.fileUploadHandler.preSetFile(data);
                    break;
                default:
                    break;
            }

        }
    }
};

ObservationOverlay.prototype.closeOverlay = function () {
    FormOverlay.prototype.closeOverlay.call(this);

    const openIndividualGlobalForm = document.querySelector('.open-individual-form-all-station');
    if(openIndividualGlobalForm) {
        this.manageIndividualAndEvents(openIndividualGlobalForm.dataset);
    }
    this.closeDetailsField();
    Array.from(this.form.getElementsByClassName('ods-form-warning')).forEach(warningEl => {
        warningEl.classList.add('hidden');
        warningEl.textContent = '';
    });
    this.resetUploadFilesComponent();
};

ObservationOverlay.prototype.closeDetailsField = function() {
    // hide button
    this.openDetailsButtonContainer.classList.remove('hidden');
    // show field
    this.detailsContainer.classList.add('hidden');
};
