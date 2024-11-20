$( document ).ready( () => {
    const charts = Array.from(document.getElementsByClassName('chart'));
    console.log(charts);
    if (charts.length > 0) {
        import(/* webpackChunkName: "plotly" */ 'plotly.js-dist' ).then(({default: Plotly}) => {

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
            $( '#phenological-chart-container > select' ).on( 'change', function() {
                const criteria = {
                    year: $( '#year-phenological-chart' ).val(),
                    species: {
                        id: $( '#species-phenological-chart' ).val(),
                        name: $( '#species-phenological-chart option:selected' ).text()
                    },
                    region: {
                        id: $( '#region-phenological-chart' ).val(),
                        name: $( '#region-phenological-chart option:selected' ).text()
                    },
                    department: {
                        id: $( '#department-phenological-chart' ).val(),
                        name: $( '#department-phenological-chart option:selected' ).text()
                    }
                };

                retrieveData(
                `${exportRoute}?year=${criteria.year}&species=${criteria.species.id}&region=${criteria.region.id}&department=${criteria.department.id}`,
                    ( data ) => {
                    if ( data.length ) {
                        displayPhenologicalChart(
                            Plotly,
                            $( '#phenological-chart-container > .chart' )[0],
                            criteria,
                            indexObsForPhenologicalChart( data )
                        );
                        $( '#phenological-chart-container > .no-data' ).hide();
                    } else {
                        $( '#phenological-chart-container > .no-data' ).show();
                    }
                } );
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
            $( '#event-evolution-chart' ).change();
            $( '#year-phenological-chart' ).change();
        });
    }
} );

function unpack( rows, key ) {
    return rows.map( row => { return row[key]; } );
}

function displayPhenologicalChart( Plotly, chart, criteria, allObs ) {
    const data = [];
    for ( const [event, obs] of Object.entries( allObs ) ) {
        data.push( {
            type: 'violin',
            x: unpack( obs, 'day' ),
            text: unpack( obs, 'displayDate' ),
            points: 'outliers',
            box: {
                visible: true
            },
            boxpoints: false,
            line: {
                color: 'black'
            },
            fillcolor: '#8dd3c7',
            opacity: 0.6,
            meanline: {
                visible: true
            },
            y0: event,
            name: event,
            legendgroup: event,
            scalegroup: event
        } );
    }
    console.log(data);
    const subtitle = ( criteria.year === "0" ) ? 'Toutes les années' : `Année ${criteria.year}`;
    const layout = {
        title: `Calendrier phénologique de l’espèce ${criteria.species.name} <br> ${subtitle}, ${locality( criteria )}`,
        xaxis: {
            zeroline: false,
            tickvals: [15,45,75,105,135,165,195,225,255,285,315,345],
            ticktext: ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'],
        },
        violingap: 0,
        violingroupgap: 0,
        violinmode: "overlay",
        height: 650,
        showlegend: false,
    };

    Plotly.newPlot( chart, data, layout );
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
    console.log(data.length);
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

function indexObsForPhenologicalChart( allObs ) {
    const obsIndexedByEvent = {};
    allObs.forEach( obs => {
        if (!obsIndexedByEvent[obs.event.name]) {
            obsIndexedByEvent[obs.event.name] = [];
        }
        obsIndexedByEvent[obs.event.name].push( {
            day: obs.date.dayOfYear,
            displayDate: obs.date.displayDate
        } );
    });

    return obsIndexedByEvent;
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
