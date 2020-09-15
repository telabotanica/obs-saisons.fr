import { createMap } from "./create-map";

$( document ).ready( () => {
    const $map = $('#results-map');

    if ( $map.length > 0 ) {
        let map = createMap( 'results-map' );
        map.cluster = [];

        // choose both region and department is not allowed
        $( '#region' ).on( 'change', function() {
            $( '#department' ).val( 0 );
        } );
        $( '#department' ).on( 'change', function() {
            $( '#region' ).val( 0 );
        } );

        // binding all select with obs retrieval
        $( '.criteria-container > select' ).on( 'change', function() {
            // filter species
            const criteria = filterCriteria();
            // retrieve obs
            retrieveObs( criteria, map );
        } );

        // initiate map
        $( '#year' ).delay( 1000 ).change();
    }
} );

function retrieveObs( criteria, map ) {
    $.ajax({
        method: "GET",
        url: `${dataRoute}?year=${criteria.year}&typeSpecies=${criteria.typeSpeciesId}&species=${criteria.speciesId}&event=${criteria.eventId}&department=${criteria.department}&region=${criteria.region}`,
        success: function ( data ) {
            // clear map before display new data
            for ( let i = 0; i < map.markers.length; i++ ){
                map.removeLayer( map.markers[i] );
            }
            map.removeLayer( map.cluster );

            // create clusters and markers
            const renderer = L.canvas( { padding: 0.5 } );
            map.cluster = L.markerClusterGroup();
            data.forEach( obs => {
                if ( !obs.isMissing ) {
                    let marker = L.circleMarker( [ obs.individual.station.lat, obs.individual.station.lon ], {
                        renderer: renderer,
                        color: '#3388ff'
                    } );
                    const url = stationUrlTemplate.replace('slugPlaceHolder', obs.individual.station.slug);
                    marker.bindPopup(
                        `<b>${obs.individual.species.displayName}</b><br>
                        ${obs.event.displayName}<br> ${obs.date.displayDate} <br>
                        <a href="${url}" target="_blank">${obs.individual.station.locality} (${obs.individual.station.habitat})</a>` );
                    map.cluster.addLayer( marker );
                    map.addLayer( map.cluster );

                    // keep reference of markers
                    map.markers.push( marker );
                }
            });
        }
    });
}

function filterCriteria() {
    const typeSpeciesId = $( '#type-species > option:selected' ).val();

    // empty species and empty events if typeSpecies changes
    if ( typeSpeciesId !== $( '#species' ).attr( 'data-selected-type-species' ) ) {
        // $('#species option.default-criteria').attr( 'disabled', false).attr( 'selected', true).attr('disabled', true);
        $(' #species ').val( 0 ).attr( 'data-selected-type-species', typeSpeciesId );
        $(' #events ').val( 0 );
        $(' #events option:not(.default-criteria)' ).each( function () {
            $( this ).attr( 'hidden', true ).attr( 'disabled', true );
        });
    }

    // show all events if no typeSpecies selected
    if ( '0' === typeSpeciesId ) {
        $( '#events option:not(.default-criteria)' ).each( function () {
            $( this ).attr( 'hidden', false ).attr( 'disabled', false );
        });
    }

    const $selectedSpecies = $( '#species > option:selected' );
    const speciesId = $selectedSpecies.val();

    // show only corresponding species (filters species)
    $( '#species option:not(.default-criteria)' ).each( function () {
        // use .val() bc .attr() doesn't work
        if ( typeSpeciesId === $( this ).attr( 'data-type-species-id' ) ) {
            $( this ).attr( 'hidden', false ).attr( 'disabled', false );
        } else {
            $( this ).attr( 'hidden', true ).attr( 'disabled', true );
        }
    });

    // hide events if...
    if (typeSpeciesId > 0 && !speciesId > 0) {
        $( '#events option:not(.default-criteria)' ).each( function () {
            $( this ).attr( 'hidden', true ).attr( 'disable', true );
        })
    }

    // show only corresponding events (filters events)
    if (speciesId > 0) {
        const eventsIds = ( ''+$selectedSpecies.data( 'eventsIds' ) ).split(', ');
        $( '#events option:not(.default-criteria)' ).each( function () {
            if ( -1 !== eventsIds.indexOf($( this ).val() ) ) {
                $( this ).attr( 'hidden', false ).attr( 'disabled', false );
            } else {
                $( this ).attr( 'hidden', true ).attr( 'disabled', true );
            }
        })
    }

    return {
        'typeSpeciesId': typeSpeciesId,
        'speciesId': speciesId,
        'eventId': $( '#events > option:selected' ).val(),
        'year': $( '#year' ).val(),
        'region': $( '#region' ).val(),
        'department': $( '#department' ).val(),
    }
}
