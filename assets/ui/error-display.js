export const displayError = (field, errorMessage, classAttr) => {
    const errorP = document.createElement('p');

    errorP.classList.add(classAttr,'field-help-text', 'help-text');
    errorP.style.color = 'red';
    errorP.textContent = errorMessage;

    removeErrors(classAttr);
    field.value = '';
    field.after(errorP);
};

export const removeErrors = classAttr => {
    Array.from(document.getElementsByClassName(classAttr)).forEach(errorMessageElement => {
        errorMessageElement.remove();
    });
};

export const handleErrorMessages = (
    element,
    message,
    classAttr
) => {
    if (!!message) {
        displayError(element, message, classAttr);
    } else {
        removeErrors(classAttr);
    }
};
