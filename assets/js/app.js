/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you require will output into a single css file (app.css in this case)
import '../css/app.scss';
import L from 'leaflet';
import 'leaflet-draw';
const places = require('places.js');
const algoliasearch = require('algoliasearch');
import './ui/wysiwyg';
import './ui/scientific-name';
import {off} from "leaflet/src/dom/DomEvent";

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
    onCloseOverlay();
    switchToNextOnHomepage();
    switchTabs();
    toggleCalendar();
    toggleDateSelection();
    calendarSwitchDate();
    hideCalendarLegend();
    toggleAccodionBlock();
    stationMapDisplay();
});

// open overlay
function onOpenOverlay() {
    $('a.open').off('click').on('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        const $thisLink = $(this);

        if ($thisLink.hasClass('disabled')) {
            window.location.href = window.location.origin+'/user/login';
        } else {
            let dataAttrs = $thisLink.data(),
                $overlay = $('.overlay.'+dataAttrs.open),
                isEdit = $thisLink.hasClass('edit');

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
                $('form', $overlay).get(0).reset();
            }

            if (isEdit) {
                $overlay.addClass('edit')
                    .find('.action-type')
                        .val('edit')
                        .after(
                        '<input class="'+dataAttrs.open+'-id" type="hidden" name="'+dataAttrs.open+'-id" value="'+dataAttrs[dataAttrs.open+'Id']+'">'
                    )
                ;
                $('.show-on-edit',$overlay).attr(
                    'href',
                    '/'+dataAttrs.open+'/'+dataAttrs[dataAttrs.open+'Id']+'/delete'
                );
            }
            $('body').css('overflow', 'hidden');
            switch(dataAttrs.open) {
                case 'obs-infos':
                    onObsInfo($thisLink, dataAttrs);
                    break;
                case 'station':
                    onLocation();
                    toggleMap();
                    onFileEvent();
                    editStationPreSetFields(dataAttrs);
                    break;
                case 'observation':
                    openDetailsField();
                    onFileEvent();
                    onChangeSetIndividual();
                    onChangeObsEvent();
                    onChangeObsDate();
                    observationOvelayManageIndividualAndEvents(dataAttrs);
                    if(isEdit) {
                        $thisLink.closest('.overlay').addClass('hidden');
                    }
                    break;
                case 'individual':
                    individualOvelayManageSpecies(dataAttrs);
                    break;
                default:
                    break;
            }
        }
    });
}

function editStationPreSetFields(dataAttrs) {
    if ($('.overlay.station').hasClass('edit')) {
        if (valOk(dataAttrs.name) && '' !== dataAttrs.name) {
            $('#station_name').val(dataAttrs.name);
        }
        if (valOk(dataAttrs.description) && '' !== dataAttrs.description) {
            $('#station_description').val(dataAttrs.description);
        }
        if (valOk(dataAttrs.latitude) && '' !== dataAttrs.latitude) {
            $('#station_latitude').val(dataAttrs.latitude);
        }
        if (valOk(dataAttrs.longitude) && '' !== dataAttrs.longitude) {
            $('#station_longitude').val(dataAttrs.longitude).trigger('blur');
        }
        if (valOk(dataAttrs.habitat)) {
            $('#station_habitat')
                .find('option[value="'+dataAttrs.habitat+'"]')
                .prop('selected', true).attr('selected', 'selected')
            ;
        }
        if (valOk(dataAttrs.isPrivate)) {
            $('#station_is_private').prop('checked', dataAttrs.isPrivate === 1)
        }
        if (valOk(dataAttrs.headerImage) && '' !== dataAttrs.headerImage) {
            $('.upload-zone-placeholder').addClass('hidden');
            $('img.placeholder-img').addClass('obj').attr('src', dataAttrs.headerImage);
        }
    }
}

function onObsInfo($thisLink, dataAttrs) {
    let $thisCalendar = $thisLink.closest('.periods-calendar'),
        theseObservations = $('.stage-marker[data-year="'+dataAttrs.year+'"][data-stage-name="'+dataAttrs.stageName+'"][data-month="'+dataAttrs.month+'"]:visible', $thisCalendar),
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
        $obsInfo.append(
        '<div class="list-cards-item obs" data-id="'+dataAttrs.obsId+'">'+
            '<a href="'+dataAttrs.pictureUrl+'" class="list-card-img" style="background-image:url('+dataAttrs.pictureUrl+')" target="_blank"></a>'+
            '<div class="item-name-block">'+
                '<div class="item-name">'+dataAttrs.author+'</div>'+
                '<div class="item-name stage">'+dataAttrs.stage+'</div>'+
                '<div class="item-heading-dropdown">'+dataAttrs.date+'</div>'+
            '</div>'+
            '<div class="dual-blocks-container">'+
                '<a href="/observation/'+dataAttrs.obsId+'/delete" class="dual-squared-button delete-icon">'+
                    '<div class="squared-button-label">Supprimer</div>'+
                '</a>'+
                '<a href="" class="dual-squared-button edit-obs edit-list-icon edit open" ' +
                    'data-action-type="edit" ' +
                    'data-open="observation" '+
                    'data-species="'+dataAttrs.speciesId+'" '+
                    'data-species-name="'+dataAttrs.speciesName+'" '+
                    'data-indiv="'+dataAttrs.indivId+'" '+
                    'data-observation-id="'+dataAttrs.obsId+'" '+
                '>'+
                    '<div class="squared-button-label">Éditer</div>'+
                '</a>'+
            '</div>'+
        '</div>'
        );
        onOpenOverlay();
    }

    $('.obs-info.title').text(obsInfoTitle);
}

// close overlay
function onCloseOverlay() {
    $('a.bt-annuler').off('click').on('click', function (event) {
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
    $overlay.addClass('hidden').removeClass('edit');
    if(valOk($('form',$overlay))) {

        $('form',$overlay).get(0).reset();
        $overlay.find('option').removeAttr('hidden');

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
        $('.action-type', $overlay).val('new');
        $('.observation-id, .individual-id, .station-id').remove();
        $('.show-on-edit', $overlay).attr('href','');
    } else if ($overlay.hasClass('obs-infos')) {
        $('.saisie-container').find('.obs-info').text('');
    }
}

function observationOvelayManageIndividualAndEvents(dataAttrs) {
    let availableIndividuals = getDataAttrValuesArray(dataAttrs.indiv.toString()),
        $formTitle = $('.overlay.observation .saisie-title'),
        stationName = $formTitle.data('stationName');

    updateSelectOptions($individual, availableIndividuals);
    $individual.trigger('change');//triggers onChangeSetIndividual()
     // Display species name in title
     if (valOk(dataAttrs.speciesName)) {
         // capitalize species name
         let speciesName = dataAttrs.speciesName.charAt(0).toUpperCase() + dataAttrs.speciesName.slice(1);
         // unescape special chars and display in title
         $formTitle.html(speciesName).text();
     } else {
         $formTitle.html(stationName).text();
     }

    if ($('.overlay.observation').hasClass('edit')) {
        let obsDataAttrs = $('.stage-marker.observation-'+dataAttrs.observationId).data();

        if (valOk(obsDataAttrs.eventId)) {
            $event
                .find('.event-option.event-'+obsDataAttrs.eventId)
                .prop('selected', true).attr('selected', 'selected')
                .prop('hidden', false).removeAttr('hidden')
            ;
        }
        if (valOk(obsDataAttrs.obsDate)) {
            $observationDate.val(obsDataAttrs.obsDate);
        }
        if (valOk(obsDataAttrs.isMissing)) {
            $('#observation_is_missing').prop('checked', obsDataAttrs.isMissing === 1)
        }
        if (valOk(obsDataAttrs.obsPicture) && '' !== obsDataAttrs.obsPicture) {
            $('.upload-zone-placeholder').addClass('hidden');
            $('img.placeholder-img').addClass('obj').attr('src', obsDataAttrs.obsPicture);
        }
        if (valOk(obsDataAttrs.details) && '' !== obsDataAttrs.details) {
            $('#observation_details').val(obsDataAttrs.details);
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

    if ($('.overlay.individual').hasClass('edit')) {
        if (valOk(dataAttrs.name) && '' !== dataAttrs.name) {
            $('#individual_name').val(dataAttrs.name);
        }
        if (valOk(dataAttrs.speciesId)) {
            $('.species-option.species-'+dataAttrs.speciesId)
                .prop('selected', true).attr('selected', 'selected')
                .prop('hidden', false).removeAttr('hidden')
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
        .find('option').removeAttr('hidden selected')
        .closest('form').get(0).reset();

    if(sortOptions) {
        $('.' + selectName + '-option', $selectEl).each(function (i, element) {
            let $element = $(element);

            if (0 <= itemsToMatch.indexOf($element.val().toString())) {
                if (1 === itemsToMatch.length && $element.hasClass(selectName + '-' + itemsToMatch[0])) {
                    $element.prop('selected', true).attr('selected', 'selected');
                }
            } else {
                $element.prop('hidden', true).attr('hidden', 'hidden');
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
            .prop('hidden',true).attr('hidden','hidden')
            .prop('selected', false).removeAttr('selected')
        ;

        if (valOk($selectedIndividual)) {
            let speciesPicture = $selectedIndividual.data('picture'),
                availableEvents = getDataAttrValuesArray($selectedIndividual.data('availableEvents').toString()),
                aberrationsDays = $selectedIndividual.data('aberrationsDays');

            $event.removeAttr('disabled').prop('disabled', false);
            if (1 === availableEvents.length) {
                $event
                    .addClass('disabled')
                    .find('.event-option.event-'+availableEvents[0])
                        .prop('selected', true).attr('selected','selected')
                        .prop('hidden', false).removeAttr('hidden')
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
        if(valOk($(this).val()) && valOk($event.val())) {
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
        message = 'Votre donnée semble anormale, elle ne correspond pas à la moyenne saisonnière (du '+$selectedEvent.data('displayedStartDate')+' au '+$selectedEvent.data('displayedEndDate')+'), si vous êtes sûr(e) de votre observation, ne tenez pas compte de ce message';
    }
    $('.ods-form-warning').toggleClass('hidden', message === '').text(message);
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
        map = mapInfo.map;

        // interactions with map
        map.on('click', function (e) {
            onPosition(e.latlng, marker);
        });
        marker.on('dragend', function (e) {
            onPosition(marker.getLatLng(), e.target);
        });
    } else {
        mapRemove();
    }
    return marker;
}

// Remove the map
function mapRemove() {
    // reset map
    map = L.DomUtil.get('map');

    if(map != null){
        map._leaflet_id = null;
    }
    $('#map').remove();
    $('.map-container').append('<div id="map" class="hidden"></div>');
}

// intit places
function placesInit() {
    placesRemove();
    return places({
        appId: PLACES_CONFIG.appId,
        apiKey: PLACES_CONFIG.apiKey,
        container: '#search',
        language: 'fr',
        countries: ['fr']
    });
}

function placesLocation(marker) {
    //Algolia places configuration
    let placesAutocomplete = placesInit();
    placesAutocomplete.on('change', function (e) {
        onPosition(e.suggestion.latlng, marker);
    });
    // algolia places search
    $locality.on('blur',function () {
        const localitySearch = algoliasearch.initPlaces(PLACES_CONFIG.appId, PLACES_CONFIG.apiKey);
        localitySearch.search({
            query: $(this).val()
        }).then(function (results) {
            let hits = results.hits;
            if (hits[0]) {
                onPosition(hits[0]._geoloc, marker);
            }
        });
    });
}

// remove places
function placesRemove() {
    $('.search-container')
        .empty()
        .append(
            '<label for="search" class="search-label">Localisez votre station</label>' +
            '<input id="search" type="search" placeholder="Entrez une adresse ici">'
        )
    ;
}

// Location events management
function onLocation() {
    let marker = mapLocation();
    // location fields filled
    $('#station_latitude, #station_longitude').on('blur', function() {
        let latitude = parseFloat($latitude.val()),
            longitude = parseFloat($longitude.val());
        if(!isNaN(latitude) && !isNaN(longitude)) {
            onPosition({'lat': latitude,'lng': longitude}, marker);
        }
    });
    placesLocation(marker);
}

// Fills location fields when information is available
function onPosition(position, marker = null) {
    let transmittedLatitude = Number.parseFloat(position.lat).toFixed(4),
        transmittedLongitude = Number.parseFloat(position.lng).toFixed(5),
        $addLoadingClassElements = $('#station_locality,#station_insee_code').siblings('label');
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
            $('#station_insee_code').val(locationInformations.code_insee);
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
    let isAdvancedUpload = function() {
        let div = document.createElement('div');

        return (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) && 'FormData' in window && 'FileReader' in window;
    }();

    if (isAdvancedUpload) {
        $picture
            .on('drag dragstart dragend dragover dragenter dragleave drop', function(event) {
                event.preventDefault();
                event.stopPropagation();
            })
            .on('dragover dragenter', function() {
                $picture.addClass('is-dragover');
            })
            .on('dragleave dragend drop', function() {
                $picture.removeClass('is-dragover');
            })
            .on('drop', function(event) {
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
        $('.is-delete-picture').remove();
        displayThumbs(droppedFiles);
    });

    onDeleteFile()
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
            console.log(ajaxUpload);
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

        $('.upload-zone .upload-input')
            .val('')
            .after(
                '<input type="hidden" class="is-delete-picture" name="is-delete-picture" value="true">'
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
    let $tabsHolder = $('.tabs-holder');
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
    $('.dropdown-link').off('click').on('click', function (event) {
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
            resetTabMatchingElements($('.tabs-holder'));
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
    let $ret = (direction === 'prev') ? $element.prev(targetClass) : $element.next(targetClass);
    let valid = valOk($ret);
    return valid ? $ret : valid;
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
