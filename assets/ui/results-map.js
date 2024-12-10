import { createMap } from "./create-map";
var load =false;
$( function() {
    const $map = $('#results-map');

    if ( $map.length > 0 ) {
        var map = createMap( 'results-map' );
        filterCriteria(map);
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
            filterCriteria(map,load);
            // retrieve obs
            
        } );

        $( '#myRange' ).on( 'change', function() {
            // filter species
            filterCriteria(map,load);
            // retrieve obs
            
        } );
        $( '#cumul' ).on( 'change', function() {
            // filter species
            filterCriteria(map,load);
            // retrieve obs
            
        } );
        // initiate map
        $( '#year' ).delay( 1000 ).on('change');
    }
} );

function retrieveObs( criteria, map ) {
    var url = dataRoute+"?year="+criteria.year+"&month="+criteria.month+"&typeSpecies="+criteria.typeSpeciesId+"&species="+criteria.speciesId+"&event="+criteria.eventId+"&department="+criteria.department+"&region="+criteria.region+"&cumul="+criteria.cumul;
    $.ajax({
        method: "GET",
        url: url,
        success: function ( data ) {
            
            if (!map.cluster) {
                map.cluster = L.markerClusterGroup();  
            }

            // Vider les layers du cluster si map.cluster est initialisé
            map.cluster.clearLayers();

            // Retirer la couche de cluster de la carte
            if (map.hasLayer(map.cluster)) {
                map.removeLayer(map.cluster);
            }

            // Réinitialiser les markers
            map.markers = [];

            // Ajouter le groupe de clusters à la carte à nouveau
            map.addLayer(map.cluster);

            // Créer des clusters et des markers avec les nouvelles données
            const renderer = L.canvas({ padding: 0.5 });

            data.data.forEach(obs => {
                if (!obs.isMissing) {
                    console.log(obs.individual.station);
                    // Créer un nouveau marker
                    let marker = L.circleMarker([obs.individual.station.town_lat, obs.individual.station.town_lon], {
                        renderer: renderer,
                        color: '#3388ff'
                    });

                    const url = stationUrlTemplate.replace('slugPlaceHolder', obs.individual.station.slug);
                    marker.bindPopup(
                        `<b>${obs.individual.species.displayName}</b><br>
                        ${obs.event.displayName}<br> ${obs.date.displayDate} <br>
                        <a href="${url}" target="_blank">${obs.individual.station.locality} (${obs.individual.station.habitat})</a>`
                    );

                    // Ajouter le marker au cluster
                    map.cluster.addLayer(marker);

                    // Ajouter le cluster à la carte (si nécessaire)
                    map.addLayer(map.cluster);

                    // Garder une référence des markers
                    map.markers.push(marker);
                }
            });

            
        }
    });
}

function filterCriteria(map) {
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

    var slider = document.getElementById("myRange");
    var months = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre","Tous les mois"];
    document.getElementById("demo").innerHTML = months[slider.value];
   
    slider.oninput = function() {
        document.getElementById("demo").innerHTML = months[slider.value];
        switch(document.getElementById("demo").innerHTML){
            case "Janvier":
                $('#myRange').val("0");
                break;
            case "Février":
                $('#myRange').val("1");
                break;
            case "Mars":
                $('#myRange').val("2");
                break;
            case "Avril":
                $('#myRange').val("3");
                break;
            case "Mai":
                $('#myRange').val("4");
                break;
            case "Juin":
                $('#myRange').val("5");
                break;
            case "Juillet":
                $('#myRange').val("6");
                break;
            case "Août":
                $('#myRange').val("7");
                break;
            case "Septembre":
                $('#myRange').val("8");
                break;
            case "Octobre":
                $('#myRange').val("9");
                break;
            case "Novembre":
                $('#myRange').val("10");
                break;
            case "Décembre":
                $('#myRange').val("11");
                break;
            case "Tous les mois":
                $('#myRange').val("12");
                break;
        }
    }
    if ($('#cumul').is(':checked')){
        $('#cumul').val(1);
    }else{
        $('#cumul').val(0);
    }
    const criteria = {
        'typeSpeciesId': typeSpeciesId,
        'speciesId': speciesId,
        'eventId': $( '#events > option:selected' ).val(),
        'year': $( '#year' ).val(),
        'month' : parseInt($('#myRange').val())+1,
        'region': $( '#region' ).val(),
        'department': $( '#department' ).val(),
        'cumul': $('#cumul').val()
    };
    retrieveObs( criteria, map );
    
}
