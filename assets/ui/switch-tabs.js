import domready from 'mf-js/modules/dom/ready';
import {toggleVisibility} from "../lib/toggle-element-visibility";

export const resetTabMatchingElements = (tabsHolder) => {
    const activeTab = tabsHolder.dataset.active,
        targets =  document.querySelectorAll('[data-tab]:not(.tab)');

    if(activeTab !== 'all' && targets) {
        Array.from(targets).forEach(target => {
            if(activeTab !== target.dataset.tab) {
                target.classList.add('hide');
            }
        });
    }
};

export const observationsToggleCombinedConditions = (
    element,
    activeDate,
    matchesTab = null
) => {
    let showObs = element.dataset.year.toString() === activeDate;

    if (null !== matchesTab) {// if matchesTab is defined it is boolean
        showObs &= matchesTab;
    }

    return showObs;
};

// switch between tabs
domready(() => {
    const tabsHolder = document.querySelector('.tabs-holder:not(.stations)');

    if (tabsHolder) {
        const tabElements = tabsHolder.getElementsByClassName('tab'),
            elementsWithDataTab = document.querySelectorAll('[data-tab]');

        resetTabMatchingElements(tabsHolder);

        Array.from(tabElements).forEach(tab => {
            tab.addEventListener('click', evt => {
                const activeTab = tab.dataset.tab;

                evt.preventDefault();

                tabsHolder.dataset.active = activeTab;
                Array.from(elementsWithDataTab).forEach(elementWithDataTab => {
                    if (elementWithDataTab.classList.contains('tab')) {
                        elementWithDataTab.classList.toggle('not', (activeTab !== elementWithDataTab.dataset.tab));
                    } else {
                        let matchesTab = ('all' === activeTab || elementWithDataTab.dataset.tab === activeTab);
                        // for the case of observations
                        if (!!elementWithDataTab.dataset.year) {
                            let activeDate = elementWithDataTab.closest('.table-container').querySelector('.active-year').textContent;

                            matchesTab = observationsToggleCombinedConditions(elementWithDataTab, activeDate, matchesTab);
                        }
                        toggleVisibility(elementWithDataTab, matchesTab);
                    }
                });
            })
        });
    }
});
