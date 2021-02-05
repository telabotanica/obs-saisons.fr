import domready from 'mf-js/modules/dom/ready';

export const $stationSearchField = $('#station-search-field');
export const $stationSearchForm = $('#station-search-form');

domready(() => {
    // submitting form without submit button
    $stationSearchField.on('blur', function () {
        $stationSearchForm.trigger('submit');
    });
    // form is being submitted on blur or on enter key press
    $stationSearchForm.on('submit', function (event) {
        // avoid submitting empty strings
        if(!valOk($.trim($stationSearchField.val()))) {
            event.preventDefault();
        }
    });
});
