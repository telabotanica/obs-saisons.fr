import domready from "mf-js/modules/dom/ready";
import {createMap} from "../create-map";

domready(() => {
    const headerMap = document.getElementById('stations-list-header-map');

    if (headerMap) {
        const map = createMap('stations-list-header-map'),
            stations = JSON.parse(headerMap.dataset.stations);

        map.cluster = [];// clear map before display new data

        // clear map before display new data
        for ( let i = 0; i < map.markers.length; i++ ) {
            map.removeLayer( map.markers[i] );
        }

        // create clusters and markers
        const renderer = L.canvas({padding: 0.5});

        map.cluster = L.markerClusterGroup();

        stations.forEach(station => {
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

            // keep reference of markers
            map.markers.push(marker);
        });
    }
});
