import domready from "mf-js/modules/dom/ready";
import {createMap} from "../create-map";

const retrieveStations = (map, query) => {
    let url = dataRoute;
    console.log(query);

    if (!!query && 'object' == typeof JSON.parse(query)) {
        url += '?';
        for (const [key, value] of Object.entries(JSON.parse(query))) {
            url += `${key}=${value}`;
        }
    }

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
        }
    });
};

domready(() => {
    const headerMap = document.getElementById('stations-list-header-map');

    if (headerMap) {
        retrieveStations(
            createMap(headerMap.id),
            headerMap.dataset.stationsQuery
        );
    }
});
