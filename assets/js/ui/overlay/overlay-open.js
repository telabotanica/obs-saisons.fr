import domready from 'mf-js/modules/dom/ready';
import {handleErrorMessages} from "../error-display";
import {HandleFileUploads} from "../handle-file-uploads";
import {StationLocation} from "../stations-observations/locate-station";
import {closeOverlayOnClickOut} from "./overlay-close";
import {generateComparableFormatedDate} from "../date-format";
import {onDeleteButton} from "../handle-delete-button";

export const stationLocation = new StationLocation();

const onOpenOverlay = function() {
    const openOverlayButtons = document.getElementsByClassName('open');

    if(openOverlayButtons) {
        openOverlayButtons.forEach(openOverlayButton => {
            openOverlayButton.addEventListener('click', evt => {
                evt.preventDefault();
                evt.stopPropagation();

                if (openOverlayButton.classList.contains('disabled')) {//user is not logged
                    window.location.href = window.location.origin + '/user/login';
                } else {
                    let dataAttrs = openOverlayButton.dataset;
                    const overlay = document.querySelector('.overlay.' + dataAttrs.open),
                        form = overlay.querySelector('form');

                    overlay.classList.remove('hidden');
                    closeOverlayOnClickOut(overlay);
                    document.body.style.overflow = 'hidden';
                    if (form) {
                        dataAttrs = initFormOverlay(overlay, form, openOverlayButton, dataAttrs);
                        switch (dataAttrs.open) {
                            case 'admin-profile':
                            case 'profile':
                                editProfilePreSetFields(overlay, dataAttrs);
                                break;
                            case 'station':
                                stationLocation.init();
                                editStationPreSetFields(overlay, dataAttrs);
                                break;
                            case 'observation':
                                openDetailsField();
                                onChangeSetIndividual();
                                onChangeObsEvent();
                                onChangeObsDate();
                                observationOverlayManageIndividualAndEvents(overlay, dataAttrs);
                                editObservationPreSetFields(overlay, dataAttrs);
                                break;
                            case 'individual':
                                individualOverlayManageSpecies(dataAttrs);
                                editIndividualPreSetFields(overlay, dataAttrs);
                                break;
                            default:
                                break;
                        }
                    } else if ('obs-infos' === dataAttrs.open) {
                        onObsInfo(openOverlayButton, dataAttrs);
                    }
                    onDeleteButton(dataAttrs.open);
                }
            })
        });
    }
};

/* *************** *
 *  FORM OVERLAY   *
 * *************** */

const initFormOverlay = function(
    overlay,
    form,
    openOverlayButton,
    dataAttrs
) {
    form.reset();
    if(document.querySelector('.upload-zone .upload-input')) {
        const fileUploadHandler = new HandleFileUploads();

        fileUploadHandler.init();
    }
    if (openOverlayButton.classList.contains('edit')) {
        return setEditOverlayForm(overlay, dataAttrs);
    }

    return dataAttrs;
};

const setEditOverlayForm = function(
    overlay,
    dataAttrs
) {
    const form = overlay.querySelector('form'),
        overlayType = ['admin-profile', 'station', 'individual', 'observation'].find(type => dataAttrs.open === type);
    if ('admin-profile' === overlayType) {
        form.setAttribute('action', dataAttrs.editionPath);
    } else {
        let formActionReset = '/'+dataAttrs.open+'/new',
            editionPath = '/'+dataAttrs.open;

        if (overlayType) {
            if ('station' !== overlayType) {
                let stationId;

                if ('observation' === overlayType) {
                    const observation = document.querySelector('.stage-marker.observation-' + dataAttrs.observationId),
                        obsInfosOverlay = document.querySelector('.obs-infos');

                    // close obs-infos overlay
                    if(obsInfosOverlay) {
                        obsInfosOverlay.classList.add('hidden');
                    }

                    dataAttrs.observation = observation.dataset.observation;
                    dataAttrs.individualsIds = observation.dataset.individualsIds;

                    const parsedObservationData = JSON.parse(dataAttrs.observation);

                    dataAttrs.speciesName = parsedObservationData.individual.species.vernacularName;
                    stationId = parsedObservationData.individual.station.id;
                } else {
                    stationId = JSON.parse(dataAttrs.individual).station.id;
                }
                formActionReset = '/station/' + stationId + formActionReset;
            }
            const targetTypeId = JSON.parse(dataAttrs[dataAttrs.open]).id;

            editionPath += '/' + targetTypeId;
            overlay.querySelector('.show-on-edit').setAttribute('href', editionPath + '/delete');
        }
        overlay.classList.add('edit');
        form.action = editionPath+'/edit';
        form.dataset.formActionReset = formActionReset;
    }

    return dataAttrs;
};

// returns an array of values from data attributes value
const getDataAttrValuesArray = function (dataAttrValue) {
    if (0 > dataAttrValue.indexOf(',')) {
        return [dataAttrValue];
    } else {
        return dataAttrValue.split(',');
    }
};

const updateSelectOptions = function(
    selectEl,
    itemsToMatch,
    sortOptions = true
) {
    const selectName = selectEl.dataset.name;

    selectEl.classList.toggle('disabled',(1 >= itemsToMatch.length && sortOptions));

    selectEl.getElementsByTagName('option').forEach(option => {
        option.removeAttribute('hidden');
        option.removeAttribute('selected');

    });
    selectEl.closest('form').reset();
    selectEl.querySelectorAll('option:not(.exists-in-station.animal)').forEach(option => {
        option.removeAttribute('disabled');
    });


    if(sortOptions) {
        selectEl.querySelectorAll('.' + selectName + '-option').forEach(element => {
            if (itemsToMatch.includes(element.value.toString())) {
                if (1 === itemsToMatch.length && element.classList.contains(selectName + '-' + itemsToMatch[0])) {
                    element.setAttribute('selected', 'selected');
                }
            } else {
                element.setAttribute('hidden', 'hidden');
                element.setAttribute('disabled', 'disabled');
            }
        });
        if(1 === itemsToMatch.length) {
            selectEl.value = itemsToMatch[0];
        }
    }
};

const displayThumbs = function (overlay, src) {
    if (src) {
        const placeholderImg = overlay.querySelector('.placeholder-img');

        overlay.querySelector('.upload-zone-placeholder').classList.add('hidden');
        placeholderImg.classList.add('obj');
        placeholderImg.src = src;
    }
};

const selectOptionsLockToggle = function(element, lock = true) {
    if (lock) {
        element.setAttribute('disabled','disabled');
        element.setAttribute('hidden','hidden');
    } else {
        element.removeAttribute('disabled');
        element.removeAttribute('hidden');
    }
};

const selectOption = function (element) {
    selectOptionsLockToggle(element, false);
    element.setAttribute('selected', 'selected');
};

/* ********** *
 *  PROFILE   *
 * ********** */

const editProfilePreSetFields = function(
    overlay,
    dataAttrs
) {
    const user = JSON.parse(dataAttrs.user);

    if (overlay.classList.contains('edit') && !!user.avatar) {
        displayThumbs(overlay, user.avatar);
    }
};

/* ********** *
 *  STATION   *
 * ********** */

const editStationPreSetFields = function(
    overlay,
    dataAttrs
) {
    if (overlay.classList.contains('edit')) {
        const stationData = JSON.parse(dataAttrs.station);

        overlay.querySelector('.saisie-header').textContent = 'Modifier la station';

        Object.keys(stationData).forEach(
            key => {
                const field = document.getElementById('station_' + key);

                switch (key) {
                    case 'name':
                    case 'description':
                    case 'latitude':
                        field.value = stationData[key];
                        break;
                    case 'longitude':
                        field.value = stationData.longitude;
                        field.dispatchEvent(new Event('blur'));
                        break;
                    case 'habitat':
                        const habitatOption = Array.from(field.childNodes).find(
                            option => option.value = stationData.habitat
                        );

                        habitatOption.setAttribute('selected', 'selected');
                        break;
                    case 'isPrivate':
                        field.checked = stationData.isPrivate;
                        break;
                    case 'headerImage':
                        displayThumbs(overlay, stationData.headerImage);
                        break;
                    default:
                        break;
                }
            }
        );
    }
};

/* ************** *
 *  OBSERVATION   *
 * ************** */

const openDetailsField = function() {
    const openDetailsButton = document.querySelector('.open-details-button');

    openDetailsButton.addEventListener('click', evt => {
        evt.preventDefault();

        const openDetailsButtonContainer = openDetailsButton.closest('.button-form-container');

        // hide button
        openDetailsButtonContainer.classList.add('hidden');
        // show field
        openDetailsButtonContainer.parentElement.querySelector('.details-container').classList.remove('hidden');
    });
};

const onChangeSetIndividual = function() {
    const individualEl = document.getElementById('observation_individual'),
        eventEl = document.getElementById('observation_event');

    individualEl.addEventListener('change', () => {
        const selectedIndividual = individualEl.options[individualEl.selectedIndex];

        Array.from(eventEl.getElementsByClassName('event-option')).forEach(
            eventOption => {
                eventOption.removeAttribute('selected');
                selectOptionsLockToggle(eventOption);
            }
        );

        if (selectedIndividual && selectedIndividual.value) {
            const availableEvents = getDataAttrValuesArray(selectedIndividual.dataset.availableEvents.toString()),
                eventsAberrationsDays = JSON.parse(selectedIndividual.dataset.aberrationsDays),
                speciesPictureBase = selectedIndividual.dataset.picture;

            updateSpeciesPageUrl(selectedIndividual);
            eventEl.removeAttribute('disabled');

            if (1 === availableEvents.length) {
                const eventId= availableEvents[0],
                    eventOption = eventEl.querySelector('.event-option.event-'+ eventId);

                eventOption.setAttribute('selected','selected');
                selectOptionsLockToggle(eventEl.firstElementChild);
                eventEl.value = eventId;
                eventEl.required = false;
                eventEl.classList.add('disabled');
                setObsEvent(eventId,
                    eventOption,
                    speciesPictureBase,
                    eventsAberrationsDays
                );
            } else {
                availableEvents.forEach(eventId => {
                    const eventOption = eventEl.querySelector('.event-option.event-'+ eventId),
                        speciesPicture = speciesPictureBase + eventOption.dataset.pictureSuffix;
                    eventEl.required = true;
                    eventEl.classList.remove('disabled');
                    setObsEvent(eventId,
                        eventOption,
                        speciesPicture,
                        eventsAberrationsDays
                    );
                });
            }
        } else {
            eventEl.classList.add('disabled');
            eventEl.setAttribute('disabled', 'disabled');
        }
        eventEl.dispatchEvent(new Event('change'));// triggers onChangeObsEvent()
    });
};

const updateSpeciesPageUrl = function(selectedIndividual) {
    const link = document.querySelector('.saisie-aide-txt a.green-link'),
        url = link.getAttribute('href'),
        speciesInUrl = url.substring(url.lastIndexOf('/')+1),
        species = selectedIndividual.dataset.speciesName;

    if (speciesInUrl !== species) {
        link.setAttribute('href', url.replace(speciesInUrl,species));
    }
};

const setObsEvent = (
    eventId,
    eventOption,
    speciesPicture,
    eventsAberrationsDays
) => {
    selectOptionsLockToggle(eventOption, false);
    eventOption.dataset.picture = '/media/species/' + speciesPicture + '.jpg';
    if(!!eventsAberrationsDays) {
        setEventAberrationDaysDataAttr(eventId, eventsAberrationsDays);
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
const setEventAberrationDaysDataAttr = function(
    eventId,
    eventsAberrationsDays
) {
    const eventEl = document.getElementById('observation_event'),
        eventOptionEl = eventEl.querySelector('.event-option.event-'+eventId),
        aberrationDays = eventsAberrationsDays.find(aberrationDays =>
            // ensure that both are integers and compare
            parseInt(aberrationDays.eventId) === parseInt(eventId)
        );

    for(const [key, value] of Object.entries(aberrationDays)) {
        if('eventId' !== key && value) {
            eventOptionEl.dataset[key] = value;
        }
    }
};

const onChangeObsEvent = function() {
    const eventEl = document.getElementById('observation_event'),
        observationDateEl = document.getElementById('observation_date');

    eventEl.addEventListener('change', () => {
        const isValidEvent = !!eventEl.value;

        updateHelpInfos(isValidEvent);
        if (isValidEvent && !!observationDateEl.value) {
            checkAberrationsObsDays();
        }
    });
};

const updateHelpInfos = function(isValidEvent) {
    const eventEl = document.getElementById('observation_event'),
        eventHelp = document.querySelector('.saisie-aide.event');

    eventHelp.getElementsByTagName('img').forEach(img => img.remove());

    if (isValidEvent) {
        const selectedEvent = eventEl.options[eventEl.selectedIndex],
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
        document.querySelector('.text-aide-1.event').textContent = eventStage;
        document.querySelector('.text-aide-2.event').textContent = selectedEvent.dataset.description;
    } else {
        eventHelp.classList.add('hidden');
    }
};

const onChangeObsDate = function() {
    const observationDate = document.getElementById('observation_date'),
        eventEl = document.getElementById('observation_event');
    let message;

    if(!!observationDate && !!eventEl) {
        observationDate.addEventListener('blur', function () {
            // front validation for safari input type date to type text
            const dateValue = observationDate.value;

            message = '';
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
                observationDate,
                message,
                'invalid-date'
            );

            // check aberration dates
            if (!!dateValue && !!eventEl.value) {
                checkAberrationsObsDays();
            }
        });
    }
};

const checkAberrationsObsDays = function() {
    const eventEl = document.getElementById('observation_event'),
        observationDateEl = document.getElementById('observation_date'),
        individualEl = document.getElementById('observation_individual'),
        formWarningEl = document.querySelector('.ods-form-warning');
    const selectedEvent = eventEl.options[eventEl.selectedIndex],
        aberrationStartDay = selectedEvent.dataset.aberrationStartDay,
        aberrationEndDay = selectedEvent.dataset.aberrationEndDay,
        observationDay = observationDateEl.value.slice(5);
    let message = '';

    function comparativeTimeValue(day) {
        return parseInt(day.replace('-', ''));
    }

    if(
        !!aberrationStartDay && !!aberrationEndDay && !!observationDay
        && (
            comparativeTimeValue(aberrationStartDay) > comparativeTimeValue(observationDay)
            || comparativeTimeValue(aberrationEndDay) < comparativeTimeValue(observationDay)
        )
    ) {
        const species = individualEl.options[individualEl.selectedIndex].dataset.speciesName;
        message = 'La date que vous venez de saisir sort de la période habituelle pour cet événement chez cette espèce ('+selectedEvent.dataset.displayedStartDate+' au '+selectedEvent.dataset.displayedEndDate+'). ' +
            'Si vous êtes sûr(e) de votre observation, ne tenez pas compte de ce message, sinon, vérifiez qu’il s’agit bien de ce stade et de cette <a href="/especes/'+species+'" target="_blank" class="deep-green-link small">espèce</a>. ' +
            'Si vous restez dans le doute, <a href="" target="_blank" class="deep-green-link small">contactez nous</a>.';
    }

    formWarningEl.innerHTML = message;
    formWarningEl.classList.toggle('hidden',!message);
};

export const observationOverlayManageIndividualAndEvents = function(
    overlay,
    dataAttrs
) {
    const individualEl = document.getElementById('observation_individual'),
        availableIndividuals = dataAttrs.individualsIds ? getDataAttrValuesArray(dataAttrs.individualsIds.toString()) : [],
        formTitle = overlay.querySelector('.saisie-title');

    updateSelectOptions(individualEl, availableIndividuals);

    individualEl.dispatchEvent(new Event('change'));//triggers onChangeSetIndividual()
    if (formTitle) {
        const formTitleText = dataAttrs.speciesName || formTitle.dataset.stationName;

        formTitle.textContent = formTitleText.htmlEntitiesODS();
    }
};

const editObservationPreSetFields = function(overlay, dataAttrs) {
    if (overlay.classList.contains('edit')) {
        const observation = JSON.parse(dataAttrs.observation),
            individualEl = document.getElementById('observation_individual');

        overlay.querySelector('.saisie-header').textContent = 'Modifier l’observation';

        individualEl.classList.remove('disabled');
        selectOption(
            individualEl.querySelector('.individual-option.individual-'+observation.individual.id)
        );
        individualEl.dispatchEvent(new Event('change'));

        if (!!observation.event.id || 0 === observation.event.id) {
            const eventEl = document.getElementById('observation_event');

            selectOption(
                eventEl.querySelector('.event-option.event-'+observation.event.id)
            );
        }

        Object.keys(observation).forEach(
            key => {
                const field = document.getElementById('observation_' + key);

                switch (key) {
                    case 'date':
                        field.value = observation.date;
                        break;
                    case 'details':
                        field.value = observation.details;
                        overlay.querySelector('.open-details-button').click()//triggers openDetailsField()
                        break;
                    case 'isMissing':
                        field.checked = observation.isMissing;
                        break;
                    case 'picture':
                        displayThumbs(overlay, observation.picture);
                        break;
                    default:
                        break;
                }
            }
        );
    }
};

/* ************* *
 *  INDIVIDUAL   *
 * ************* */

export const individualOverlayManageSpecies = function(dataAttrs) {
    const speciesEl = document.getElementById('individual_species'),
        helpEl = document.getElementById('individual_species_help'),
        species = dataAttrs.species || '',
        availableSpecies = getDataAttrValuesArray(species.toString()) || null,
        showAll = typeof dataAttrs.allSpecies === "boolean" ? dataAttrs.allSpecies : ['true',1,"1"].includes(dataAttrs.allSpecies);
     let speciesNameText;

    // toggle marker and help text on already recorded species in station
    helpEl.classList.toggle('hidden',!showAll || !species);
    speciesEl.querySelectorAll('.species-option.exists-in-station').forEach(element => {
        speciesNameText = element.textContent;
        if (!showAll && /\(\+\)/.test(speciesNameText)) {
            element.textContent = speciesNameText.replace(' (+)', '');
        } else if (showAll && !/\(\+\)/.test(speciesNameText)) {
            element.textContent = speciesNameText+' (+)';
        }
    });

    updateSelectOptions(speciesEl, availableSpecies, !showAll);
};

const editIndividualPreSetFields = function(overlay, dataAttrs) {
    if (overlay.classList.contains('edit')) {
        const individual = JSON.parse(dataAttrs.individual);

        overlay.querySelector('.saisie-header').textContent = 'Modifier l’individu';
        if (individual.name) {
            document.getElementById('individual_name').value = individual.name;
        }
        document.getElementById('individual_species').classList.remove('disabled');
        if (!!individual.species.id || 0 === individual.species.id) {
            selectOption(
                overlay.querySelector('.species-option.species-'+individual.species.id)
            );
        }
    }
};

/* ************************** *
 *  OBSERVATION INFORMATION   *
 * ************************** */

const onObsInfo = function(
    openOverlayButton,
    dataAttrs
) {
    const observationElements = Array.from(
        openOverlayButton.closest('.periods-calendar').querySelectorAll('.stage-marker')
    ),
        observationDatasetKeysCriteria = ['stage', 'individualId', 'year', 'month'];

    const theseObservations = observationElements.filter(observation => {
        if (observation.classList.contains('hide')) {
            return false;
        }
        return observationDatasetKeysCriteria.every(function (key) {
            return dataAttrs[key] ===  observation.dataset[key];
        });
    });
    const obsInfoEl = document.querySelector('.obs-informations');
    let obsInfoTitle = 'Détails de l’observation';

    while(obsInfoEl.firstChild) {
        obsInfoEl.removeChild(obsInfoEl.firstChild);
    }

    if(openOverlayButton.classList.contains('absence') && 1 === theseObservations.length) {
        obsInfoTitle = 'Signalement d’absence de ce stade';
    } else if (!!theseObservations) {
        obsInfoTitle = 'Détails des observations';
    }

    theseObservations.forEach(observation => {
        obsInfoEl.append(observationListCardHtmlGenerate(observation.dataset));
        onOpenOverlay();
    });

    obsInfoEl.textcontent = obsInfoTitle;
};

const observationListCardHtmlGenerate = function(dataAttrs) {
    const observation = JSON.parse(dataAttrs.observation),
        temp = document.createElement('div');
    let editButtons = '';

    if(dataAttrs?.showEdit) {
        editButtons =
            '<div class="dual-blocks-container">'+
                '<a href="" class="dual-squared-button edit-obs edit-list-icon edit open" '+
                'data-open="observation" '+
                'data-observation-id="'+observation.id+'" '+
                '>'+
                    '<div class="squared-button-label">Éditer</div>'+
                '</a>'+
                '<a href="/observation/'+observation.id+'/delete" class="dual-squared-button delete-icon delete-button">'+
                    '<div class="squared-button-label">Supprimer</div>'+
                '</a>'+
            '</div>'
        ;
    }
    temp.innerHTML = '<div class="list-cards-item obs" data-id="'+observation.id+'">'+
            '<a href="'+dataAttrs.pictureUrl+'" class="list-card-img" style="background-image:url('+dataAttrs.pictureUrl+')" target="_blank"></a>'+
            '<div class="item-name-block">'+
                '<div class="item-name">'+observation.user.displayName+'</div>'+
                '<div class="item-name stage">'+dataAttrs.stage+'</div>'+
                '<div class="item-heading-dropdown">'+dataAttrs.date+'</div>'+
            '</div>'+
            editButtons +
        '</div>';

    return temp;
};

/* *********** *
 *  DOM READY   *
 * *********** */

domready(onOpenOverlay);
