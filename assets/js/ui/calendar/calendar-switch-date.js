import domready from 'mf-js/modules/dom/ready';
import {resetTabMatchingElements} from '../switch-tabs';
import {observationsToggleCombinedConditions} from '../switch-tabs';

domready(() => {
    $('.periods-calendar .dropdown-link').off('click').on('click', function (event) {
        event.preventDefault();

        let $thisCalendar = $(this).closest('.periods-calendar'),
            activeDate = $(this).text();

        $('.active-year', $thisCalendar).text(activeDate);
        $('.dropdown-link.hidden', $thisCalendar).removeClass('hidden');
        $(this).addClass('hidden');
        $('.dropdown-list', $thisCalendar).addClass('hidden');
        // show/hide observations
        $('.stage-marker', $thisCalendar).each( function () {
            let $element = $(this);

            if(observationsToggleCombinedConditions($element, activeDate)) {
                $element.show(200);
            } else {
                $element.hide(200);
            }
            resetTabMatchingElements($('.tabs-holder:not(.stations)'));
        });
    });
});
