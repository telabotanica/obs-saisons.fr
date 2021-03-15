import domready from 'mf-js/modules/dom/ready';
import {OdsPlaces} from "./location/locality";
import {HandleFileUploads} from "./handle-file-uploads";

const initFullPageFormFileUploads = formContainer => {
    const uploadInput = formContainer.querySelector('.upload-input');

    if (uploadInput) {
        const fileUploadHandler = new HandleFileUploads(),
            image = uploadInput.closest('.form-col').dataset.image;

        fileUploadHandler.init();

        if (!!image) {
            const placeholderImgEl = formContainer.querySelector('.placeholder-img');

            formContainer.querySelector('.upload-zone-placeholder').classList.add('hidden');
            placeholderImgEl.classList.add('obj');
            placeholderImgEl.src = image;
        }
    }
};

const initFullPageFormLocality = formContainer => {
    const formName = formContainer.querySelector('form').name,
        localityTargetInput = document.getElementById(formName+'_location');

    if (localityTargetInput) {
        const odsPlaces = new OdsPlaces(localityData =>
            localityTargetInput.value = localityData['display_name']
        );
        odsPlaces.init();
    }
};

domready(() => {
    const formContainers = document.querySelectorAll('.saisie-container.page');// ".page" means: not an overlay form

    if (formContainers.length) {
        formContainers.forEach(formContainer => {
            initFullPageFormFileUploads(formContainer);
            initFullPageFormLocality(formContainer);
        });
    }
});
