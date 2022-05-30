import domready from 'mf-js/modules/dom/ready';
import {toggleVisibility} from "../lib/toggle-element-visibility";
import {animateDropdownArrow} from "./animate-dropdown-arrow";

domready(() => {
    Array.from(document.querySelectorAll('a.accordion-title-dropdown')).forEach(
        dropdown => dropdown.addEventListener('click',event => {
            event.preventDefault();

            const accordionContent = dropdown.closest('.accordion-block').querySelector('.accordion-content');

            animateDropdownArrow(dropdown);
            toggleVisibility(accordionContent);
        })
    );
});
