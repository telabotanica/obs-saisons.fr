import domready from 'mf-js/modules/dom/ready';
import {resetTabMatchingElements} from '../switch-tabs';
import {observationsToggleCombinedConditions} from '../switch-tabs';
import {toggleVisibility} from "../../lib/toggle-element-visibility";

domready(() => {
    document.getElementsByClassName('periods-calendar').forEach(calendar => {
        calendar.querySelectorAll('.dropdown-link').forEach(dropdownLink => {
            dropdownLink.addEventListener('click', evt => {
                evt.preventDefault();

                const activeDate = dropdownLink.textContent;

                calendar.querySelector('.active-year').textContent = activeDate;
                calendar.querySelectorAll('.dropdown-link.hidden').forEach(hiddenYear => hiddenYear.classList.remove('hidden'));
                dropdownLink.classList.add('hidden');
                calendar.querySelector('.dropdown-list').classList.add('hidden');
                // show/hide observations
                calendar.querySelectorAll('.stage-marker').forEach( observation => {
                    const tabsHolder = document.querySelector('.tabs-holder:not(.stations)');
                    toggleVisibility(
                        observation,
                        observationsToggleCombinedConditions(observation, activeDate)
                    );
                    resetTabMatchingElements(tabsHolder);
                });
            });
        });
    });
});
