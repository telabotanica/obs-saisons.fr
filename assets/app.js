/* *** *
 * SCSS *
 * *** */
import './styles/app.scss';
/* ********* *
 * POLYFILLS *
 * ********* */
import 'core-js/features/object/assign';
import 'core-js/features/object/values';
import 'core-js/features/array/from';
import 'core-js/features/array/for-each';
import 'core-js/features/promise';
/* ****** *
 * JQUERY *
 * ****** */
export const $ = require('jquery');
global.$ = global.jQuery = $;
require('bootstrap');
/* ******* *
 * GLOBALS *
 * ******* */
import './lib/html-entities-decode';
import './ui/error-display';
import './ui/notice-cookies';
/* ********** *
 * COMPONENTS *
 * ********** */
import './ui/mod-touch';
import './ui/hide-flash-messages';
import './ui/toggle-menu-small-device';
import './ui/wysiwyg';
import './ui/textarea-auto-resize';
import './ui/oembed-to-iframe';
import './ui/switch-tabs';
import './ui/scientific-name';
import './ui/accordion-block-toggle';
import './ui/results-charts';
import './ui/results-map';
import './ui/switch-to-next-post';
import './ui/user-delete-admin-confirm';
import './ui/full-page-form-init';
/* ******** *
 * CALENDAR *
 * ******** */
import './ui/calendar/calendar-switch-date';
import './ui/calendar/calendar-toggle-date-selection';
import './ui/calendar/calendar-toggle';
import './ui/calendar/calendar-hide-legend';
/* ******************* *
 * STATION/OBSERVATION *
 * ******************* */
import './ui/stations-observations/station-search-form-submit';
import './ui/stations-observations/event-post-dates-validate';
import './ui/stations-observations/station-page-header-map';
import './ui/stations-observations/stations-list-page-header-map';
/* ******* *
 * OVERLAY *
 * ******* */
import './ui/overlay/overlay-open';
/* *********** *
 * D3 Calendar *
 * *********** */
import './ui/observation-calendar-chart';
