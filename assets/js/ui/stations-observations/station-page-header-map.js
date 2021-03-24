import domready from "mf-js/modules/dom/ready";
import {Location, DEFAULT_CITY_ZOOM} from "../location/location";

domready(() => {
    const stationHeaderMap = new Location(),
        headerMap = document.getElementById('station-single-header-map');

    if (headerMap && headerMap.classList.contains('show-map')) {
        stationHeaderMap.createLocationMap(
            {
                lat: headerMap.dataset.latitude,
                lng: headerMap.dataset.longitude,
            },
            DEFAULT_CITY_ZOOM,
            'station-single-header-map',
            false,
            false,
        );
    }
});
