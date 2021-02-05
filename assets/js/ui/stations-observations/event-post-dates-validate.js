import domready from 'mf-js/modules/dom/ready';

import {displayError} from '../error-display'

export const generateComparableFormatedDate = (dateData) => {
    if (/^([\d]{2}\/){2}[\d]{4}$/.test(dateData)) {
        dateData = dateData.split('/').reverse();
    } else if(/^[\d]{4}(-[\d]{2}){2}$/.test(dateData)) {
        dateData = dateData.split('-');
    } else {
        dateData = dateData
            .toISOString()
            .substr(0, 10)
            .split('-');
    }

    return dateData.join('');
};

domready(() => {
    let $postEventsStartDate = $('#post_events_startDate'),
        $postEventsEndDate = $('#post_events_endDate'),
        errorMessage = '',
        todayDate = generateComparableFormatedDate(new Date());

    $postEventsStartDate.on('change', function () {
        let hasStartDate = valOk($(this).val()),
            hasEndDate = valOk($postEventsEndDate.val()),
            isInvalidStartDate = !hasStartDate;

        errorMessage = 'Vous devez entrer une date de début valide';

        if (hasStartDate && hasEndDate) {
            let startDate = generateComparableFormatedDate($(this).val()),
                endDate = generateComparableFormatedDate($postEventsEndDate.val()),
                minDate =  generateComparableFormatedDate(new Date('2006-01-01')),
                isBeforeBeginningOfTime = minDate > startDate,
                isTimeTrip = endDate < startDate;

            isInvalidStartDate = isBeforeBeginningOfTime || isTimeTrip;

            if (isBeforeBeginningOfTime) {
                errorMessage = 'Cette date est antérieure à ODS';
            } else if (isTimeTrip) {
                errorMessage = 'Votre date de fin précède votre date de début.';
            }
        }

        if (isInvalidStartDate) {
            displayError($(this), errorMessage, 'invalid-date');
        }
    });

    $postEventsEndDate.on('change', function () {
        let hasEndDate = valOk($(this).val()),
            hasStartDate = valOk($postEventsStartDate.val()),
            isInvalidEndDate = !hasEndDate;

        errorMessage = 'Vous devez entrer une date de fin valide';

        if(hasEndDate) {
            let endDate = generateComparableFormatedDate($(this).val());

            if(todayDate > endDate) {
                isInvalidEndDate = true;
                errorMessage = 'Nous ne publions pas les évènements terminés'

            } else if(hasStartDate) {
                let startDate = generateComparableFormatedDate($postEventsStartDate.val());

                if(startDate > endDate) {
                    isInvalidEndDate = true;
                    errorMessage = 'Votre date de fin précède votre date de début.';
                }
            }
        }

        if(isInvalidEndDate) {
            displayError($(this), errorMessage, 'invalid-date');
        }
    });
});
