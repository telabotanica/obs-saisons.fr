import domready from "mf-js/modules/dom/ready";
import {Location, DEFAULT_CITY_ZOOM} from "../location/location";

const stationHeaderMap = new Location();

domready(() => {
    let $headerMap = $('#headerMap');
    if (valOk($headerMap) && $headerMap.hasClass('show-map')) {
        stationHeaderMap.createLocationMap(
            {
                lat: $headerMap.data('latitude'),
                lng: $headerMap.data('longitude'),
            },
            DEFAULT_CITY_ZOOM,
            'headerMap',
            false,
            false,
        );
    }
});
