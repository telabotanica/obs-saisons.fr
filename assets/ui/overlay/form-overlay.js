/* ************ *
 * FORM OVERLAY *
 * ************ */

import {Overlay} from "./overlay";

export function FormOverlay(openOverlayButton) {
    Overlay.call(this, openOverlayButton);
}
FormOverlay.prototype = Object.create(Overlay.prototype);
FormOverlay.prototype.constructor = FormOverlay;

FormOverlay.prototype.init = function() {
    Overlay.prototype.init.call(this);

    this.form.reset();
    this.setOverlayEditForm();
};

FormOverlay.prototype.closeOverlay = function () {
    Overlay.prototype.closeOverlay.call(this);
    this.resetEditionForm();
    this.form.reset();
    this.resetAllSelectOptions();
};

FormOverlay.prototype.setOverlayEditForm = function() {
    if (this.openOverlayButton.classList.contains('edit')) {
        const editionPath = `/${this.dataAttrs.open}/`;
        this.overlay.classList.add('edit');
        this.form.action = editionPath + 'edit';
        this.form.dataset.formActionReset = editionPath + 'new';
    }
};

FormOverlay.prototype.resetEditionForm = function() {
    if (this.overlay.classList.contains('edit')) {
        this.form.action = this.form.dataset.formActionReset;
        this.overlay.classList.remove('edit');
    }
    Array.from(this.overlay.getElementsByClassName('show-on-edit')).forEach(
        shownLinkOnEdition => shownLinkOnEdition.href = ''
    );
};

FormOverlay.prototype.resetAllSelectOptions = function () {
    const lthis = this;
    Array.from(this.form.getElementsByTagName('option')).forEach(optionEl => lthis.selectOptionsLockToggle(optionEl, false));
};

// returns an array of values from data attributes value
FormOverlay.prototype.getDataAttrValuesArray = function (dataAttrValue) {
    if (0 > dataAttrValue.indexOf(',')) {
        return [dataAttrValue];
    } else {
        return dataAttrValue.split(',');
    }
};

FormOverlay.prototype.updateSelectOptions = function(
    selectEl,
    itemsToMatch,
    sortOptions = true
) {
    const lthis = this,
        selectName = selectEl.dataset.name;

    selectEl.classList.toggle('disabled',(1 >= itemsToMatch.length && sortOptions));
    Array.from(selectEl.getElementsByTagName('option')).forEach(option => lthis.selectOptionsLockToggle(option, false));
    selectEl.closest('form').reset();

    if(sortOptions) {
        Array.from(selectEl.querySelectorAll('.' + selectName + '-option')).forEach(element => {
            if (itemsToMatch.includes(element.value.toString())) {
                if (1 === itemsToMatch.length && element.classList.contains(selectName + '-' + itemsToMatch[0])) {
                    element.setAttribute('selected', 'selected');
                }
            } else {
                lthis.selectOptionsLockToggle(element);
            }
        });
        if(1 === itemsToMatch.length) {
            selectEl.value = itemsToMatch[0];
        }
    }
};

FormOverlay.prototype.selectOptionsLockToggle = function(element, lock = true) {
    if (lock) {
        element.setAttribute('disabled','disabled');
        element.setAttribute('hidden','hidden');
    } else {
        element.removeAttribute('disabled');
        element.removeAttribute('hidden');
    }
};

FormOverlay.prototype.selectOption = function (element) {
    this.selectOptionsLockToggle(element, false);
    element.setAttribute('selected', 'selected');
};

FormOverlay.prototype.resetUploadFilesComponent = function (form) {
    const deleteFileButton = this.form.querySelector('.delete-file'),
        hiddenIsDeletePictureInput = this.form.querySelector('.is-delete-picture');

    if(deleteFileButton) {
        deleteFileButton.click();
    }
    if(hiddenIsDeletePictureInput) {
        hiddenIsDeletePictureInput.remove();
    }
};
