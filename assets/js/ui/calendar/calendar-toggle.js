import domready from 'mf-js/modules/dom/ready';

domready(() => {
    $('a.item-heading-dropdown').off('click').on('click', function (event) {
        event.preventDefault();

        let id = $(this).closest('.list-cards-item').data('id');

        $(this).toggleClass('right-arrow-orange-icon down-arrow-icon');
        $('.periods-calendar[data-id="' + id + '"]').toggle(200);
    });

    $('.table-mask-button').off('click').on('click', function (event) {
        event.preventDefault();

        let id = $(this).closest('.periods-calendar').data('id');

        $('.list-cards-item[data-id="' + id + '"] a.item-heading-dropdown').trigger('click');
    });
});
