/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you require will output into a single css file (app.css in this case)
import '../css/app.scss';
const places = require('places.js');
const algoliasearch = require('algoliasearch');
import L from 'leaflet';
import {off} from "leaflet/src/dom/DomEvent";
import 'leaflet-draw';
import 'leaflet.markercluster';

import './ui/wysiwyg';
import './ui/scientific-name';
import './ui/textarea-auto-resize';
import './ui/create-map';
import './ui/results-charts';
import './ui/results-map';

// Need jQuery? Install it with "yarn add jquery", then uncomment to require it.
const $ = require('jquery');

// create global $ and jQuery variables
global.$ = global.jQuery = $;

const $event = $('#observation_event');
const $individual = $('#observation_individual');
const $species = $('#individual_species');
const $latitude = $('#station_latitude');
const $longitude = $('#station_longitude');
const $locality = $('#station_locality');
const $observationDate = $('#observation_date');
const $stationSearchField = $('#station-search-field');
const $stationSearchForm = $('#station-search-form');
const imageType = /^image\//;

//map configuration
const MARKER_ICON = L.Icon.extend({
    options: {
        shadowUrl: '/media/map/marker-shadow.png',
        iconUrl: '/media/map/marker-icon.png',
        iconSize: [24,40],
        iconAnchor: [12,40]//correctly replaces the dot of the pointer
    }
});
const PLACES_CONFIG = {
    appId: 'plV00W9UJC60',
    apiKey: 'b8630d75d81f1343304ac3547a2994af'
};

$( document ).ready( function() {
    addModsTouchClass();
    toggleMenuSmallDevices();
    onOpenOverlay();
    onFileEvent();
    onCloseOverlay();
    switchToNextOnHomepage();
    switchTabs();
    toggleCalendar();
    toggleDateSelection();
    calendarSwitchDate();
    hideCalendarLegend();
    toggleAccodionBlock();
    stationMapDisplay();
    hideFlashMessages();
    stationSearchFormSubmit();
    if (0 < $('.ods-places').length) {
        let placesAutocomplete = placesInit();
    }
    validateEventPostDates();
    initFormEditPage();
});

// open overlay
function onOpenOverlay() {
    $('a.open').off('click').on('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        const $thisLink = $(this);

        if ($thisLink.hasClass('disabled')) {//user is not logged
            window.location.href = window.location.origin+'/user/login';
        } else {
            let dataAttrs = $thisLink.data(),
                $overlay = $('.overlay.'+dataAttrs.open);

            $overlay
                .removeClass('hidden')
            // triggers onCloseOverlay() when clicking out of container
                .on('click', function(event) {
                    if(!$(event.target).closest('.saisie-container, .obs-info-container').length) {
                        $('a.bt-annuler').trigger('click');
                    }
                })
            ;
            if(valOk($('form', $overlay))) {

                let $form = $('form', $overlay);
                $form.get(0).reset();
                if ($thisLink.hasClass('edit')) {
                    dataAttrs = setEditOverlayForm($overlay, $form, $thisLink, dataAttrs);
                }
            }

            $('body').css('overflow', 'hidden');
            switch(dataAttrs.open) {
                case 'obs-infos':
                    onObsInfo($thisLink, dataAttrs);
                    break;
                case 'station':
                    onLocation();
                    toggleMap();
                    editStationPreSetFields(dataAttrs);
                    break;
                case 'observation':
                    openDetailsField();
                    onChangeSetIndividual();
                    onChangeObsEvent();
                    onChangeObsDate();
                    observationOvelayManageIndividualAndEvents(dataAttrs);
                    editObservationPreSetFields(dataAttrs);
                    break;
                case 'individual':
                    individualOvelayManageSpecies(dataAttrs);
                    editIndividualPreSetFields(dataAttrs);
                    break;
                case 'profile':
                    editProfilePreSetFields(dataAttrs);
                    break;
                default:
                    break;
            }
            onDeleteButton(dataAttrs.open);
        }
    });
}

function setEditOverlayForm($overlay, $form, $thisLink, dataAttrs) {
    let formActionReset = '/'+dataAttrs.open+'/new',
        editionPath = '/'+dataAttrs.open;

    if (0 <= $.inArray(dataAttrs.open, ['station', 'individual', 'observation'])) {
        if ('station' !== dataAttrs.open) {
            let stationId;

            if ('observation' === dataAttrs.open) {
                let $observation = $('.stage-marker.observation-' + dataAttrs.observationId);
                // close obs-infos overlay
                $thisLink.closest('.overlay').addClass('hidden');

                dataAttrs.observation = $observation.data('observation');
                dataAttrs.individualsIds = $observation.data('individualsIds');
                dataAttrs.speciesName = dataAttrs.observation.individual.species.vernacularName;

                stationId = dataAttrs.observation.individual.station.id;
            } else {
                stationId = dataAttrs.individual.station.id;
            }
            formActionReset = '/station/' + stationId + formActionReset;
        }
        editionPath += '/'+dataAttrs[dataAttrs.open]['id'];
        $('.show-on-edit', $overlay).attr('href', editionPath+'/delete');
    }
    $overlay.addClass('edit');
    $form
        .attr('action', editionPath+'/edit')
        .data('formActionReset', formActionReset)
    ;

    return dataAttrs;
}

function onObsInfo($thisLink, dataAttrs) {
    let $thisCalendar = $thisLink.closest('.periods-calendar'),
        theseObservations = $(
            '.stage-marker' +
            '[data-stage="'+dataAttrs.stage+'"]' +
            '[data-year="'+dataAttrs.year+'"]' +
            '[data-month="'+dataAttrs.month+'"]' +
            ':visible',
            $thisCalendar
        ),
        $obsInfo = $('.obs-informations'),
        obsInfoTitle = 'Détails de l’observation';

    $obsInfo.empty();

    if(1 === theseObservations.length) {
        if ($thisLink.hasClass('absence')) {
            obsInfoTitle = 'Signalement d’absence de ce stade';
        }
    } else if (1 < theseObservations.length) {
        obsInfoTitle = 'Détails des observations';
    }

    for(let index=0;index < theseObservations.length;index++) {
        dataAttrs = theseObservations[index].dataset;

        let observation = $.parseJSON(dataAttrs.observation),
            editButtons = '';

        if(dataAttrs.showEdit) {
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
        $obsInfo.append(
            '<div class="list-cards-item obs" data-id="'+observation.id+'">'+
                '<a href="'+dataAttrs.pictureUrl+'" class="list-card-img" style="background-image:url('+dataAttrs.pictureUrl+')" target="_blank"></a>'+
                '<div class="item-name-block">'+
                    '<div class="item-name">'+observation.user.displayName+'</div>'+
                    '<div class="item-name stage">'+dataAttrs.stage+'</div>'+
                    '<div class="item-heading-dropdown">'+dataAttrs.date+'</div>'+
                '</div>'+
                editButtons +
            '</div>'
        );
        onOpenOverlay();
    }

    $('.obs-info.title').text(obsInfoTitle);
}

// close overlay
function onCloseOverlay() {
    $('.overlay a.bt-annuler').off('click').on('click', function (event) {
        event.preventDefault();

        closeOverlay($(this).closest('.overlay'));
    });
    $('body').on('keydown', function (event) {
        const ESC_KEY_STRING = /^Esc(ape)?/;
        if (27 === event.keyCode || ESC_KEY_STRING.test(event.key)) {
            closeOverlay($('.overlay:not(.hidden)'));
        }
    });
}

function closeOverlay($overlay) {
    $('body').css('overflow', 'auto');
    $overlay.addClass('hidden');

    if(valOk($('form',$overlay))) {
        let $form = $('form', $overlay);

        if ($overlay.hasClass('edit')) {
            $form.attr('action', $form.data('formActionReset'));
            $overlay.removeClass('edit');
        }
        $form.get(0).reset();

        $overlay.find('option').removeAttr('hidden disabled');

        if ($overlay.hasClass('individual')) {
            individualOvelayManageSpecies($('.open-individual-form-all-station').data().species.toString(), true);
        } else {
            if ($overlay.hasClass('observation')) {
                observationOvelayManageIndividualAndEvents($('.open-observation-form-all-station').data());
                $('.ods-form-warning').addClass('hidden').text('');
            } else if($overlay.hasClass('station')) {
                mapRemove();
                placesRemove();
            }
            $('.delete-file').trigger('click');
            $('.is-delete-picture').remove();
        }
        $('.show-on-edit', $overlay).attr('href','');
    } else if ($overlay.hasClass('obs-infos')) {
        $('.saisie-container').find('.obs-info').text('');
    }
}

function editStationPreSetFields(dataAttrs) {
    let $overlay = $('.overlay.station');

    if ($overlay.hasClass('edit')) {
        let station = dataAttrs.station;

        $('.saisie-header',$overlay).text('Modifier la station');

        if (valOk(station.name) && '' !== station.name) {
            $('#station_name').val(station.name);
        }
        if (valOk(station.description) && '' !== station.description) {
            $('#station_description').val(station.description);
        }
        if (valOk(station.latitude) && '' !== station.latitude) {
            $latitude.val(station.latitude);
        }
        if (valOk(station.longitude) && '' !== station.longitude) {
            $longitude.val(station.longitude).trigger('blur');
        }
        if (valOk(station.habitat)) {
            $('#station_habitat')
                .find('option[value="'+station.habitat+'"]')
                .prop('selected', true).attr('selected', 'selected')
            ;
        }
        if (valOk(station.isPrivate)) {
            $('#station_isPrivate').prop('checked', station.isPrivate);
        }
        if (valOk(station.headerImage) && '' !== station.headerImage) {
            $('.upload-zone-placeholder').addClass('hidden');
            $('img.placeholder-img').addClass('obj').attr('src', station.headerImage);
        }
    }
}

function observationOvelayManageIndividualAndEvents(dataAttrs) {
    let availableIndividuals = dataAttrs.individualsIds ? getDataAttrValuesArray(dataAttrs.individualsIds.toString()) : [],
        $formTitle = $('.overlay.observation .saisie-title'),
        stationName = $formTitle.data('stationName');

    updateSelectOptions($individual, availableIndividuals);
    $individual.trigger('change');//triggers onChangeSetIndividual()
    // Display species name in title
    if (valOk(dataAttrs.speciesName)) {
        let speciesName = dataAttrs.speciesName;
        // capitalize species name
        speciesName = speciesName.charAt(0).toUpperCase() + speciesName.slice(1);
        // unescape special chars and display in title
        $formTitle.html(speciesName).text();
    } else {
        // unescape special chars and display in title
        $formTitle.html(stationName).text();
    }
}

function editObservationPreSetFields(dataAttrs) {
    let $overlay = $('.overlay.observation');

    if ($overlay.hasClass('edit')) {
        let observation = dataAttrs.observation;

        $('.saisie-header',$overlay).text('Modifier l’observation');

        $individual
            .removeClass('disabled')
            .find('.individual-option.individual-'+observation.individual.id)
            .prop('hidden', false).removeAttr('hidden')
            .prop('disabled', false).removeAttr('disabled')
            .prop('selected', true).attr('selected', 'selected')
        ;
        $individual.trigger('change');
        if (valOk(observation.event.id)) {
            $event
                .find('.event-option.event-'+observation.event.id)
                .prop('hidden', false).removeAttr('hidden')
                .prop('disabled', false).removeAttr('disabled')
                .prop('selected', true).attr('selected', 'selected')
            ;
        }
        if (valOk(observation.date)) {
            $observationDate.val(observation.date);
        }
        if (valOk(observation.isMissing)) {
            $('#observation_isMissing').prop('checked', observation.isMissing);
        }
        if (valOk(observation.picture) && '' !== observation.picture) {
            $('.upload-zone-placeholder').addClass('hidden');
            $('img.placeholder-img').addClass('obj').attr('src', observation.picture);
        }
        if (valOk(observation.details) && '' !== observation.details) {
            $('#observation_details').val(observation.details);
            $('.open-details-button').trigger('click')//triggers openDetailsField()
        }
    }
}

function individualOvelayManageSpecies(dataAttrs) {
    let species = dataAttrs.species || '',
        availableSpecies = getDataAttrValuesArray(species.toString()) || null,
        showAll = dataAttrs.allSpecies,
        $element = null,
        speciesNameText = '';

    // toggle marker and help text on already recorded species in station
    $species.siblings('.field-help-text').toggleClass('hidden', !showAll || !valOk(species));
    $('.species-option.exists-in-station', $species).each(function (i,element) {
        $element = $(element);
        speciesNameText = $element.text();
        if (!showAll && /\(\+\)/.test(speciesNameText)) {
            $element.text(speciesNameText.replace(' (+)', ''));
        } else if (showAll && !/\(\+\)/.test(speciesNameText)) {
            $element.text(speciesNameText+' (+)');
        }
    });

    updateSelectOptions($species, availableSpecies, !showAll);
}

function editIndividualPreSetFields(dataAttrs) {
    let $overlay = $('.overlay.individual');

    if ($overlay.hasClass('edit')) {
        let individual = dataAttrs.individual;

        $('.saisie-header', $overlay).text('Modifier l’individu');

        if (valOk(individual.name) && '' !== individual.name) {
            $('#individual_name').val(individual.name);
        }
        $species.removeClass('disabled');
        if (valOk(individual.species.id)) {
            $('.species-option.species-'+individual.species.id)
                .prop('hidden', false).removeAttr('hidden')
                .prop('disabled', false).removeAttr('disabled')
                .prop('selected', true).attr('selected', 'selected')
            ;
        }
    }
}

// returns an array of values from data attributes value
function getDataAttrValuesArray(dataAttrValue) {
    if (0 > dataAttrValue.indexOf(',')) {
        return [dataAttrValue];
    } else {
        return dataAttrValue.split(',');
    }
}

function updateSelectOptions($selectEl, itemsToMatch, sortOptions = true) {
    let selectName = $selectEl.data('name');

    $selectEl
        .toggleClass('disabled',(1 >= itemsToMatch.length && sortOptions))
        .find('option')
            .prop('hidden', false).removeAttr('hidden')
            .prop('disabled', false).removeAttr('disabled')
            .prop('selected', false).removeAttr('selected')
            .closest('form').get(0).reset();

    if(sortOptions) {
        $('.' + selectName + '-option', $selectEl).each(function (i, element) {
            let $element = $(element);

            if (0 <= itemsToMatch.indexOf($element.val().toString())) {
                if (1 === itemsToMatch.length && $element.hasClass(selectName + '-' + itemsToMatch[0])) {
                    $element.prop('selected', true).attr('selected', 'selected');
                }
            } else {
                $element
                    .prop('hidden', true).attr('hidden', 'hidden')
                    .prop('disabled', true).attr('disabled', 'disabled')
                ;
            }
        });
        if(1 === itemsToMatch.length) {
            $selectEl.val(itemsToMatch[0]);
        }
    }
}

function onChangeSetIndividual() {
    $individual.off('change').on('change', function () {
        let $selectedIndividual = $('.individual-option:selected', this);

        $event.find('.event-option')
            .prop('selected', false).removeAttr('selected')
            .prop('hidden', true).attr('hidden','hidden')
            .prop('disabled', true).attr('disabled','disabled')
        ;

        if (valOk($selectedIndividual)) {
            let speciesPicture = $selectedIndividual.data('picture'),
                availableEvents = getDataAttrValuesArray($selectedIndividual.data('availableEvents').toString()),
                aberrationsDays = $selectedIndividual.data('aberrationsDays');

            updateSpeciesPageUrl($selectedIndividual);
            $event.removeAttr('disabled').prop('disabled', false);
            if (1 === availableEvents.length) {
                $event
                    .addClass('disabled')
                    .find('.event-option.event-'+availableEvents[0])
                        .prop('hidden', false).removeAttr('hidden')
                        .prop('disabled', false).removeAttr('disabled')
                        .prop('selected', true).attr('selected','selected')
                        .data('picture', '/media/species/' + speciesPicture + '.jpg')
                ;
                if(0 < aberrationsDays.length) {
                    setEventAberrationDaysDataAttr(availableEvents[0], aberrationsDays);
                }
            } else {
                let $eventOption = null;

                $event.removeClass('disabled');
                for (let i = 0; i < availableEvents.length; i++) {
                    $eventOption = $('.event-option.event-'+availableEvents[i], $event);

                    let eventPictureSuffix = $eventOption.data('pictureSuffix');

                    $eventOption
                        .prop('hidden', false).removeAttr('hidden')
                        .prop('disabled', false).removeAttr('disabled')
                        .data('picture','/media/species/'+speciesPicture+eventPictureSuffix+'.jpg')
                    ;
                    if(0 < aberrationsDays.length) {
                        setEventAberrationDaysDataAttr(availableEvents[i], aberrationsDays);
                    }
                }
            }
        } else {
            $event.addClass('disabled').attr('disabled', 'disabled').prop('disabled', true);
        }
        $event.trigger('change');// triggers onChangeObsEvent()
    });
}

function updateSpeciesPageUrl($selectedIndividual)
{
    let $link = $('.saisie-aide-txt a.green-link'),
        url = $link.attr('href'),
        speciesInUrl = url.substring(url.lastIndexOf('/')+1),
        species = $selectedIndividual.data('speciesName');

    if (speciesInUrl !== species) {
        $link.attr('href',url.replace(speciesInUrl,species));
    }

}
function setEventAberrationDaysDataAttr(eventId, aberrationsDays) {
    let eventOptionEl = $('.event-option.event-'+eventId, $event)[0],
        eventAberrationDays = aberrationsDays.filter(function (aberrationDays) {
            return parseInt(aberrationDays.eventId) === parseInt(eventId);
        })[0];

    $.each(eventAberrationDays, function (dataAttrName, value) {
        if('eventId' !== dataAttrName) {
            eventOptionEl.dataset[dataAttrName] = value;
        }
    });
}

function onChangeObsEvent() {
    $event.off('change').on('change', function () {
        let isValidEvent = valOk($(this).val());
        updateHelpInfos(isValidEvent);
        if (isValidEvent) {
            if (valOk($observationDate.val())) {
                checkAberrationsObsDays();
            }
        }
    });
}

function updateHelpInfos(isValidEvent) {
    let $saisieAide = $('.saisie-aide.event');

    $('img', $saisieAide).remove();

    if (isValidEvent) {
        let $selectedEvent = $('.event-option:selected', $event),
            eventStage = $selectedEvent.text();

        $saisieAide.removeClass('hidden').prepend(
            '<img src="'+ $selectedEvent.data('picture') + '" alt="' + eventStage + '" width="80" height="80">'
        );
        $('.text-aide-1.event').text(eventStage);
        $('.text-aide-2.event').text($selectedEvent.data('description'));
    } else {
        $saisieAide.addClass('hidden');
    }
}

function onChangeObsDate() {

    $observationDate.off('change').on('change', function () {

        // front validation for safari input type date to type text
        let date = $(this).val(),
            isDateValid = valOk(date);

        if (isDateValid && /^([\d]{2}\/){2}[\d]{4}$/.test(date)) {
            let dateArray = date.split('/'),
                now = new Date(),
                minDate = new Date('2006-01-01'),
                jsDate = new Date(dateArray.reverse().join('-'));
            if(minDate > jsDate || now < jsDate) {
                let message ='';
                if (minDate > jsDate) {
                    message = 'Cette date est antérieure au programme ODS';
                } else {
                    message = 'Cette date est postérieure à aujourd’hui';
                }
                displayDateError($(this), message);
            }
        }

        // check aberration dates
        if(isDateValid && valOk($event.val())) {
            checkAberrationsObsDays();
        }
    });
}

function checkAberrationsObsDays() {
    let $selectedEvent = $('.event-option:selected', $event),
        aberrationStartDay = $selectedEvent.data('aberrationStartDay'),
        aberrationEndDay = $selectedEvent.data('aberrationEndDay'),
        observationDay = $observationDate.val().slice(5),
        message = '';

    function comparativeTimeValue(day) {
        return parseInt(day.replace('-', ''));
    }

    if(
        valOk(aberrationStartDay) && valOk(aberrationEndDay) && valOk(observationDay)
        && (
            comparativeTimeValue(aberrationStartDay) > comparativeTimeValue(observationDay)
            || comparativeTimeValue(aberrationEndDay) < comparativeTimeValue(observationDay)
        )
    ) {
        let species = $('.individual-option:selected', $individual).attr('speciesName')
        message = 'La date que vous venez de saisir sort de la période habituelle pour cet événement chez cette espèce ('+$selectedEvent.data('displayedStartDate')+' au '+$selectedEvent.data('displayedEndDate')+'). ' +
            'Si vous êtes sûr(e) de votre observation, ne tenez pas compte de ce message, sinon, vérifiez qu’il s’agit bien de ce stade et de cette <a href="/especes/'+species+'" target="_blank" class="green-link small">espèce</a>. ' +
            'Si vous restez dans le doute, <a href="" target="_blank" class="green-link small">contactez nous</a>.';
    }
    $('.ods-form-warning')
        .toggleClass('hidden', message === '')
        .empty()
        .append(message)
    ;
}

// Create the map
function mapInit(lat = 46.7111, lon = 1.7191, zoom = 6) {
    if(valOk($latitude.val()) && valOk($longitude.val())) {
        lat = $latitude.val();
        lon = $longitude.val();
        zoom = 12;
    }
    return mapDisplay('map', lat, lon, zoom);
}

function mapLocation() {
    let marker = null;
    if (!$('#map').hasClass('hidden')) {
        let mapInfo = mapInit(),
            marker = mapInfo.marker;
        var map = mapInfo.map;

        // interactions with map
        map.on('click', function (e) {
            onPosition(map, e.latlng, marker);
        });
        marker.on('dragend', function (e) {
            onPosition(map, marker.getLatLng(), e.target);
        });
    } else {
        mapRemove();
    }
    return {marker: marker, map: map};
}

// Remove the map
function mapRemove() {
    // reset map
    var map = L.DomUtil.get('map');

    if(map != null){
        map._leaflet_id = null;
    }
    $('#map').remove();
    $('#open-map').removeClass('hidden');
    $('#close-map').addClass('hidden');
    $('.map-container').append('<div id="map" class="hidden"></div>');
}

// intit places
function placesInit() {
    placesRemove();
    return places({
        appId: PLACES_CONFIG.appId,
        apiKey: PLACES_CONFIG.apiKey,
        container: '.ods-places',
        language: 'fr',
        countries: ['fr']
    });
}

function stationPlacesLocation(placesAutocomplete, map, marker) {
    //Algolia places configuration
    placesAutocomplete.on('change', function (e) {
        onPosition(map, e.suggestion.latlng, marker);
    });
    // algolia places search
    $locality.on('blur',function () {
        const localitySearch = algoliasearch.initPlaces(PLACES_CONFIG.appId, PLACES_CONFIG.apiKey);
        localitySearch.search({
            query: $(this).val()
        }).then(function (results) {
            let hits = results.hits;
            if (hits[0]) {
                onPosition(map, hits[0]._geoloc, marker);
                placesRemove();
            }
        });
    });
}

// remove places
function placesRemove() {
    $('.ap-icon-clear').trigger('click');
}

// Location events management
function onLocation() {
    let mapInfo = mapLocation(),
        marker = mapInfo.marker,
        map = mapInfo.map;
    // location fields filled
    $('#station_latitude, #station_longitude').on('blur', function() {
        let latitude = parseFloat($latitude.val()),
            longitude = parseFloat($longitude.val());
        if(!isNaN(latitude) && !isNaN(longitude)) {
            onPosition(map, {'lat': latitude,'lng': longitude}, marker);
        }
    });
    stationPlacesLocation(placesAutocomplete, map, marker);
}

// Fills location fields when information is available
function onPosition(map, position, marker = null) {
    let transmittedLatitude = Number.parseFloat(position.lat).toFixed(4),
        transmittedLongitude = Number.parseFloat(position.lng).toFixed(5),
        $addLoadingClassElements = $('#station_locality,#station_inseeCode').siblings('label');
    //updates coordinates fields
    $latitude.val(transmittedLatitude);
    $longitude.val(transmittedLongitude);
    if(valOk(marker) && valOk(map)) {
        // updates map
        map.panTo(new L.LatLng(position.lat, position.lng));
        marker.setLatLng(new L.LatLng(position.lat, position.lng), {draggable: 'true'});
    }
    // displays loading gif
    $addLoadingClassElements.addClass('loading');
    $.ajax({
        method: "GET",
        url: 'https://www.obs-saisons.fr/applications/jrest/OdsCommune/informationsPourCoordonnees',
        data: {'lat': position.lat, 'lon': position.lng},
        success: function (data) {
            let locationInformations = JSON.parse(data);
            // updates location informations fields
            $locality.val(locationInformations.commune);
            $('#station_inseeCode').val(locationInformations.code_insee);
            $('#station_altitude').val(locationInformations.alt);
            // stops displaying loading gif
            $addLoadingClassElements.removeClass('loading');
        }
    });
}

function toggleMap() {
    $('.open-map-button').off('click').on('click', function (e) {
        e.preventDefault();
        $(this).find('span').toggleClass('hidden');
        $('#map').toggleClass('hidden');
        mapLocation();
    });
}

function onFileEvent() {
    let droppedFiles = false,
        $picture = $('.upload-zone .upload-input');

    if(valOk($picture)) {
        let isAdvancedUpload = function () {
            let div = document.createElement('div');

            return (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) && 'FormData' in window && 'FileReader' in window;
        }();

        if (isAdvancedUpload) {
            $picture
                .on('drag dragstart dragend dragover dragenter dragleave drop', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                })
                .on('dragover dragenter', function () {
                    $picture.addClass('is-dragover');
                })
                .on('dragleave dragend drop', function () {
                    $picture.removeClass('is-dragover');
                })
                .on('drop', function (event) {
                    if (event.originalEvent) {
                        droppedFiles = event.originalEvent.dataTransfer.files;
                        $('.is-delete-picture').remove();
                        displayThumbs(droppedFiles);
                        ajaxSendFile($(this), droppedFiles);
                    }
                });


        }
        $picture.on('change', function (event) {
            droppedFiles = event.target.files;
            console.log(droppedFiles);
            $('.is-delete-picture').remove();
            displayThumbs(droppedFiles);
        });

        onDeleteFile()
    }
}

function displayThumbs(files) {
    let uploadZonePlaceholder = $('.upload-zone-placeholder');

    if (!files) {
        uploadZonePlaceholder.removeClass('hidden').text('L’image n’a pas pu être téléchargée.');
    }

    let file = files[0];

    if (!imageType.test(file.type)) {
        uploadZonePlaceholder.removeClass('hidden').text('Le format du fichier n’est pas valide.');
    }

    let $img = $('img.placeholder-img'),
        reader = new FileReader();

    $img.addClass('obj');
    $img.file = file;
    reader.onload = (function(aImg) {
        return function(event) {
            aImg.attr('src', event.target.result);
        };
    })($img);
    reader.readAsDataURL(file);
    uploadZonePlaceholder.addClass('hidden');
}

function ajaxSendFile($picture, files) {
    let $form = $picture.closest('form');

    $form.on('submit.ddupload', function(e) {
        if ($form.hasClass('is-uploading')) {
            return false;
        }

        $form.addClass('is-uploading').removeClass('is-error');

        e.preventDefault();

        let ajaxData = new FormData($form.get(0));

        if (files) {
            let file = files[0];

            if (!imageType.test(file.type)) {
                return false;
            }
            ajaxData.append($picture.attr('name'), file);
        }

        $.ajax({
            type: $form.attr('method'),
            url: $form.attr('action'),
            data: ajaxData,
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            complete: function () {
                $form.removeClass('is-uploading');
            },
            success: function (data) {
                $form.addClass(data.success ? 'is-success' : 'is-error');
                window.location.href = data.redirect;
            },
            error: function () {
                console.log('Drag’n’drop file upload failed.');
            }
        });
    });
}

function onDeleteFile() {
    $('.delete-file').off('click').on('click', function (event) {
        event.preventDefault();
        let action = $(this).closest('form').attr('name');

        $('.upload-zone .upload-input')
            .val('')
            .after(
                '<input type="hidden" class="is-delete-picture" name="'+action+'[isDeletePicture]" value="true">'
            )
            .closest('form').off('submit.ddupload')
        ;
        $('.placeholder-img').removeClass('obj').attr('src', '/media/layout/icons/photo.svg');
        $('.upload-zone-placeholder').removeClass('hidden').text('Ajoutez une photo');

    });
}

function openDetailsField() {
    $('.open-details-button').on('click', function (e) {
        e.preventDefault();

        $(this).closest('.button-form-container').addClass('hidden');
        $('.details-container').removeClass('hidden');
    })
}

//touch or desktop device
function addModsTouchClass() {
    $('html').toggleClass('ods-mod-touch', window.matchMedia('(max-width: 991px)').matches);
    $(window).off('resize').on('resize', addModsTouchClass);
}

// open/close menu on small devices
function toggleMenuSmallDevices(){
    $('.menu-img, .close-menu-img').on('click', function() {
        let menuTanslateX = ( /close/.test(this.className) ? '-' : '' ) + '280px';

        $('.menu').animate({
            right: menuTanslateX
        }, 200);
    });
}

// switch between tabs
function switchTabs() {
    let $tabsHolder = $('.tabs-holder:not(.stations)');
    resetTabMatchingElements($tabsHolder);

    $('.tab').off('click').on('click', function (event) {
        event.preventDefault();

        let activeTab = $(this).data('tab');
        $tabsHolder.data('active', activeTab).attr('data-active', activeTab);
        $('[data-tab]').each(function (i, element) {
            let $element = $(element);

            if ($element.hasClass('tab')) {
                $element.toggleClass('not',(activeTab !== $element.data('tab')));
            } else {
                let toggleElement = ('all' === activeTab || $element.data('tab') === activeTab);
                // for the case of observations
                if (valOk($element.data('year'))) {
                    let activeDate = $element.closest('.table-container').find('.active-year').text();

                    toggleElement = observationsToggleCombinedConditions($element, activeDate, toggleElement);
                }
                if(toggleElement) {
                    $element.show(200);
                } else {
                    $element.hide(200);
                }
            }
        });
    });
}

function resetTabMatchingElements($tabsHolder) {
    let activeTab = $tabsHolder.data('active');

    if(activeTab !== 'all') {
        $('[data-tab]:not(.tab)').each(function () {
            if(activeTab !== $(this).data('tab')) {
                $(this).hide();
            }
        });
    }
}

// Open/close calendar
function toggleCalendar() {
    $('a.item-heading-dropdown').off('click').on('click', function (event) {
        event.preventDefault();

        let id = $(this).closest('.list-cards-item').data('id');

        $(this).toggleClass('right-arrow-orange-icon down-arrow-icon');
        $('.periods-calendar[data-id="' + id + '"]').toggle(200);
    });

    $('.table-mask-button').off('click').on('click', function (event) {
        event.preventDefault();

        let id = $(this).closest('.periods-calendar').data('id');

        $('.list-cards-item[data-id="' + id + '"] a.item-heading-dropdown').trigger('click');
    });
}

// open/close date selection
function toggleDateSelection() {
    $('.dropdown-toggle').off('click').on('click', function (event) {
        event.preventDefault();

        $(this).siblings('.dropdown-list').toggleClass('hidden');
    })
}

// select new date and show/hide observations
function calendarSwitchDate() {
    $('.periods-calendar .dropdown-link').off('click').on('click', function (event) {
        event.preventDefault();

        let $thisCalendar = $(this).closest('.periods-calendar'),
            activeDate = $(this).text();

        $('.active-year', $thisCalendar).text(activeDate);
        $('.dropdown-link.hidden', $thisCalendar).removeClass('hidden');
        $(this).addClass('hidden');
        $('.dropdown-list', $thisCalendar).addClass('hidden');
        // show/hide observations
        $('.stage-marker', $thisCalendar).each( function () {
            let $element = $(this);

            if(observationsToggleCombinedConditions($element, activeDate)) {
                $element.show(200);
            } else {
                $element.hide(200);
            }
            resetTabMatchingElements($('.tabs-holder:not(.stations)'));
        });
    });
}

function observationsToggleCombinedConditions ($element, activeDate, matchsTab = null) {
    let showObs = $element.data('year').toString() === activeDate;

    if (null !== matchsTab) {// if matchsTab is defined it is boolean
        showObs &= matchsTab;
    }
    return showObs;
}

function hideCalendarLegend () {
    $('.helper-legend .hide-button').click(function (event) {
        event.preventDefault();

        $('.pages-container').find('.helper-legend').hide(200);
    })
}

function toggleAccodionBlock() {
    $('a.accordion-title-dropdown').off('click').on('click', function (event) {
        event.preventDefault();

        let $thisBlock = $(this).closest('.accordion-block');

        $(this).toggleClass('right-arrow-orange-icon down-arrow-icon');
        $('.accordion-content', $thisBlock).toggle(200);
    });
}

function switchToNextOnHomepage() {
    $('.nav-arrow').off('click').on('click', function (event) {
        event.preventDefault();

        let $thisBlock = $(this).closest('.nav-arrow-buttons'),
            targetClass = '.'+$thisBlock.data('target'),
            $visibleTargetPost = $(targetClass).not('.hidden'),
            isActuUne = (targetClass === '.actu-une-container'),
            direction = $(this).data('direction');

        $visibleTargetPost.addClass('hidden');
        $thisBlock.find('.nav-arrow.inactive').removeClass('inactive');

        let $newTargetPost = findNextTarget($visibleTargetPost, targetClass, direction);

        if ($newTargetPost) {
            if (!findNextTarget($newTargetPost, targetClass, direction)) {
                $thisBlock.find('.nav-arrow.'+direction).addClass('inactive');
            }
            $newTargetPost.removeClass('hidden');
            if (isActuUne) {
                let imageClass = '.actus-une-img',
                    $visibleTargetImage = $(imageClass).not('.hidden');
                $visibleTargetImage.addClass('hidden');
                let $newTargetImage = findNextTarget($visibleTargetImage, imageClass, direction);
                if ($newTargetImage) {
                    $newTargetImage.removeClass('hidden');
                }
            }
        }
    });
}

function findNextTarget($element, targetClass, direction) {
    let $nextDisplayedElement = $element.next(targetClass).length ? $element.next(targetClass) : $(targetClass).first();
    if('prev' === direction) {
        $nextDisplayedElement = $element.prev(targetClass).length ? $element.prev(targetClass) : $(targetClass).last();
    }
    return $nextDisplayedElement;
}

function stationMapDisplay() {
    let $headerMapDisplay = $('#headerMap');
    if (valOk($headerMapDisplay) && $headerMapDisplay.hasClass('show-map')) {
        let lat = $headerMapDisplay.data('latitude'),
            lng = $headerMapDisplay.data('longitude');
        mapDisplay('headerMap', lat, lng, 12, false, false);
    }
}

function mapDisplay(
    elementIdAttr,
    lat,
    lng,
    zoom,
    hasZoomcontrol = true,
    isDraggable = true
) {
// Create the map
    let map = L.map(elementIdAttr, {zoomControl: hasZoomcontrol}).setView([lat, lng], zoom);
    // Set up the OSM layer
    L.tileLayer(
        'https://osm.tela-botanica.org/tuiles/osmfr/{z}/{x}/{y}.png', {
            attribution: 'Data © <a href="http://osm.org/copyright">OpenStreetMap</a>',
            maxZoom: 18
        }).addTo(map)
    ;
    // Initialise the FeatureGroup to store editable layers
    map.addLayer(new L.FeatureGroup());
    let marker = new L.Marker(
        {
            'lat':lat,
            'lng':lng
        },
        {
            draggable: isDraggable,
            icon: new MARKER_ICON()
        }
    );
    map.addLayer(marker);

    return {map:map,marker:marker};
}

function editProfilePreSetFields(dataAttrs) {
    let user = dataAttrs.user;

    if ($('.overlay.profile').hasClass('edit')) {
        if (valOk(user.avatar) && '' !== user.avatar) {
            $('.upload-zone-placeholder').addClass('hidden');
            $('img.placeholder-img').addClass('obj').attr('src', user.avatar);
        }
    }
}

function onDeleteButton(subject) {
    $('.delete-button').off('click').on('click', function (event) {


        let question = 'Êtes vous sûr de vouloir supprimer ce';
        switch (subject) {
            case 'obs-infos':
                subject = 'observation';
            case 'station':
            case 'observation':
                question += 'tte '+subject;
                break;
            case 'individual':
                question += 't individu';
                break;
            default:
                question += 't élément';
                break;
        }
        question += '?';

        if(!confirm(question)) {
            event.preventDefault();
        }
    });
}

function validateEventPostDates() {
    let $postEventsStartDate = $('#post_events_startDate'),
        $postEventsEndDate = $('#post_events_endDate'),
        errorMessage = '',
        todayDate = generateComparableFormatedDate(new Date());

    $postEventsStartDate.on('change', function () {
        let hasStartDate = valOk($(this).val()),
            hasEndDate = valOk($postEventsEndDate.val()),
            isInvalidStartDate = !hasStartDate;

        errorMessage = 'Vous devez entrer une date de début valide';

        if (hasStartDate && hasEndDate) {
            let startDate = generateComparableFormatedDate($(this).val()),
                endDate = generateComparableFormatedDate($postEventsEndDate.val()),
                minDate =  generateComparableFormatedDate(new Date('2006-01-01')),
                isBeforeBeginningOfTime = minDate > startDate,
                isTimeTrip = endDate < startDate;

            isInvalidStartDate = isBeforeBeginningOfTime || isTimeTrip;

            if (isBeforeBeginningOfTime) {
                errorMessage = 'Cette date est antérieure à ODS';
            } else if (isTimeTrip) {
                errorMessage = 'Votre date de fin précède votre date de début.';
            }
        }

        if (isInvalidStartDate) {
            displayDateError($(this), errorMessage);
        }
    });

    $postEventsEndDate.on('change', function () {
        let hasEndDate = valOk($(this).val()),
            hasStartDate = valOk($postEventsStartDate.val()),
            isInvalidEndDate = !hasEndDate;

        errorMessage = 'Vous devez entrer une date de fin valide';

        if(hasEndDate) {
            let endDate = generateComparableFormatedDate($(this).val());

            if(todayDate > endDate) {
                isInvalidEndDate = true;
                errorMessage = 'Nous ne publions pas les évènements terminés'

            } else if(hasStartDate) {
                let startDate = generateComparableFormatedDate($postEventsStartDate.val());

                if(startDate > endDate) {
                    isInvalidEndDate = true;
                    errorMessage = 'Votre date de fin précède votre date de début.';
                }
            }
        }

        if(isInvalidEndDate) {
            displayDateError($(this), errorMessage);
        }
    });
}

function generateComparableFormatedDate(dateData) {
    if (/^([\d]{2}\/){2}[\d]{4}$/.test(dateData)) {
        dateData = dateData.split('/').reverse();
    } else if(/^[\d]{4}(-[\d]{2}){2}$/.test(dateData)) {
        dateData = dateData.split('-');
    } else {
         dateData = dateData
            .toISOString()
            .substr(0, 10)
            .split('-');
    }

    return dateData.join('');
}

function displayDateError($field, errorMessage)
{
    $field
        .val('')
        .after(
            '<p class="invalid-date field-help-text help-text" style="color:red;">' + errorMessage + '</p>'
        )
    ;
    setTimeout(function () {
        $('.invalid-date').hide(200, function () {
            $(this).remove();
        });
    }, 3000);
}

function initFormEditPage() {
    let $uploadInput = $('.upload-input'),
        isPageForm = $uploadInput.closest('.saisie-container').hasClass('page'),
        image = $uploadInput.closest('.form-col').data('image');

    if (isPageForm && valOk(image) && '' !== image) {
        $('.upload-zone-placeholder').addClass('hidden');
        $('img.placeholder-img').addClass('obj').attr('src', image);
    }

    let $places = $('.ods-places');

    if (0 < $places.length && valOk($places.val())) {
        $places.siblings('button.ap-input-icon').toggle();
    }

}

// trigger search field submit
function stationSearchFormSubmit() {
    // submitting form without submit button
    $stationSearchField.on('blur', function () {
        $stationSearchForm.trigger('submit');
    });
    // form is being submitted on blur or on enter key press
    $stationSearchForm.on('submit', function (event) {
        // avoid submitting empty strings
        if(!valOk($.trim($stationSearchField.val()))) {
            event.preventDefault();
        }
    });
}

function hideFlashMessages() {
    $('.app-flashes').delay(5000).slideUp(300);
}

function valOk(value, comparisonDirection = true, compareTo = null) {
    var result = true;
    if ('boolean' !== typeof value) {
        switch(typeof value) {
            case 'string' :
                result = ('' !== value);
                break;
            case 'number' :
                result = (!isNaN(value));
                break;
            case 'object' :
                result = (null !== value && undefined !== value && !$.isEmptyObject(value));
                if (null !== value && undefined !== value.length) {
                    result = (result && 0 < value.length);
                }
                break;
            case 'undefined' :
            default :
                result = false;
        }
        if (result && compareTo !== null) {
            var comparisonResult = (compareTo === value);
            result = (comparisonDirection) ? comparisonResult : !comparisonResult;
        }
        return result;
    } else {
        // Boolean is valid value
        return true;
    }
}
