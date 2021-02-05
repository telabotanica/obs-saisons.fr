import domready from 'mf-js/modules/dom/ready';

export const resetTabMatchingElements = ($tabsHolder) => {
    let activeTab = $tabsHolder.data('active');

    if(activeTab !== 'all') {
        $('[data-tab]:not(.tab)').each(function () {
            if(activeTab !== $(this).data('tab')) {
                $(this).hide();
            }
        });
    }
};

export const observationsToggleCombinedConditions = ($element, activeDate, matchsTab = null) => {
    let showObs = $element.data('year').toString() === activeDate;

    if (null !== matchsTab) {// if matchsTab is defined it is boolean
        showObs &= matchsTab;
    }
    return showObs;
};

// switch between tabs
domready(() => {
    let $tabsHolder = $('.tabs-holder:not(.stations)');
    resetTabMatchingElements($tabsHolder);

    $('.tab').off('click').on('click', function (event) {
        event.preventDefault();

        let activeTab = $(this).data('tab');

        $tabsHolder.data('active', activeTab).attr('data-active', activeTab);
        $('[data-tab]').each(function (i, element) {
            let $element = $(element);

            if ($element.hasClass('tab')) {
                $element.toggleClass('not',(activeTab !== $element.data('tab')));
            } else {
                let toggleElement = ('all' === activeTab || $element.data('tab') === activeTab);
                // for the case of observations
                if (valOk($element.data('year'))) {
                    let activeDate = $element.closest('.table-container').find('.active-year').text();

                    toggleElement = observationsToggleCombinedConditions($element, activeDate, toggleElement);
                }
                if(toggleElement) {
                    $element.show(200);
                } else {
                    $element.hide(200);
                }
            }
        });
    });
});
