import domready from "mf-js/modules/dom/ready";
import {Location, DEFAULT_CITY_ZOOM} from "../location/location";

domready(() => {
    const stationHeaderMap = new Location(),
        headerMap = document.getElementById('station-single-header-map');

    if (headerMap && headerMap.classList.contains('show-map')) {
        const lat = headerMap.dataset.approxlocation ? Math.round(headerMap.dataset.latitude * 10000) / 10000 : headerMap.dataset.latitude;
        const lng = headerMap.dataset.approxlocation ? Math.round(headerMap.dataset.longitude * 10000) / 10000 : headerMap.dataset.longitude;

        stationHeaderMap.createLocationMap(
            {
                lat: lat,
                lng: lng,
            },
            DEFAULT_CITY_ZOOM,
            'station-single-header-map',
            false,
            false,
        );
    }
});
