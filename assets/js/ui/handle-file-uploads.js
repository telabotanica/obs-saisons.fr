const imageType = /^image\//;

export function HandleFileUploads(uploadInput) {
    this.uploadInput = uploadInput;
}

HandleFileUploads.prototype.init = function() {
    if (this.uploadInput) {
        this.initForm();
        this.initEvents();
    } else {
        console.warn('File uploader could not initialize');
    }

};

HandleFileUploads.prototype.initForm = function() {
    const uploadZone = this.uploadInput.closest('.upload-zone');

    this.form = uploadZone.closest('form');
    this.uploadTextPlaceholder = uploadZone.querySelector('.upload-zone-placeholder');
    this.img = uploadZone.querySelector('.placeholder-img');
    this.deleteFileEl = this.form.querySelector('.delete-file');
};

HandleFileUploads.prototype.initEvents = function() {
    const lthis = this,
        isAdvancedUpload = function () {
            const div = document.createElement('div');

            return (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) && 'FormData' in window && 'FileReader' in window;
        }();

    if (isAdvancedUpload) {
        // need jquery event.originalEvent
        $(this.uploadInput).off('drop').on('drop', evt => {
            if (!!evt.originalEvent) {
                lthis.files = evt.originalEvent.dataTransfer.files;
                const hiddenIsDeletePictureInput = document.querySelector('.is-delete-picture');

                if(hiddenIsDeletePictureInput) {
                    hiddenIsDeletePictureInput.remove();
                }
                lthis.displayThumbs();
                lthis.form.addEventListener(
                    'submit',
                    lthis.ajaxSendFileSubmitHandler.bind(lthis)
                );
            }
        });
    }

    this.uploadInput.addEventListener('change', evt => {
        lthis.files = evt.target.files;
        const hiddenIsDeletePictureInput = document.querySelector('.is-delete-picture');

        if(hiddenIsDeletePictureInput) {
            hiddenIsDeletePictureInput.remove();
        }
        lthis.displayThumbs();
    });

    this.onDeleteFile();
};

HandleFileUploads.prototype.displayThumbs = function() {
    if (!this.files) {
        this.uploadTextPlaceholder.classList.remove('hidden');
        this.uploadTextPlaceholder.textContent = 'L’image n’a pas pu être téléchargée.';
    }

    const file = this.files[0];

    if (!imageType.test(file.type)) {
        this.uploadTextPlaceholder.classList.remove('hidden');
        this.uploadTextPlaceholder.textContent = 'Le format du fichier n’est pas valide.';
    }

    const reader = new FileReader();

    this.img.classList.add('obj');
    this.img.file = file;
    reader.onload = (function(aImg) {
        return evt => aImg.src = evt.target.result;
    })(this.img);
    reader.readAsDataURL(file);
    this.uploadTextPlaceholder.classList.add('hidden');
};

HandleFileUploads.prototype.ajaxSendFileSubmitHandler = function(evt) {
    const lthis = this;

    if (this.form.classList.contains('is-uploading')) {
        return false;
    }

    this.form.classList.add('is-uploading');
    this.form.classList.remove('is-error');

    evt.preventDefault();

    const ajaxData = new FormData(this.form),
        formAction = this.form.getAttribute('action');

    if (this.files) {
        const file = this.files[0];

        if (!imageType.test(file.type)) {
            return false;
        }
        ajaxData.append(this.uploadInput.name, file);
    }

    fetch(formAction, {
        method: lthis.form.method,
        body: ajaxData
    }).then(function(response) {
        lthis.form.classList.remove('is-uploading');

        if (response.ok) {
            lthis.form.classList.add('is-success');
            window.location.href = response.url;
        } else {
            lthis.form.classList.add('is-error');
            return Promise.reject(response);
        }
    }).catch(function(error){
        console.warn('Drag’n’drop file upload failed');
        console.warn(error);
    });
};

HandleFileUploads.prototype.onDeleteFile = function() {
    const action = this.form.name,
        lthis = this;

    this.deleteFileEl.addEventListener('click', function (evt) {
        evt.preventDefault();
        evt.stopPropagation();

        const hiddenIsDeletePictureInput = document.createElement('input');

        Object.assign(hiddenIsDeletePictureInput,{
            type : 'hidden',
            classList : 'is-delete-picture',
            name : action + '[isDeletePicture]',
            value : 'true',
        });

         lthis.uploadInput.after(hiddenIsDeletePictureInput);
         lthis.uploadInput.value = '';
         lthis.form.removeEventListener(
             'submit',
             lthis.ajaxSendFileSubmitHandler.bind(lthis)
         );

         lthis.img.classList.remove('obj');
         lthis.img.src = '/media/layout/icons/photo.svg';

         lthis.uploadTextPlaceholder.classList.remove('hidden');
         lthis.uploadTextPlaceholder.textContent = 'Ajoutez une photo';
    });
};

HandleFileUploads.prototype.preSetFile = function(src) {
    if (src && this.uploadInput) {
        this.uploadTextPlaceholder.classList.add('hidden');
        this.img.classList.add('obj');
        this.img.src = src;
    }
};

