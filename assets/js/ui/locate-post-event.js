import domready from 'mf-js/modules/dom/ready';

/**************************************************
 * LOCALITY
 **************************************************/
import {OdsPlaces} from "./location/locality";

/**************************************************
 * DOM ELEMENTS
 **************************************************/
const $eventLocation = $('#event_post_location');
/***************************************************/

domready(function() {
    if(0 < $eventLocation.length) {
        let odsPlaces = new OdsPlaces(localityData =>
            $eventLocation.val(localityData['display_name'])
        );
        odsPlaces.init();
    }
});
