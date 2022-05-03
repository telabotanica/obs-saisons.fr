import domready from 'mf-js/modules/dom/ready';

domready(() => {
    const stationSearchField = document.getElementById('station-search-field');
    const stationSearchForm = document.getElementById('station-search-form');

    if(!!stationSearchForm) {
        // submitting form without submit button
        ['blur', 'DOMAutoComplete'].forEach(
            eventKey => stationSearchField.addEventListener(eventKey, () => {
                if (!!stationSearchField.value) {
                    stationSearchForm.submit();
                    stationSearchField.value = '';
                }
            })
        );
    }
});
