import domready from 'mf-js/modules/dom/ready';

const imageType = /^image\//;

export function displayThumbs(files) {
    let uploadZonePlaceholder = $('.upload-zone-placeholder');

    if (!files) {
        uploadZonePlaceholder.removeClass('hidden').text('L’image n’a pas pu être téléchargée.');
    }

    let file = files[0];

    if (!imageType.test(file.type)) {
        uploadZonePlaceholder.removeClass('hidden').text('Le format du fichier n’est pas valide.');
    }

    let $img = $('img.placeholder-img'),
        reader = new FileReader();

    $img.addClass('obj');
    $img.file = file;
    reader.onload = (function(aImg) {
        return function(event) {
            aImg.attr('src', event.target.result);
        };
    })($img);
    reader.readAsDataURL(file);
    uploadZonePlaceholder.addClass('hidden');
}

export function ajaxSendFile($picture, files) {
    let $form = $picture.closest('form');

    $form.on('submit.ddupload', function(e) {
        if ($form.hasClass('is-uploading')) {
            return false;
        }

        $form.addClass('is-uploading').removeClass('is-error');

        e.preventDefault();

        let ajaxData = new FormData($form.get(0));

        if (files) {
            let file = files[0];

            if (!imageType.test(file.type)) {
                return false;
            }
            ajaxData.append($picture.attr('name'), file);
        }

        $.ajax({
            type: $form.attr('method'),
            url: $form.attr('action'),
            data: ajaxData,
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            complete: function () {
                $form.removeClass('is-uploading');
            },
            success: function (data) {
                $form.addClass(data.success ? 'is-success' : 'is-error');
                window.location.href = data.redirect;
            },
            error: function () {
                console.log('Drag’n’drop file upload failed.');
            }
        });
    });
}

export function onDeleteFile() {
    $('.delete-file').off('click').on('click', function (event) {
        event.preventDefault();
        let action = $(this).closest('form').attr('name');

        $('.upload-zone .upload-input')
            .val('')
            .after(
                '<input type="hidden" class="is-delete-picture" name="'+action+'[isDeletePicture]" value="true">'
            )
            .closest('form').off('submit.ddupload')
        ;
        $('.placeholder-img').removeClass('obj').attr('src', '/media/layout/icons/photo.svg');
        $('.upload-zone-placeholder').removeClass('hidden').text('Ajoutez une photo');

    });
}

domready(() => {
    let droppedFiles = false,
        $picture = $('.upload-zone .upload-input');

    if (valOk($picture)) {
        let isAdvancedUpload = function () {
            let div = document.createElement('div');

            return (('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)) && 'FormData' in window && 'FileReader' in window;
        }();

        if (isAdvancedUpload) {
            $picture
                .on('drag dragstart dragend dragover dragenter dragleave drop', function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                })
                .on('dragover dragenter', function () {
                    $picture.addClass('is-dragover');
                })
                .on('dragleave dragend drop', function () {
                    $picture.removeClass('is-dragover');
                })
                .on('drop', function (event) {
                    if (event.originalEvent) {
                        droppedFiles = event.originalEvent.dataTransfer.files;
                        $('.is-delete-picture').remove();
                        displayThumbs(droppedFiles);
                        ajaxSendFile($(this), droppedFiles);
                    }
                });


        }
        $picture.on('change', function (event) {
            droppedFiles = event.target.files;
            console.log(droppedFiles);
            $('.is-delete-picture').remove();
            displayThumbs(droppedFiles);
        });

        onDeleteFile()
    }
});

