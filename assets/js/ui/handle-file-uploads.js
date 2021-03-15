const imageType = /^image\//;

export function HandleFileUploads() {}

HandleFileUploads.prototype.init = function() {
    const uploadInputEl = document.querySelector('.upload-zone .upload-input');

    if(uploadInputEl) {
        this.initForm(uploadInputEl);
        this.initEvents();
    }
};

HandleFileUploads.prototype.initForm = function(uploadInputEl) {
    this.uploadInputEl = uploadInputEl;
    this.uploadZonePlaceholderEL = document.querySelector('.upload-zone-placeholder');
    this.imgEl = document.querySelector('.placeholder-img');
    this.form = uploadInputEl.closest('form');
    this.deleteFileEl = document.querySelector('.delete-file');
};

HandleFileUploads.prototype.initEvents = function() {
    const lthis = this;
    const isAdvancedUpload = function () {
        const div = document.createElement('div');

        return (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) && 'FormData' in window && 'FileReader' in window;
    }();

    if (isAdvancedUpload) {
        ['drag', 'dragstart', 'dragend', 'dragover', 'dragenter', 'dragleave', 'drop'].forEach(
            eventType => lthis.uploadInputEl.addEventListener(eventType, function (evt) {
                evt.preventDefault();
                evt.stopPropagation();
            })
        );

        ['dragover', 'dragenter'].forEach(
            eventType => lthis.uploadInputEl.addEventListener(eventType, function () {
                lthis.uploadInputEl.classList.add('is-dragover');
            })
        );

        ['dragleave', 'dragend', 'drop'].forEach(
            eventType => lthis.uploadInputEl.addEventListener(eventType, function () {
                lthis.uploadInputEl.classList.remove('is-dragover');
            })
        );

        $(this.uploadInputEl).on('drop', function (evt) {
            if (!!evt.originalEvent) {
                lthis.files = evt.originalEvent.dataTransfer.files;
                const hiddenIsDeletePictureInput = document.querySelector('.is-delete-picture');

                if(hiddenIsDeletePictureInput) {
                    hiddenIsDeletePictureInput.remove();
                }
                lthis.displayThumbs();
                lthis.uploadInputEl.closest('form').addEventListener(
                    'submit',
                    lthis.ajaxSendFileSubmitHandler.bind(lthis)
                );
            }
        });
    }

    this.uploadInputEl.addEventListener('change', function (evt) {
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
        this.uploadZonePlaceholderEL.classList.remove('hidden');
        this.uploadZonePlaceholderEL.textContent = 'L’image n’a pas pu être téléchargée.';
    }

    const file = this.files[0];

    if (!imageType.test(file.type)) {
        this.uploadZonePlaceholderEL.classList.remove('hidden');
        this.uploadZonePlaceholderEL.textContent = 'Le format du fichier n’est pas valide.';
    }

    const reader = new FileReader();

    this.imgEl.classList.add('obj');
    this.imgEl.file = file;
    reader.onload = (function(aImg) {
        return function(evt) {
            aImg.setAttribute('src', evt.target.result);
        };
    })(this.imgEl);
    reader.readAsDataURL(file);
    this.uploadZonePlaceholderEL.classList.add('hidden');
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
        ajaxData.append(this.uploadInputEl.name, file);
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

         lthis.uploadInputEl.after(hiddenIsDeletePictureInput);
         lthis.uploadInputEl.value = '';
         lthis.uploadInputEl.closest('form').removeEventListener(
             'submit',
             lthis.ajaxSendFileSubmitHandler
         );

         lthis.imgEl.classList.remove('obj');
         lthis.imgEl.src = '/media/layout/icons/photo.svg';

         lthis.uploadZonePlaceholderEL.classList.remove('hidden');
         lthis.uploadZonePlaceholderEL.textContent = 'Ajoutez une photo';
    });
};

