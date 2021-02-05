import domready from 'mf-js/modules/dom/ready';

domready(() => {
    $('.dropdown-toggle').off('click').on('click', function (event) {
        event.preventDefault();

        $(this).siblings('.dropdown-list').toggleClass('hidden');
    })
});
