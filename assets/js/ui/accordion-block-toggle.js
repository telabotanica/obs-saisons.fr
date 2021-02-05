import domready from 'mf-js/modules/dom/ready';

domready(() => {
    $('a.accordion-title-dropdown').off('click').on('click', function (event) {
        event.preventDefault();

        let $thisBlock = $(this).closest('.accordion-block');

        $(this).toggleClass('right-arrow-orange-icon down-arrow-icon');
        $('.accordion-content', $thisBlock).toggle(200);
    });
});
