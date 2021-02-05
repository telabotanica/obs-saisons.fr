import domready from 'mf-js/modules/dom/ready';

domready(() => {
    $('.helper-legend .hide-button').click(function (event) {
        event.preventDefault();

        $('.pages-container').find('.helper-legend').hide(200);
    })
});
