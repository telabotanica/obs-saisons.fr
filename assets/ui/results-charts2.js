$( function(){
    const chart2 = document.getElementById('chart2');
    if (chart2) {
        import('plotly.js-dist' ).then(({default: Plotly}) => {

            // choose both region and department is not allowed
            $('#region-phenological-chart2').on('change', function() {
                $('#department-phenological-chart2').val(0);
            });
            $('#department-phenological-chart2').on('change',function() {
                $('#region-phenological-chart2').val(0);
            });

            // binding all select with obs retrieval
            $( '#phenological-chart2-container > select' ).on( 'change', function() {
                getInfoForMustacheBox(Plotly);
            } );

            $( '#year-phenological-chart2' ).on('change');
            getInfoForMustacheBox(Plotly);
        });
    }
} );

function unpack2( rows, key ) {
    return rows.map( row => { return row[key]; } );
}

function getInfoForMustacheBox(Plotly){
    const criteria = {
        year: $( '#year-phenological-chart2' ).val(),
        species: {
            id: $( '#species-phenological-chart2' ).val(),
            name: $( '#species-phenological-chart2 option:selected' ).text()
        },
        region: {
            id: $( '#region-phenological-chart2' ).val(),
            name: $( '#region-phenological-chart2 option:selected' ).text()
        },
        department: {
            id: $( '#department-phenological-chart2' ).val(),
            name: $( '#department-phenological-chart2 option:selected' ).text()
        }
    };

    retrieveData2(
    `${exportRoute}?year=${criteria.year}&species=${criteria.species.id}&region=${criteria.region.id}&department=${criteria.department.id}`,
        ( data ) => {
        if ( data.length ) {
            displayPhenologicalChart2(
                Plotly,
                chart2,
                criteria,
                indexObsForPhenologicalChart2( data )
            );
            $( '#phenological-chart2-container > .no-data' ).hide();
        } else {
            $( '#phenological-chart2-container > .no-data' ).show();
        }
    } );
}
function displayPhenologicalChart2( Plotly, chart, criteria, allObs ) {
    const data = [];
    for ( const [event, obs] of Object.entries( allObs ) ) {
        data.push( {
            type: 'violin',
            x: unpack2( obs, 'day' ),
            text: unpack2( obs, 'displayDate' ),
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
    const subtitle = ( criteria.year === "0" ) ? 'Toutes les années' : `Année ${criteria.year}`;
    const layout = {
        title: `Calendrier phénologique de l’espèce ${criteria.species.name} <br> ${subtitle}, ${locality2( criteria )}`,
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

function retrieveData2( url, handleData, previousPageData = [] ) {
    $.ajax({
        method: "GET",
        url: url,
        success: data => {
            if ( data.data ) {
                if (data.links.next) {
                    retrieveData2(data.links.next, handleData, previousPageData.concat(data.data));
                    
                } else {
                    handleData( data.data );
                    
                }
            } else {
                handleData( data );
                
            }
            
        }
    });
    
}

function indexObsForPhenologicalChart2( allObs ) {
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

function locality2( criteria ) {
    let locality = 'toute la France métropolitaine';

    if ( '0' !== criteria.region.id ) {
        locality = `région : ${criteria.region.name}`;
    }
    if ( '0' !== criteria.department.id ) {
        locality = `département : ${criteria.department.name}`;
    }

    return locality;
}

function indexObsForEvolutionChart2( allObs ) {
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

