import domready from 'mf-js/modules/dom/ready';
import {handleErrorMessages} from '../error-display'
import {generateComparableFormatedDate} from "../date-format";

domready(() => {
    const postEventsStartDateEl = document.getElementById('event_post_startDate'),
        postEventsEndDateEl = document.getElementById('event_post_endDate'),
        todayDate = generateComparableFormatedDate(new Date()),
        invalidDateClassAttr = 'invalid-date';
    let errorMessage;

    if(!!postEventsStartDateEl && !!postEventsEndDateEl) {
        postEventsStartDateEl.addEventListener('blur', function () {
            const startDateValue = postEventsStartDateEl.value,
                endDateValue = postEventsEndDateEl.value;

            errorMessage = '';
            if(!!startDateValue) {
                const startDate = generateComparableFormatedDate(startDateValue),
                    minDate = generateComparableFormatedDate(new Date('2006-01-01'));

                if (minDate > startDate) {
                    errorMessage = 'Cette date est antérieure à ODS';
                }
                if (!!endDateValue && !errorMessage) {
                    const endDate = generateComparableFormatedDate(endDateValue);

                    if (endDate < startDate) {
                        errorMessage = 'Votre date de fin précède votre date de début.';
                    }
                }
            } else {
                errorMessage = 'Vous devez entrer une date de début valide';
            }
            handleErrorMessages(
                postEventsStartDateEl,
                errorMessage,
                invalidDateClassAttr
            );
        });

        postEventsEndDateEl.addEventListener('blur', function () {
            const endDateValue = postEventsEndDateEl.value,
                startDateValue = postEventsStartDateEl.value;

            errorMessage = '';
            if (!!endDateValue) {
                const endDate = generateComparableFormatedDate(endDateValue);

                if (todayDate > endDate) {
                    errorMessage = 'Nous ne publions pas les évènements terminés'
                } else if (!!startDateValue) {
                    const startDate = generateComparableFormatedDate(startDateValue);

                    if (startDate > endDate) {
                        errorMessage = 'Votre date de fin précède votre date de début.';
                    }
                }
            }

            handleErrorMessages(
                postEventsEndDateEl,
                errorMessage,
                invalidDateClassAttr
            );
        });
    }
});
