import domready from 'mf-js/modules/dom/ready';
import {animateDropdownArrow} from "../animate-dropdown-arrow";
import {toggleVisibility} from "../../lib/toggle-element-visibility";

domready(() => {
    Array.from(document.querySelectorAll('a.item-heading-dropdown')).forEach(dropdown =>
        dropdown.addEventListener('click', event => {
            event.preventDefault();
            const id = dropdown.closest('.list-cards-item').dataset.id;

            animateDropdownArrow(dropdown);
            toggleVisibility(document.querySelector('.periods-calendar[data-id="' + id + '"]'));
        })
    );

    Array.from(document.getElementsByClassName('table-mask-button')).forEach(button =>
        button.addEventListener('click', event => {
            event.preventDefault();

            const id = button.closest('.periods-calendar').dataset.id;

            document.querySelector('.list-cards-item[data-id="' + id + '"] a.item-heading-dropdown').click();
        })
    );
});
