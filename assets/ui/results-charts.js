$(function() {
    const charts = Array.from(document.getElementsByClassName('chart'));
   
    if (charts.length > 0) {
        import('plotly.js-dist' ).then(({default: Plotly}) => {
            
            // choose both region and department is not allowed
            $('#region-phenological-chart').on('change', function() {
                $('#department-phenological-chart').val(0);
            });
            $('#department-phenological-chart').on('change',function() {
                $('#region-phenological-chart').val(0);
            });
            $('#region-evolution-chart').on('change', function() {
                $('#department-evolution-chart').val(0);
            });
            $('#department-evolution-chart').on('change', function() {
                $('#region-evolution-chart').val(0);
            });

            // binding all select with obs retrieval
            $( '#phenological-chart-container > select' ).on( 'change', async function() {
                
                getInfoforFirstChart(Plotly);
                
            } );

            $( '#evolution-chart-container > select' ).on( 'change', function() {
                filterCriteria();

                const criteria = {
                    species: {
                        id: $( '#species-evolution-chart' ).val(),
                        name: $( '#species-evolution-chart option:selected' ).text()
                    },
                    event: {
                        id: $( '#event-evolution-chart' ).val(),
                        name: $( '#event-evolution-chart option:selected' ).text()
                    },
                    region: {
                        id: $( '#region-evolution-chart' ).val(),
                        name: $( '#region-evolution-chart option:selected' ).text()
                    },
                    department: {
                        id: $( '#department-evolution-chart' ).val(),
                        name: $( '#department-evolution-chart option:selected' ).text()
                    }
                };

                retrieveData(`${eventsEvolutionRoute}?species=${criteria.species.id}&event=${criteria.event.id}&region=${criteria.region.id}&department=${criteria.department.id}`,
                    ( data ) => {
                    if ( data.length ) {
                        displayEvolutionChart(
                            Plotly,
                            $( '#evolution-chart-container > .chart' )[0],
                            criteria,
                            indexObsForEvolutionChart( data )
                        );
                        $( '#evolution-chart-container > .no-data' ).hide();
                    } else {
                        $( '#evolution-chart-container > .no-data' ).show();
                    }
                } );
            } );

            // display charts
            filterCriteria();
            $( '#event-evolution-chart' ).on('change');
            $( '#year-phenological-chart' ).on('change');
            getInfoforFirstChart(Plotly);
        });
    }
} );

function unpack( rows, key ) {
    return rows.map( row => { return row[key]; } );
}

function displayEvolutionChart( Plotly, chart, criteria, allObs ) {
    const data = [];
    const obsArray = Object.entries( allObs );
    for ( const [bbch, obs] of obsArray ) {
        data.push( {
            type: "scatter",
            mode: 'lines+markers',
            name: bbch,
            x: unpack(obs, 'year'),
            y: unpack(obs, 'day'),
            text: unpack(obs, 'displayDate'),
            hoverinfo: 'text'
        } );
    }
    
    const multipleEvents = ( obsArray.length > 1 ) ? 'début et de pleine ' : '';
    const title = `Dates moyennes de ${multipleEvents}${criteria.event.name} pour l’espèce ${criteria.species.name} <br> ${locality( criteria )}`;

    let years = [];
    for ( let i = $( '#evolution-chart-container' ).data( 'minYear' ); i <= new Date().getFullYear(); i++ ) {
        years.push( i );
    }

    const layout = {
        title: title,
        xaxis: {
            title: 'Années',
            showgrid: false,
            zeroline: false,
            tickvals: years,
            ticktext: years,
            autoticks: false,
        },
        yaxis: {
            title: '',
            autoticks: false,
            tickvals: [15,45,75,105,135,165,195,225,255,285,315,345],
            ticktext: ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'],
        },
        // display legend at the bottom to save space
        legend:{
            xanchor:"center",
            yanchor:"top",
            y:-0.3,
            x:0.5
        }
    };

    Plotly.newPlot( chart, data, layout );
}

function retrieveData( url, handleData, previousPageData = [] ) {
    $.ajax({
        method: "GET",
        url: url,
        success: data => {
            if ( data.data ) {
                if (data.links.next) {
                    retrieveData(data.links.next, handleData, previousPageData.concat(data.data));
                    
                } else {
                    handleData( data.data );
                    
                }
            } else {
                handleData( data );
                
            }
            
        }
    });
    
}

function locality( criteria ) {
    let locality = 'toute la France métropolitaine';

    if ( '0' !== criteria.region.id ) {
        locality = `région : ${criteria.region.name}`;
    }
    if ( '0' !== criteria.department.id ) {
        locality = `département : ${criteria.department.name}`;
    }

    return locality;
}

function indexObsForEvolutionChart( allObs ) {
    const obsIndexedByEvent = {};
    allObs.forEach( obs => {
        if (!obsIndexedByEvent[obs.event]) {
            obsIndexedByEvent[obs.event] = [];
        }
        const date = new Date( obs.date );
        obsIndexedByEvent[obs.event].push( {
            day: obs.dayOfYear,
            year: obs.year,
            displayDate: '' + date.getDate() + '/' + (date.getMonth() + 1) + '/' + date.getFullYear()
        } );
    });

    return obsIndexedByEvent;
}

function filterCriteria() {
    const $selectedSpecies = $( '#species-evolution-chart > option:selected' );
    const speciesId = $selectedSpecies.val();

    // show only corresponding events (filters events)
    if ( speciesId > 0 ) {
        const eventsIds = ( ''+$selectedSpecies.data( 'eventsIds' ) ).split( ', ' );
        $( '#event-evolution-chart option:not(.default-criteria)' ).each( function() {
            if ( -1 !== eventsIds.indexOf( $( this ).val().split(',')[0] ) ) {
                $( this ).attr( 'hidden', false ).attr( 'disabled', false );
                // if only one event, select it
                if ( 1 === eventsIds.length ) $( this ).attr( 'selected', true );
            } else {
                $( this ).attr( 'hidden', true ).attr( 'disabled', true ).attr( 'selected', false );
            }
        })
    }
}

function getInfoforFirstChart(Plotly){
    var year = $("#year-phenological-chart").val();
    var region = $("#region-phenological-chart").val();
    var species = $("#species-phenological-chart").val();
    var dpt = $("#department-phenological-chart").val();
    var data_sent='{"year":'+year+',"region":'+region+',"specy":'+species+',"dpt":"'+dpt+'"}';
    $.ajax({
        method: "POST",
        url: obsRoute,
        data:data_sent,
        success: function(response) {
            var results = response.results;
            const txt_obs = "Nombre d'observations utilisées dans le graphique : ";
            if(results.length>0){

                const nb_obs_total = results[0].nb_obs_total.toString();
                $('#nb_obs').html(txt_obs+nb_obs_total);
                const moisNoms = {
                    "1": "Janvier", "2": "Février", "3": "Mars", "4": "Avril", "5": "Mai", "6": "Juin", 
                    "7": "Juillet", "8": "Août", "9": "Septembre", "10": "Octobre", "11": "Novembre", "12": "Décembre"
                };
                // Regroupement des données par mois et par étape
                const mois = Array.from(new Set(results.map(item => item.mois))); // Récupère les mois distincts
                const etapes = ["1ère apparition","feuillaison", "floraison", "fructification", "sénescence"];
                const moisNom = mois.map(month => moisNoms[month]);
                const couleurs = {
                    "feuillaison": "#bcd35f",  
                    "floraison": "#ed7c1c",  
                    "fructification": "#5fbcd3",  
                    "sénescence": "#bb381c",
                    "1ère apparition":"#4d4d4dff"  
                };
                
                // Créer une trace pour chaque étape
                const traces = etapes.map(etape => {
                    return {
                        x: moisNom,
                        y: mois.map(mois_ => {
                            const entry = results.find(d => d.mois === mois_ && d.etape === etape);
                            return entry ? entry.nb_obs : 0;
                        }),
                        name: `Observations - ${etape}`,
                        type: 'bar',
                        barmode: 'group',
                        marker: { color: couleurs[etape] }
                    };
                });
                
    
                const layout = {
                    title: 'Nombre d\'observations par mois et étape',
                    xaxis: { title: 'Mois' },
                    yaxis: { title: 'Nombre d\'observations' },
                    barmode: 'group' 
                };
    
                Plotly.newPlot('chart', [...traces], layout);
                $('#alerte_pheno').hide();
                $('#chart').show();
            }else{
                $('#nb_obs').html(txt_obs + "0");
                $('#alerte_pheno').show();
                $('#chart').hide();
            }
            
        }
    });
}