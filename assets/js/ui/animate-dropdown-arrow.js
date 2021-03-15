export const animateDropdownArrow = dropdownElement => {
    const arrowAnimationClasses = ['right-arrow-orange-icon', 'down-arrow-icon'];

    arrowAnimationClasses.forEach(
        classAttr => dropdownElement.classList.toggle(classAttr)
    );
};
