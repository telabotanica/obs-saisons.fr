import domready from "mf-js/modules/dom/ready";

export const toggleVisibility = (element, matchesCondition = null) => {
    if(null !== matchesCondition) {
        element.classList.toggle('hide', !matchesCondition);
    } else {
        element.classList.toggle('hide');
    }
};

domready(() => {
    const hiddenElements = document.getElementsByClassName('hide');
    if (hiddenElements) {
        Array.from(hiddenElements).forEach(hiddenElement => hiddenElement.classList.add('toggle-visibility'));
    }
});
