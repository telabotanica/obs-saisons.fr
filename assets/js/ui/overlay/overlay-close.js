import domready from 'mf-js/modules/dom/ready';
import {stationLocation, individualOvelayManageSpecies, observationOvelayManageIndividualAndEvents} from "./overlay-open";

export const closeOverlay = overlay => {
    const form = overlay.querySelector('form'),
        overlayClasses = overlay.classList;

    document.body.style.overflow = 'auto';
    overlay.classList.add('hidden');

    if(form) {
        resetEditionForm(overlay, form);
        form.reset();
        resetAllSelectOptions(form);

        const openIndividualGlobalForm = document.querySelector('.open-individual-form-all-station');

        if (overlayClasses.contains('individual') && openIndividualGlobalForm) {
            individualOvelayManageSpecies(openIndividualGlobalForm.dataset.species.toString(), true);
        } else {
            if (overlayClasses.contains('observation')) {
                if(openIndividualGlobalForm) {
                    observationOvelayManageIndividualAndEvents(overlay, openIndividualGlobalForm.dataset);
                }
                closeDetailsField();
                document.getElementsByClassName('ods-form-warning').forEach(warningEl => {
                    warningEl.classList.add('hidden');
                    warningEl.textContent = '';
                });
            } else if(overlayClasses.contains('station')) {
                stationLocation.removeMap();
                stationLocation.odsPlaces.resetPlacesSearch();
            }
            resetUploadFilesComponent(form);
        }
    } else if (overlayClasses.contains('obs-infos')) {
        overlay.textcontent = '';
    }
};

const resetAllSelectOptions = form => {
    const optionElements = form.getElementsByTagName('option');

    if(optionElements) {
        optionElements.forEach(
            optionEl => {
                ['hidden', 'disabled'].forEach(
                    attribute => optionEl.removeAttribute(attribute)
                );
            }
        );
    }
};

const resetEditionForm = (
    overlay,
    form
) => {
    if (overlay.classList.contains('edit')) {
        form.action = form.dataset.formActionReset;
        overlay.classList.remove('edit');
    }
    overlay.getElementsByClassName('show-on-edit').forEach(
        shownLinkOnEdition => shownLinkOnEdition.setAttribute('href', '')
    );
};

const closeDetailsField = function() {
    // hide button
    document.querySelector('.button-form-container').classList.remove('hidden');
    // show field
    document.querySelector('.details-container').classList.add('hidden');

};

const resetUploadFilesComponent = form => {
    const deleteFileButton = form.querySelector('.delete-file'),
        hiddenIsDeletePictureInput = form.querySelector('.is-delete-picture');

    if(deleteFileButton && hiddenIsDeletePictureInput) {
        deleteFileButton('.delete-file').click();
        hiddenIsDeletePictureInput.remove();
    }
};

const closeOverlayOnButtonClick = overlays => {
    overlays.forEach(overlay => {
        overlay.querySelector('.bt-cancel').addEventListener('click', evt => {
            evt.preventDefault();

            closeOverlay(overlay);
        });
    });
};

const closeOverlayOnEscapeKey = overlays => {
    document.body.addEventListener('keydown', function (evt) {
        const ESC_KEY_STRING = /^Esc(ape)?/;

        if(27 === evt.keyCode || ESC_KEY_STRING.test(evt.key)) {
            const openedOverlay = Array.from(overlays).find(overlay => !overlay.classList.contains('hidden'));

            if (openedOverlay) {
                const target = evt.target,
                    isOdsPlacesDropdownEscape = ['ods-places-suggestion','ods-places'].some(
                        className => target.classList.contains(className)
                    );

                if (!isOdsPlacesDropdownEscape) {
                    closeOverlay(openedOverlay);
                }
            }
        }
    });
};

export const closeOverlayOnClickOut = overlay => {
    overlay.addEventListener('click', evt => {
        if(
            !evt.target.closest('.saisie-container, .obs-info-container') &&
            !evt.target.classList.contains('ods-places-suggestion')
        ) {
            closeOverlay(overlay);
        }
    });
};

domready(() => {
    const overlays = document.getElementsByClassName('overlay');

    if(overlays.length) {
        closeOverlayOnButtonClick(overlays);
        closeOverlayOnEscapeKey(overlays);
    }
});
