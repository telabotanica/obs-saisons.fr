import domready from 'mf-js/modules/dom/ready';

domready(() => {
    let $uploadInput = $('.upload-input'),
        isPageForm = $uploadInput.closest('.saisie-container').hasClass('page'),
        image = $uploadInput.closest('.form-col').data('image');

    if (isPageForm && valOk(image) && '' !== image) {
        $('.upload-zone-placeholder').addClass('hidden');
        $('img.placeholder-img').addClass('obj').attr('src', image);
    }

    let $places = $('#ods-places');

    if (0 < $places.length && valOk($places.val())) {
        $places.siblings('button.ap-input-icon').toggle();
    }
});
