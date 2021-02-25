import domready from 'mf-js/modules/dom/ready';
import {stationLocation, individualOvelayManageSpecies, observationOvelayManageIndividualAndEvents} from "./overlay-open";

export const closeOverlay = ($overlay) => {
    $('body').css('overflow', 'auto');
    $overlay.addClass('hidden');

    if(valOk($('form',$overlay))) {
        let $form = $('form', $overlay);

        if ($overlay.hasClass('edit')) {
            $form.attr('action', $form.data('formActionReset'));
            $overlay.removeClass('edit');
        }
        $form.get(0).reset();

        $overlay.find('option').removeAttr('hidden disabled');

        if ($overlay.hasClass('individual')) {
            individualOvelayManageSpecies($('.open-individual-form-all-station').data().species.toString(), true);
        } else {
            if ($overlay.hasClass('observation')) {
                observationOvelayManageIndividualAndEvents($('.open-observation-form-all-station').data());
                $('.ods-form-warning').addClass('hidden').text('');
            } else if($overlay.hasClass('station')) {
                stationLocation.removeMap();
                $('.ap-icon-clear').trigger('click');
            }
            $('.delete-file').trigger('click');
            $('.is-delete-picture').remove();
        }
        $('.show-on-edit', $overlay).attr('href','');
    } else if ($overlay.hasClass('obs-infos')) {
        $('.saisie-container').find('.obs-info').text('');
    }
}

// open overlay
domready(() => {
    $('.overlay a.bt-annuler').off('click').on('click', function (event) {
        event.preventDefault();

        closeOverlay($(this).closest('.overlay'));
    });
    $('body').on('keydown', function (event) {
        const ESC_KEY_STRING = /^Esc(ape)?/;
        if (27 === event.keyCode || ESC_KEY_STRING.test(event.key)) {
            closeOverlay($('.overlay:not(.hidden)'));
        }
    });
});
