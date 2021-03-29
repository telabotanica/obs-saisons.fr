import domready from "mf-js/modules/dom/ready";
import {createMap, DEFAULT_POSITION, DEFAULT_ZOOM} from "../create-map";

const retrieveStations = (map, query) => {
    const mapInfo = setMapInfoUrl(query);

    if(mapInfo.url) {
        $.ajax({
            method: "GET",
            url: mapInfo.url,
            success: function (data) {
                const renderer = L.canvas({padding: 0.5}),
                    legend = L.control({ position: "bottomleft" });

                legend.onAdd = function(map) {
                    const div = L.DomUtil.create("div", "legend");
                    let title = 'Toutes les stations';

                    if ('user' === mapInfo.queryType) {
                        title = 'Mes stations';
                    } else if ('search' === mapInfo.queryType) {
                        title = 'Resultats de ma recherche';
                    }
                    div.innerHTML = `<h4>${title}</h4>
                                    <i style="border-color: #3388ff;background-color: rgba(51, 136, 255 ,0.2)"></i><span>Station publique</span><br>
                                    <i style="border-color: #524d4b;background-color: rgba(82, 77, 75, 0.2)"></i><span>Station personnelle*</span><br>
                                    <em>*La position des stations personnelles est volontairement imprécise</em>`
                    ;
                    return div;

                };
                legend.addTo(map);

                map.cluster = L.markerClusterGroup();

                data.forEach(station => {
                    let privateIcon = '',
                        markerColor = '#3388ff',
                        latitude = station.latitude,
                        longitude = station.longitude;

                    if (station.isPrivate) {
                        privateIcon = '<div class="private-icon cadenas-icon"></div>';
                        markerColor = '#524d4b';
                        latitude = Number.parseFloat(station.latitude).toFixed(2);
                        longitude = Number.parseFloat(station.longitude).toFixed(2);
                    }

                    const marker = L.circleMarker([latitude, longitude], {
                            renderer: renderer,
                            color: markerColor
                        }),
                        url = stationUrlTemplate.replace('slugPlaceHolder', station.slug);

                    marker.bindPopup(
                        `<div class="card stations-card-popup">
                        <a href="${url}" class="card-header" style="background-image:url(${station.headerImage ? station.headerImage : '/media/layout/image-placeholder.svg'})">
                            ${privateIcon}
                        </a>
                        <div class="card-body">
                            <a href="${url}">
                                <h4 class="card-heading">${station.name}</h4>
                            </a>
                            <div class="card-detail pointer-icon">${station.locality}</div>
                            <div class="card-detail leaf-icon">${station.habitat}</div>
                        </div>
                    </div>`
                    );
                    map.cluster.addLayer(marker);
                    map.addLayer(map.cluster);
                });
            },
            error: function (error) {
                console.warn(error);
            }
        });
    }
};


const setMapInfoUrl = query => {
    let url = dataRoute;

    if (!!query) {
        url += '?';

        if ('user' === query) {
            return {
                url: url + `${query}=`,
                queryType: 'user'
            };
        }

        try {
            const parsedQuery = JSON.parse(query);

            if (parsedQuery.search) {
                return {
                    url: url + `search=${parsedQuery.search}`,
                    queryType: 'search'
                };
            }
        } catch (error) {
            console.warn(error);
        }
    }

    return {
        url:url,
        queryType: 'station'
    };
};

domready(() => {
    const headerMap = document.getElementById('stations-list-header-map');

    if (headerMap) {
        const map = createMap(
            headerMap.id,
            DEFAULT_POSITION.lat,
            DEFAULT_POSITION.lng,
            5
        );

        retrieveStations(map, headerMap.dataset.stationsQuery);
    }
});
