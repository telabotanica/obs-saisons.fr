import domready from "mf-js/modules/dom/ready";
import {createMap, DEFAULT_POSITION, DEFAULT_ZOOM} from "../create-map";

const retrieveStations = (map, query) => {
    const url = setMapInfoUrl(query);

    if(url) {
        $.ajax({
            method: "GET",
            url: url,
            success: function (data) {
                const renderer = L.canvas({padding: 0.5});

                map.cluster = L.markerClusterGroup();

                data.forEach(station => {
                    const marker = L.circleMarker([station.latitude, station.longitude], {
                            renderer: renderer,
                            color: '#3388ff'
                        }),
                        url = stationUrlTemplate.replace('slugPlaceHolder', station.slug);

                    marker.bindPopup(
                        `<div class="card stations-card-popup">
                        <a href="${url}" class="card-header" style="background-image:url(${station.headerImage ? station.headerImage : '/media/layout/image-placeholder.svg'})">
                            ${station.isPrivate ? '<div class="private-icon cadenas-icon"></div>': ''}
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
            return url + `${query}=`;
        }

        try {
            const parsedQuery = JSON.parse(query);

            if (parsedQuery.search) {
                return url + `search=${parsedQuery.search}`;
            }
        } catch (error) {
            console.warn(error);
        }
    }

    return url;
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
