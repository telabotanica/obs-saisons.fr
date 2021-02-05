/**************************************************
 * CSS
 **************************************************/
//
import '../css/app.scss';
//
/**************************************************
 * POLYFILLS
 **************************************************/
//
import 'core-js/features/object/assign';
import 'core-js/features/object/values';
import 'core-js/features/array/from';
import 'core-js/features/array/for-each';
import 'core-js/features/promise';
//
/**************************************************
 * GLOBALS
 **************************************************/
//
import './ui/provide-jquery';
import './ui/check-value-valid';
import './ui/date-error-display';
//
/**************************************************
 * LEAFLET
 **************************************************/
//
import L from 'leaflet';
import 'leaflet-draw';
import 'leaflet.markercluster';
//
/**************************************************
 * COMPONENTS
 **************************************************/
//
import './ui/wysiwyg';
import './ui/scientific-name';
import './ui/textarea-auto-resize';
import './ui/create-map';
import './ui/results-charts';
import './ui/results-map';
import './ui/oembed-to-iframe';
import './ui/mod-touch';
import './ui/hide-flash-messages';
import './ui/toggle-menu-small-device';
import './ui/handle-file-uploads';
import './ui/switch-to-next-post';
import './ui/switch-tabs';
import './ui/calendar-switch-date';
import './ui/calendar-toggle-date-selection';
import './ui/calendar-toggle';
import './ui/calendar-hide-legend';
import './ui/accordion-block-toggle';
import './ui/station-search-form-submit';
import './ui/event-post-dates-validate';


const $event = $('#observation_event');
const $individual = $('#observation_individual');
const $species = $('#individual_species');
const $latitude = $('#station_latitude');
const $longitude = $('#station_longitude');
const $locality = $('#station_locality');
const $observationDate = $('#observation_date');
const $adminDeleteUser = $('#admin-delete-user');

const places = require('places.js');
const algoliasearch = require('algoliasearch');
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
    let placesAutocomplete = {};
    if (0 < $('.ods-places').length) {
        placesAutocomplete = placesInit();
    }

    onOpenOverlay(placesAutocomplete);
    onCloseOverlay();
    stationMapDisplay();
    initFormEditPage();
    if(0 < $adminDeleteUser.length) {
        userDeleteAdminConfirm();
    }
});

// open overlay
function onOpenOverlay(placesAutocomplete) {
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
                case 'admin-profile':
                    editProfilePreSetFields(dataAttrs);
                    break;
                case 'obs-infos':
                    onObsInfo($thisLink, dataAttrs);
                    break;
                case 'station':
                    onLocation(placesAutocomplete);
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
    if ('admin-profile' === dataAttrs.open) {
        $form.attr('action', dataAttrs.editionPath);
    } else {
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
            editionPath += '/' + dataAttrs[dataAttrs.open]['id'];
            $('.show-on-edit', $overlay).attr('href', editionPath + '/delete');
        }
        $overlay.addClass('edit');
        $form
            .attr('action', editionPath+'/edit')
            .data('formActionReset', formActionReset)
        ;
    }

    return dataAttrs;
}

function onObsInfo($thisLink, dataAttrs) {
    let $thisCalendar = $thisLink.closest('.periods-calendar'),
        theseObservations = $(
            '.stage-marker' +
            '[data-stage="'+dataAttrs.stage+'"]' +
            '[data-individual-id="'+dataAttrs.individualId+'"]' +
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
            .prop('selected', false).removeAttr('selected')
            .closest('form').get(0).reset();

    $selectEl.find('option:not(.exists-in-station.animal)')
        .prop('disabled', false).removeAttr('disabled');

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
            'Si vous êtes sûr(e) de votre observation, ne tenez pas compte de ce message, sinon, vérifiez qu’il s’agit bien de ce stade et de cette <a href="/especes/'+species+'" target="_blank" class="deep-green-link small">espèce</a>. ' +
            'Si vous restez dans le doute, <a href="" target="_blank" class="deep-green-link small">contactez nous</a>.';
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
    let $places = $('.ods-places');
    if (0 < $places.length && valOk($places.val())) {
        $places.siblings('button.ap-input-icon').toggle();
    }

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
function onLocation(placesAutocomplete) {
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

    if (!valOk(placesAutocomplete)) {
        let placesAutocomplete = placesInit();
    }

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

function openDetailsField() {
    $('.open-details-button').on('click', function (e) {
        e.preventDefault();

        $(this).closest('.button-form-container').addClass('hidden');
        $('.details-container').removeClass('hidden');
    })
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

    if ($('.overlay.'+dataAttrs.open).hasClass('edit')) {
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

function userDeleteAdminConfirm() {
    $adminDeleteUser.on('click', function (event) {
        if(!confirm('Confirmer la suppression du compte')) {
            event.preventDefault();
        }
    });
}
