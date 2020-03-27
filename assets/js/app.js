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
});

// open overlay
function onOpenOverlay() {
    $('a.open').off('click').on('click', function (event) {
        event.preventDefault();
        event.stopPropagation();

        if ($(this).hasClass('disabled')) {
            window.location.href = window.location.origin+'/user/login';
        } else {
            let dataAttrs = $(this).data();

            $('.overlay.'+dataAttrs.open)
                .removeClass('hidden')
                .find('form').get(0).reset()
            ;
            $('body').css('overflow', 'hidden');
            switch(dataAttrs.open) {
                case 'station':
                    onLocation();
                    onFileEvent();
                    break;
                case 'observation':
                    openDetailsField();
                    onFileEvent();
                    onChangeSetIndividual();
                    onChangeObsEventUpdateHelpInfos();
                    observationOvelayManageIndividualAndEvents(dataAttrs);
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
    $overlay
        .addClass('hidden')
        .find('form').get(0).reset()
    ;
    $overlay.find('option').removeAttr('hidden');
    if ($overlay.hasClass('observation')) {
        observationOvelayManageIndividualAndEvents($('.open-observation-form-all-station').data());
    } else if ($overlay.hasClass('individual')) {
        individualOvelayManageSpecies($('.open-individual-form-all-station').data().species.toString(), true);
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
            let eventPicturesConnections = $selectedIndividual.data('eventPictures');

            $event.removeAttr('disabled').prop('disabled', false);
            if (1 === eventPicturesConnections.length) {
                $event
                    .addClass('disabled')
                    .find('.event-option.event-' + eventPicturesConnections[0].eventId)
                    .prop('selected', true).attr('selected','selected')
                    .prop('hidden', false).removeAttr('hidden')
                    .data('picture', eventPicturesConnections[0].picture)
                ;
            } else {
                $event.removeClass('disabled');
                for (let i = 0; i < eventPicturesConnections.length; i++) {
                    $('.event-option.event-' + eventPicturesConnections[i].eventId, $event)
                        .prop('hidden', false).removeAttr('hidden')
                        .data('picture', eventPicturesConnections[i].picture)
                    ;
                }
            }
        } else {
            $event.addClass('disabled').attr('disabled', 'disabled').prop('disabled', true);
        }
        $event.trigger('change');// triggers onChangeObsEventUpdateHelpInfos()
    });
}

function onChangeObsEventUpdateHelpInfos() {
    $event.on('change', function () {
        let $saisieAide = $('.saisie-aide.event');

        $('img', $saisieAide).remove();

        if (valOk($(this).val())) {
            let $selectedEvent = $('.event-option:selected', this),
                eventStade = $selectedEvent.text();

            $saisieAide.removeClass('hidden').prepend(
                '<img src="'+$selectedEvent.data('picture')+'" alt="'+eventStade+'" width="80" height="80">'
            );
            $('.text-aide-1.event').text(eventStade);
            $('.text-aide-2.event').text($selectedEvent.data('description'));
        } else {
            $saisieAide.addClass('hidden');
        }
    });
}

// Location events management
function onLocation() {
    //Algolia places configuration
    const placesAutocomplete = places({
        appId: 'plV00W9UJC60',
        apiKey: 'b8630d75d81f1343304ac3547a2994af',
        container: '#search',
        language: 'fr',
        countries: ['fr']
    });
    //map configuration
    const MARKER_ICON = L.Icon.extend({
        options: {
            shadowUrl: '/media/map/marker-shadow.png',
            iconUrl: '/media/map/marker-icon.png',
            iconSize: [24,40],
            iconAnchor: [12,40]//correctly replaces the dot of the pointer
        }
    });
    //toggleMap();
    // Create the map
    map = L.map('map').setView([46.7111, 1.7191], 6);
    // Set up the OSM layer
    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: 'Data © <a href="http://osm.org/copyright">OpenStreetMap</a>',
            maxZoom: 18
        }).addTo(map)
    ;
    // Initialise the FeatureGroup to store editable layers
    map.addLayer(new L.FeatureGroup());
    let position =
        {'lat':46.7111,'lng':1.7191},
        marker = new L.Marker(
            position, {
                draggable: true,
                icon: new MARKER_ICON()
            }
        )
    ;
    map.addLayer(marker);

    // interactions with map
    map.on('click', function (e) {
        onPosition(marker, e.latlng);
    });
    marker.on('dragend', function(e){
        onPosition(e.target, marker.getLatLng());
    });
    // location fields filled
    $('#station_latitude, #station_longitude').on('blur', function() {
        let latitude = parseFloat($latitude.val()),
            longitude = parseFloat($longitude.val());
        if(!isNaN(latitude) && !isNaN(longitude)) {
            onPosition(marker, {'lat': latitude,'lng': longitude});
        }
    });
    // algolia places search
    $locality.on('blur',function () {
        const localitySearch = algoliasearch.initPlaces('plV00W9UJC60', 'b8630d75d81f1343304ac3547a2994af');
        localitySearch.search({
            query: $(this).val()
        }).then(function (results) {
            let hits = results.hits;
            if (hits[0]) {
                onPosition(marker, hits[0]._geoloc);
            }
        });
    });
    placesAutocomplete.on('change', function (e) {
        onPosition(marker, e.suggestion.latlng);
    });
}

// Fills location fields when information is available
function onPosition(marker, position) {
    let transmittedLatitude = Number.parseFloat(position.lat).toFixed(4),
        transmittedLongitude = Number.parseFloat(position.lng).toFixed(5),
        $addLoadingClassElements = $('#station_locality,#station_insee_code').siblings('label');
    //updates coordinates fields
    $latitude.val(transmittedLatitude);
    $longitude.val(transmittedLongitude);
    // updates map
    map.panTo(new L.LatLng(position.lat, position.lng));
    marker.setLatLng(new L.LatLng(position.lat, position.lng),{draggable:'true'});
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
    $('.open-map-button').on('click', function (e) {
        e.preventDefault();
        $(this).find('span').toggleClass('hidden');
        $('#map').toggleClass('hidden');
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
                droppedFiles = event.originalEvent.dataTransfer.files;
                displayThumbs(droppedFiles);
            })
        ;

    }
    $picture.on('change', function (event) {
        droppedFiles = event.target.files;
        displayThumbs(droppedFiles);
    });
}

function displayThumbs(files) {
    let uploadZonePlaceholder = $('.upload-zone-placeholder'),
        $picture = $('.upload-zone .upload-input');

    if (!files) {
        uploadZonePlaceholder.removeClass('hidden').text('L’image n’a pas pu être téléchargée.');
    }

    let file = files[0];
    const imageType = /^image\//;

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
    let $tabsHolder = $('.tabs-holder'),
        activeTab = $tabsHolder.data('active');

    if(activeTab !== 'all') {
        $('[data-tab]:not(.tab)').each(function () {
            if(activeTab !== $(this).data('tab')) {
                $(this).hide();
            }
        });
    }

    $('.tab').off('click').on('click', function (event) {
        event.preventDefault();

        activeTab = $(this).data('tab');
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
        $('.stade-marker', $thisCalendar).each( function () {
            let $element = $(this);
            if(observationsToggleCombinedConditions($element, activeDate)) {
                $element.show(200);
            } else {
                $element.hide(200);
            }
        });
    });
}

function observationsToggleCombinedConditions ($element, activeDate, matchsTab = null) {
    if(null !== matchsTab) {// if matchsTab is defined it is boolean
        let activeTab = $('.tabs-holder').data('active');

        matchsTab = ('all' === activeTab || $element.data('tab') === activeTab);
    }
    return ($element.data('year').toString() === activeDate && matchsTab );
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
